<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Nomor pesanan yang bisa dibaca manusia.
 *
 * Sebelumnya `rental_orders.reference` diisi UUID, misalnya
 * `9f1c0b1a-6f4d-4f8e-9a0b-7c2e5d1a3b44`. UUID memang unik, tetapi tidak bisa
 * diucapkan lewat telepon, tidak bisa ditulis di buku catatan, dan di layar
 * hanya terbaca sebagai deretan acak. Admin dan pelanggan sama-sama perlu
 * nomor yang pendek dan rapi.
 *
 * Bentuk yang dipakai:
 *
 *     BK-261127-001
 *     │  │      └── urutan pesanan pada hari itu (001, 002, …)
 *     │  └───────── tanggal pesanan dibuat (YYMMDD, WIB)
 *     └──────────── kode usaha (Berkah Kreatif)
 *
 * Sifatnya:
 *  - unik dan tidak pernah dipakai ulang (dicek ke basis data, bukan hanya
 *    dihitung, supaya celah dari pesanan yang dihapus tidak menabrak);
 *  - pendek (13 karakter) sehingga muat di kolom `reference` (36) dan di layar
 *    ponsel tanpa terpotong;
 *  - pencarian yang sudah ada (`reference LIKE %kata%`) otomatis bisa mencari
 *    potongan seperti `261127` atau `001`.
 */
class OrderReference
{
    /** Kode usaha pada nomor pesanan. */
    public const PREFIX = 'BK';

    /** Contoh format, dipakai pada teks bantuan pencarian di panel. */
    public const CONTOH = 'BK-261127-001';

    /**
     * Buat nomor pesanan berikutnya untuk hari ini.
     *
     * Pemanggil tetap harus siap menerima tabrakan (dua pesanan dibuat pada
     * milidetik yang sama): jumlah baris dibaca tanpa kunci, jadi dua proses
     * bisa memperoleh urutan yang sama. Kontroler mencoba ulang bila basis data
     * menolak karena `reference` unik.
     */
    public static function make(?CarbonImmutable $hari = null): string
    {
        $tanggal = ($hari ?? CarbonImmutable::now('Asia/Jakarta'))->format('ymd');
        $awalan = self::PREFIX.'-'.$tanggal.'-';
        $urutan = (int) DB::table('rental_orders')->where('reference', 'like', $awalan.'%')->count();

        do {
            $urutan++;
            $kode = $awalan.str_pad((string) $urutan, 3, '0', STR_PAD_LEFT);
        } while (DB::table('rental_orders')->where('reference', $kode)->exists());

        return $kode;
    }

    /** Nomor lama (UUID) tetap dikenali supaya layar tidak pecah. */
    public static function isReadable(?string $reference): bool
    {
        return is_string($reference) && preg_match('/^'.self::PREFIX.'-\d{6}-\d{3,}$/', $reference) === 1;
    }

    /**
     * Potongan pendek untuk ditampilkan di ruang sempit: `261127-001`.
     * Bila nomornya masih UUID lama, kembalikan delapan karakter pertama.
     */
    public static function short(?string $reference): string
    {
        if (! is_string($reference) || $reference === '') {
            return '-';
        }

        if (self::isReadable($reference)) {
            return substr($reference, strlen(self::PREFIX) + 1);
        }

        return substr($reference, 0, 8);
    }
}
