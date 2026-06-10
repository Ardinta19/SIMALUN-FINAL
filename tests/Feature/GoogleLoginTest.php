<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Siapkan mock Socialite yang mengembalikan user Google tertentu.
     */
    private function mockGoogleUser(string $id, string $email, bool $emailVerified = true): void
    {
        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn($id);
        $abstractUser->shouldReceive('getEmail')->andReturn($email);
        $abstractUser->user = ['email_verified' => $emailVerified];

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_user_terdaftar_bisa_login_via_google_dan_google_id_tertaut(): void
    {
        $user = User::factory()->create([
            'email' => 'pelanggan@gmail.com',
            'is_active' => true,
        ]);

        $this->mockGoogleUser('google-abc-123', 'pelanggan@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('customer.dashboard', absolute: false));
        $this->assertSame('google-abc-123', $user->fresh()->google_id);
    }

    public function test_email_belum_terdaftar_ditolak_dan_tidak_membuat_akun(): void
    {
        $this->mockGoogleUser('google-xyz-999', 'orangasing@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('identifier');
        // Sign-in only: tidak ada akun baru yang dibuat.
        $this->assertDatabaseMissing('users', ['email' => 'orangasing@gmail.com']);
    }

    public function test_akun_nonaktif_diblokir_walau_email_cocok(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@gmail.com',
            'is_active' => false,
        ]);

        $this->mockGoogleUser('google-555', 'nonaktif@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('identifier');
    }

    public function test_email_google_belum_terverifikasi_ditolak(): void
    {
        User::factory()->create([
            'email' => 'verified@gmail.com',
            'is_active' => true,
        ]);

        $this->mockGoogleUser('google-777', 'verified@gmail.com', emailVerified: false);

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
