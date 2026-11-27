<?php

namespace App\Support;

/**
 * Helper WhatsApp untuk skenario "klik tombol → buka chat".
 *
 * Pendekatan ini sengaja tidak pakai API berbayar (Fonnte/Wablas)
 * karena percakapan tetap dipegang admin dan disetujui manual.
 * Tinggal generate URL `wa.me` dengan teks pre-filled saja.
 *
 * Untuk WA toko (customer → admin), gunakan
 * {@see \App\Support\RentalWhatsApp::adminLink()} yang mengisi nomor dari
 * `config('laundry.whatsapp')`.
 * Helper ini dipakai tombol WhatsApp ke nomor pelanggan maupun admin.
 */
class WhatsApp
{
    /**
     * Bangun URL `wa.me` untuk satu nomor + pesan opsional.
     * Mengembalikan null kalau nomor kosong / tidak valid supaya view
     * bisa menyembunyikan tombol tanpa error.
     */
    public static function link(?string $phone, string $message = ''): ?string
    {
        $normalized = static::normalize($phone);
        if (! $normalized) {
            return null;
        }

        $url = "https://wa.me/{$normalized}";

        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    /**
     * Normalisasi nomor HP Indonesia ke format internasional tanpa simbol:
     *   "0823-7888-6053" → "62823788860 53" → "6282378886053"
     *   "+62 823 7888 6053" → "6282378886053"
     *   "8237888..." (tanpa awalan) → tetap pakai 62
     *
     * Kalau panjangnya tidak masuk akal (kurang dari 9 atau lebih dari 15 digit)
     * dianggap tidak valid dan dikembalikan null.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        // 0xxxx → 62xxxx
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        // 8xxxx (tanpa awalan) → 628xxxx
        if (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        if (strlen($digits) < 9 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

}
