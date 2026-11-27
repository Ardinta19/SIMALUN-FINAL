<?php

namespace App\Support;

/**
 * Membersihkan teks masukan tanpa merusak kalimat biasa.
 *
 * Pembersih HTML hanya dijalankan pada nilai yang benar-benar memuat tag.
 * Sebelumnya semua nilai melewati `strip_tags()` dan pembersih atribut,
 * sehingga teks biasa ikut terpotong:
 *
 *  - "Papan Bunga <3 untuk Ibu Sari" menjadi "Papan Bunga" karena `<3 untuk
 *    Ibu Sari` dianggap tag;
 *  - catatan "ongkir=25000" hilang seluruhnya karena `ongkir=` dianggap
 *    atribut `on…=`.
 *
 * Nilai tanpa tag kini dikembalikan apa adanya (hanya byte nol yang dibuang
 * dan spasi tepi yang dipangkas), sedangkan nilai bertag tetap dibersihkan
 * seperti sebelumnya. Escaping output tetap tugas Blade.
 */
class InputSanitizer
{
    /** Kolom formulir yang dibersihkan sebelum validasi. */
    public const FIELDS = [
        'address',
        'address_note',
        'notes',
        'full_address',
        'label',
        'recipient_name',
        'customer_name',
        'name',
        'status_note',
    ];

    public static function clean(string $value): string
    {
        // Byte nol tidak pernah sah di teks dan bisa memotong string di
        // sebagian driver basis data.
        $value = str_replace(chr(0), '', $value);

        if (! self::looksLikeMarkup($value)) {
            return trim($value);
        }

        // Hapus tag skrip beserta isinya lebih dulu agar kode di dalamnya
        // tidak tersisa sebagai teks.
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);

        // Sisipan yang tidak punya padanan teks: iframe, object, embed,
        // link, meta.
        $value = preg_replace('/<(iframe|object|embed|link|meta)\b[^>]*>/is', '', $value);

        // Sisa tag apa pun dibuang bersama seluruh atributnya (termasuk
        // onclick/onerror dan href="javascript:…"), teks di dalamnya
        // dipertahankan. Tidak perlu pembersih atribut terpisah: seluruh
        // tag memang sudah hilang di langkah ini.
        $value = strip_tags($value);

        // Rapikan spasi berlebih yang tertinggal dari tag yang dibuang.
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * Tag HTML yang dikenal browser. Pemeriksaan ini sengaja memakai daftar
     * tag, bukan "<" diikuti huruf apa pun: "Papan Bunga <3" dan nama
     * "Kakak <Budi>" bukan markup, sedangkan "<div onclick=…>" jelas markup.
     * Teks lain tetap aman karena semua keluaran di-escape Blade.
     */
    private const MARKUP_TAGS = 'a|abbr|b|blockquote|br|code|div|em|font|h[1-6]|hr|i|iframe|img|input|li|link|marquee|meta|object|ol|p|pre|script|small|span|strike|strong|style|sub|sup|svg|table|tbody|td|th|thead|tr|u|ul';

    public static function looksLikeMarkup(string $value): bool
    {
        return (bool) preg_match('/<\s*\/?\s*(?:'.self::MARKUP_TAGS.')\b/i', $value);
    }
}
