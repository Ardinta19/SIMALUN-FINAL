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
        // Eloquent may not hydrate database defaults on a freshly created
        // model (for example an immediately authenticated registration).
        // Resolve a missing value from the database; never assume it is active.
        $active = $user ? ($user->is_active ?? $user->fresh()?->is_active) : null;
        if ($user && $active !== true) {
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
