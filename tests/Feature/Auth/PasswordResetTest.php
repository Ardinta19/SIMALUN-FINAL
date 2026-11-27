<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status', __('passwords.sent'));
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->get('/reset-password/'.$token);

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.request'));
    }

    /**
     * Surel pemulihan harus memakai template aplikasi ini, bukan template
     * bawaan Laravel yang berbahasa Inggris. Sebelumnya controller mengirim
     * `new ResetPassword(...)` langsung, sehingga override
     * `User::sendPasswordResetNotification()` dan view
     * `emails/reset-password.blade.php` tidak pernah terpakai.
     */
    public function test_surel_reset_memakai_notifikasi_berbahasa_indonesia(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_isi_surel_reset_berbahasa_indonesia_dan_bermerek_toko(): void
    {
        $user = User::factory()->create(['name' => 'Sari Wulandari']);
        $token = Password::broker()->createToken($user);

        $mail = (new ResetPasswordNotification($token))->toMail($user);
        $html = $mail->render();

        $this->assertStringContainsString('Halo, Sari Wulandari', $html);
        $this->assertStringContainsString('Atur ulang kata sandi', $html);
        $this->assertStringContainsString('Berkah Kreatif', $html);
        $this->assertStringNotContainsString('Reset Password Notification', (string) $mail->subject);
    }
}
