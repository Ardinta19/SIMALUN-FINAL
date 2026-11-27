<?php

namespace App\Support;

use Throwable;

/**
 * Angka kecil di navigasi (notifikasi belum dibaca, isi keranjang).
 *
 *  Halaman pelanggan memuat dua navigasi (atas di `catalog.customer-navigation`
 *  dan bawah di `_navbar_customer`), dan keduanya menghitung angka yang sama.
 *  Tanpa penampung di sini, setiap halaman menjalankan kueri notifikasi dua
 *  kali. Hasilnya disimpan selama satu permintaan.
 */
class NavCounts
{
    private static ?int $unreadNotifications = null;

    public static function unreadNotifications(): int
    {
        if (self::$unreadNotifications !== null) {
            return self::$unreadNotifications;
        }

        try {
            self::$unreadNotifications = (int) (auth()->user()?->unreadNotifications()->count() ?? 0);
        } catch (Throwable) {
            // Kesalahan basis data tidak boleh membuat seluruh halaman gagal
            // hanya karena lencana notifikasi.
            self::$unreadNotifications = 0;
        }

        return self::$unreadNotifications;
    }

    public static function cartItems(): int
    {
        $cart = session('rental_cart', []);

        return is_array($cart) ? count($cart) : 0;
    }
}
