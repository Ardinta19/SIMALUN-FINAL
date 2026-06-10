<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Login "Sign in with Google" — mode SIGN-IN ONLY (paling aman).
 *
 * Prinsip:
 *  - TIDAK membuat akun baru lewat Google. User harus sudah terdaftar
 *    (customer daftar lewat form; admin/driver dibuat via `user:create`).
 *  - Role SELALU diambil dari record yang sudah ada di DB, tidak pernah
 *    dari Google — tidak ada celah privilege escalation.
 *  - Pencocokan via google_id, lalu fallback ke email Google yang sudah
 *    terverifikasi. Kalau tidak ketemu → ditolak.
 *  - Gating is_active tetap berlaku (sama seperti login biasa).
 */
class GoogleAuthController extends Controller
{
    /**
     * Arahkan user ke halaman pemilihan akun Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Tangani callback dari Google.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            // Log untuk diagnosa (tidak membocorkan detail ke pengguna).
            report($e);

            return redirect()->route('login')->withErrors([
                'identifier' => 'Gagal masuk dengan Google. Silakan coba lagi.',
            ]);
        }

        $email = strtolower((string) $googleUser->getEmail());
        $googleId = $googleUser->getId();

        // Hanya terima email yang sudah diverifikasi Google.
        $emailVerified = $googleUser->user['email_verified'] ?? true;
        if (! $email || ! $emailVerified) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Email Google kamu belum terverifikasi.',
            ]);
        }

        // Cari akun: utamakan google_id yang sudah tertaut, lalu email.
        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        // SIGN-IN ONLY: tidak ada akun → tolak, jangan buat baru.
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Akun dengan email ini belum terdaftar. Silakan daftar terlebih dahulu.',
            ]);
        }

        // Akun nonaktif tidak boleh masuk (sama seperti login biasa).
        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Akun kamu sedang dinonaktifkan. Silakan hubungi admin.',
            ]);
        }

        // Tautkan google_id kalau belum (pertama kali login Google).
        if (empty($user->google_id)) {
            $user->google_id = $googleId;
            $user->save();
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    /**
     * Redirect ke dashboard sesuai role (selaras dengan LoginController).
     */
    private function redirectByRole(string $role)
    {
        $fallback = match ($role) {
            'admin' => route('dashboard.admin'),
            'driver' => route('driver.dashboard'),
            default => route('customer.dashboard'),
        };

        return redirect()->intended($fallback);
    }
}
