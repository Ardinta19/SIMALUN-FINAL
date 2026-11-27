<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard untuk pengetatan keamanan basic.
 *
 * Cover:
 *  - Mass-assignment privilege: register tidak boleh bisa set role=admin
 *    via field tersembunyi.
 *  - Header keamanan ke-set di response (X-Content-Type-Options dll).
 *  - Throttle reset password aktif.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_tidak_bisa_di_inject_role_admin_lewat_request(): void
    {
        // Simulasi attacker kirim field 'role' tambahan di payload register.
        // Karena 'role' di-keluarin dari $fillable, mass-fill bakal ignore.
        // RegisterController emang udah set role hardcoded ke 'customer',
        // jadi test ini juga ngeguard regression seandainya dev nambah
        // User::create($request->all()) di tempat lain.
        $response = $this->post(route('register.post'), [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'phone' => '81234567899',
            'gender' => 'L',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => 'Jl. Attempt No. 1, lengkap',
            'address_note' => null,
            'terms' => 'on',
            'role' => 'admin', // ← injection attempt
            'is_active' => false,
            'email_verified_at' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'attacker@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        // is_active default true di DB; cek truthy bukan strict boolean
        // karena tidak ada cast eksplisit di User model (kolom INT tinyint).
        $this->assertTrue((bool) $user->is_active);
        $this->assertNull($user->email_verified_at);
    }

    public function test_security_headers_di_set_di_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-XSS-Protection', '0');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
    }

    public function test_reset_password_throttle_aktif(): void
    {
        // 6 request berturut dalam 1 menit — limit 5/menit, request ke-6
        // harus dapet 429 Too Many Requests.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.update.reset'), [
                'token' => 'invalid-token',
                'email' => 'someone@example.com',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);
        }

        $response = $this->post(route('password.update.reset'), [
            'token' => 'invalid-token',
            'email' => 'someone@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Login dibatasi di tingkat rute, dan kelas `LoginRequest` bawaan Breeze
     * (yang tidak pernah dipakai controller mana pun) sudah dihapus supaya
     * tidak ada dua mekanisme pembatasan yang salah satunya mati.
     */
    public function test_login_dibatasi_di_tingkat_rute(): void
    {
        $rute = (string) file_get_contents(base_path('routes/web.php'));
        $awal = strpos($rute, "Route::post('/login'");
        $this->assertNotFalse($awal, 'Rute login tidak ditemukan.');

        $potongan = substr($rute, $awal, 400);
        $this->assertStringContainsString("throttle:5,1", $potongan, 'Rute login harus dibatasi 5 percobaan per menit.');
        $this->assertStringNotContainsString('LoginRequest::class', $potongan);

        $this->assertFileDoesNotExist(app_path('Http/Requests/Auth/LoginRequest.php'));
    }
}
