<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class ResetPasswordController extends Controller
{
    public function showForm(Request $request, string $token)
    {
        return view('auth.forgot-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $pemilik = null;

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use (&$pemilik) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
                $pemilik = $user;
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($pemilik instanceof User) {
                $this->lupakanSesiLain($pemilik);
            }

            return redirect()->route('password.request')
                ->with('password_reset_success', true);
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Kata sandi biasanya direset justru karena akun diduga dibobol. Mengganti
     * kata sandi saja tidak memutus sesi yang sudah terbuka di perangkat lain,
     * jadi baris sesi milik pengguna itu dihapus. `remember_token` sudah
     * dirotasi di dalam transaksi reset, sehingga cookie "ingat saya" lama
     * juga tidak lagi berlaku.
     *
     * Hanya berlaku untuk driver `database`; driver lain (file, array, redis)
     * tidak punya tabel yang bisa disapu dengan cara ini.
     */
    private function lupakanSesiLain(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
