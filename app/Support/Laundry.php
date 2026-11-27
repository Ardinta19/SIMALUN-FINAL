<?php

namespace App\Support;

/**
 * Akses data usaha (nama, tagline, alamat, kontak, tautan WhatsApp).
 * Semua nilai diambil dari `config/laundry.php` — satu sumber kebenaran,
 * dipakai halaman publik, email, dan ringkasan pesanan.
 */
class Laundry
{
    public static function name(): string
    {
        return config('laundry.name', 'Berkah Kreatif');
    }

    public static function tagline(): string
    {
        return config('laundry.tagline', 'Papan bunga untuk setiap cerita');
    }

    public static function address(): string
    {
        return config('laundry.address', '');
    }

    public static function googleMaps(): string
    {
        return config('laundry.google_maps', '#');
    }

    public static function phone(): string
    {
        return config('laundry.phone', '');
    }

    public static function phoneDisplay(): string
    {
        return config('laundry.phone_display', '');
    }

    public static function whatsapp(): string
    {
        return config('laundry.whatsapp', '');
    }

    /** Tautan chat WhatsApp toko sudah disediakan WhatsApp::link(). */
}
