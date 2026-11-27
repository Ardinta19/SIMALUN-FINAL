<?php

namespace App\Http\Middleware;

use App\Support\InputSanitizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Membersihkan kolom teks bebas sebelum validasi.
     *
     * Daftar kolom dan aturannya ada di App\Support\InputSanitizer supaya
     * bisa diuji tanpa memuat aplikasi dan supaya teks biasa tidak ikut
     * terpotong saat tidak memuat tag sama sekali.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value) && in_array($key, InputSanitizer::FIELDS, true)) {
                $value = InputSanitizer::clean($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
