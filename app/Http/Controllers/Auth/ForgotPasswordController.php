<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $credentials = ['email' => $request->email];
        $user = Password::broker()->getUser($credentials);

        if (! $user) {
            $user = User::query()->where('email', $request->email)->first();
        }

        if ($user) {
            $token = Password::broker()->createToken($user);

            // Jangan `new ResetPassword(...)` langsung: kelas bawaan Laravel itu
            // memakai template Inggris. `sendPasswordResetNotification()` pada
            // model User sudah diarahkan ke ResetPasswordNotification milik
            // aplikasi ini — berbahasa Indonesia dan memakai merek toko.
            $user->sendPasswordResetNotification($token);
        }

        return back()->with('status', __('passwords.sent'));
    }
}
