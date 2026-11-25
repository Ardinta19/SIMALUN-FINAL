<?php

namespace App\Support;

/**
 * Batas unggah yang benar-benar berlaku di server.
 *
 * Laravel hanya bisa menilai berkas yang berhasil masuk. Bila `php.ini`
 * menetapkan `upload_max_filesize` atau `post_max_size` lebih kecil daripada
 * batas aplikasi (mis. 25 MB per foto), berkas besar tidak pernah sampai ke
 * validasi: PHP membuangnya lebih dulu dan pelanggan hanya melihat halaman
 * galat. Karena itu batas server dibaca dan ditampilkan di halaman checkout.
 */
final class UploadLimit
{
    /**
     * Batas terkecil dari pasangan `upload_max_filesize` dan `post_max_size`,
     * dalam MB. 0 berarti tidak diketahui atau tidak dibatasi.
     */
    public static function effectiveMegabytes(): int
    {
        $limits = array_filter([
            self::toMegabytes((string) ini_get('upload_max_filesize')),
            self::toMegabytes((string) ini_get('post_max_size')),
        ], fn (int $megabytes) => $megabytes > 0);

        return $limits === [] ? 0 : (int) min($limits);
    }

    /**
     * Ubah singkatan `php.ini` ("2M", "512K", "1G", "1048576") menjadi MB.
     * Nilai kosong, "0", atau tak terbaca menghasilkan 0 (tanpa batas).
     */
    public static function toMegabytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^(\d+(?:\.\d+)?)\s*([kmg]?)b?$/i', $value, $matches)) {
            return 0;
        }

        $number = (float) $matches[1];

        return (int) round(match (strtolower($matches[2])) {
            'g' => $number * 1024,
            'm' => $number,
            'k' => $number / 1024,
            default => $number / 1048576,
        });
    }
}
