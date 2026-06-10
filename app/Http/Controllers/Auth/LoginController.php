<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle authentication dan role-based redirect.
     *
     * Login mendukung 3 format identifier:
     *  - Email  → dicari di kolom `email`
     *  - HP raw → 081234567890, +6281234567890, 6281234567890
     *            → dinormalisasi ke format DB: 81234567890
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Password wajib diisi',
        ]);

        $identifier = trim($credentials['identifier'] ?? $credentials['email'] ?? '');
        $password = $credentials['password'];

        if (! $identifier) {
            return back()->withErrors([
                'identifier' => 'Email atau No. HP wajib diisi',
            ])->onlyInput('identifier');
        }

        // Tentukan apakah email atau phone
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
            $value = strtolower($identifier);
        } else {
            $field = 'phone';
            // Normalisasi ke format kanonik yang disimpan di DB: 8xxxxxxxxxx
            $value = preg_replace('/[\s\-\.]/', '', $identifier);
            $value = preg_replace('/^(\+?62|0)/', '', $value);
        }

        if (Auth::attempt([$field => $value, 'password' => $password], $request->boolean('remember'))) {
            // Gating akun nonaktif: kredensial boleh benar, tapi akun yang
            // sudah dinonaktifkan admin tidak boleh masuk. Logout segera
            // supaya tidak ada sesi yang terbentuk untuk akun nonaktif.
            if (! Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'identifier' => 'Akun kamu sedang dinonaktifkan. Silakan hubungi admin.',
                ])->onlyInput('identifier');
            }

            $request->session()->regenerate();

            return $this->redirectByRole(Auth::user()->role);
        }

        return back()->withErrors([
            'identifier' => 'Email/No. HP atau password yang kamu masukkan salah.',
        ])->onlyInput('identifier');
    }

    /**
     * Logout handler
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/'); // Redirect ke splash/home
    }

    /**
     * Redirect ke dashboard sesuai role. Tetap menghormati intended URL
     * (mis. user diarahkan ke login saat akses halaman terproteksi),
     * dengan fallback ke dashboard role yang sesuai.
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
