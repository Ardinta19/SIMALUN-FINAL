<?php

namespace App\Notifications\Auth;

use App\Support\Laundry;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Versi reset password dengan nuansa Azka Laundry — bukan template default
 * Laravel. Subjek, salam, dan isi email diturunkan dari config/laundry.php
 * supaya identitasnya konsisten dengan struk dan halaman publik.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $brand = Laundry::name();
        $expiresInMinutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset Password Akun '.$brand)
            ->view('emails.reset-password', [
                'name' => $notifiable->name ?? 'Pelanggan '.$brand,
                'url' => $url,
                'minutes' => $expiresInMinutes,
            ]);
    }

    /**
     * Pisahkan pembuatan URL agar mudah diuji dan tetap menghormati custom
     * URL callback yang mungkin di-set di service provider.
     */
    protected function resetUrl($notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
