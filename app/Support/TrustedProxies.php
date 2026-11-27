<?php

namespace App\Support;

/**
 * Daftar proxy tepercaya untuk header X-Forwarded-*.
 *
 * Nilai diambil dari `TRUSTED_PROXIES` (dipisah koma). Bawaannya `*`, sama
 * dengan perilaku sebelumnya: hosting seperti Hostinger menerminasi TLS di
 * proxy mereka, jadi tanpa mempercayai proxy, Laravel mengira koneksi masih
 * HTTP (muncul "Mixed Content" atau redirect berulang saat HTTPS dipaksa).
 *
 * Konsekuensinya perlu diketahui: `*` membuat siapa pun yang bisa menghubungi
 * server langsung boleh mengaku sebagai IP apa saja. IP itu dipakai untuk kunci
 * pembatasan percobaan masuk (5 kali per email+IP) dan kolom `ip` catatan
 * audit. Bila server tidak berada di belakang proxy (VPS dengan TLS sendiri),
 * isi `TRUSTED_PROXIES` dengan daftar IP proxy yang sebenarnya — atau
 * `127.0.0.1,::1` — supaya IP klien tidak bisa dipalsukan.
 */
class TrustedProxies
{
    /** @var array<int, string> */
    public const DEFAULT = ['*'];

    /**
     * @return array<int, string>
     */
    public static function parse(?string $raw): array
    {
        $items = array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', (string) $raw)
        ), static fn (string $item): bool => $item !== ''));

        return $items === [] ? self::DEFAULT : $items;
    }
}
