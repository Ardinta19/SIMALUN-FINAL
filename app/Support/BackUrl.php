<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Smart back-navigation helper.
 *
 * Logika:
 * 1. Cek query param `?back=` (explicit override dari link pengirim)
 * 2. Cek HTTP Referer — hanya jika masih same-host & bukan halaman sendiri
 * 3. Fallback ke route yang diberikan
 *
 * Cara pakai di view:
 *   <x-back-button fallback="customer.orders" />
 *   atau manual: BackUrl::resolve($request, 'customer.orders')
 */
class BackUrl
{
    /**
     * Resolve URL tujuan tombol "Kembali".
     */
    public static function resolve(Request $request, string $fallbackRoute, array $fallbackParams = []): string
    {
        // 1. Explicit ?back= param
        $back = $request->query('back');
        if (is_string($back) && static::isSafeUrl($back, $request)) {
            return $back;
        }

        // 2. HTTP Referer (same-host, bukan current URL)
        $referer = $request->headers->get('referer');
        if ($referer && static::isSafeUrl($referer, $request) && ! static::isSameAs($referer, $request->fullUrl())) {
            return $referer;
        }

        // 3. Fallback ke named route
        try {
            return route($fallbackRoute, $fallbackParams);
        } catch (\Exception $e) {
            return url('/dashboard');
        }
    }

    /**
     * Generate query string `?back=current_url` untuk ditempel di link.
     * Berguna saat navigasi dari notifikasi/halaman lain.
     */
    public static function param(): string
    {
        return 'back='.urlencode(url()->current());
    }

    /**
     * Cek apakah URL aman (same-host).
     */
    protected static function isSafeUrl(string $url, Request $request): bool
    {
        // Browser dapat menafsirkan backslash dan karakter kontrol secara
        // berbeda dari parse_url. Jangan dekode ulang query yang sudah dibaca Laravel.
        if ($url === '' || str_contains($url, '\\') || preg_match('/[\x00-\x20\x7f]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])) {
            return false;
        }
        $scheme = strtolower($parsed['scheme']);
        if (! in_array($scheme, ['http', 'https'], true) || $scheme !== $request->getScheme()) {
            return false;
        }
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

        return strtolower($parsed['host']) === strtolower($request->getHost())
            && $port === $request->getPort();
    }

    /**
     * Cek apakah dua URL mengarah ke halaman yang sama (ignore query string).
     */
    protected static function isSameAs(string $url1, string $url2): bool
    {
        return strtok($url1, '?') === strtok($url2, '?');
    }
}
