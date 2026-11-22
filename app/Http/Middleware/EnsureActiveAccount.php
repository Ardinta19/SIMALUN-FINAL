<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        // Login checks alone do not revoke a session when an account is
        // disabled later. This applies to public and authenticated web routes.
        $user = $request->user();
        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun dinonaktifkan. Silakan hubungi admin.'], 401);
            }
            return redirect()->route('login')->withErrors(['identifier' => 'Akun kamu sedang dinonaktifkan. Silakan hubungi admin.']);
        }

        return $next($request);
    }
}
