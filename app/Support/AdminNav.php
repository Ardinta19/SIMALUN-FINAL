<?php

namespace App\Support;

/**
 * Satu sumber daftar tujuan panel admin.
 *
 * Dipakai oleh:
 *  - sidebar shell admin (`layouts.component.admin._nav`) sebagai menu bergrup;
 *  - bilah bawah halaman admin mandiri (`layouts.component.admin._navbar_admin`)
 *    yang hanya memuat empat tujuan harian + tombol "Lainnya";
 *  - lembar "Lainnya" berisi seluruh tujuan, supaya tidak ada menu yang hilang
 *    saat layar menyempit.
 *
 * Menambah tujuan cukup di sini, ketiga tampilan ikut menyesuaikan.
 */
class AdminNav
{
    /**
     * Menu bergrup untuk sidebar.
     *
     * Setiap butir: label (nama panjang), short (label pendek untuk bilah
     * bawah), route, match (pola nama rute untuk status aktif), icon.
     *
     * @return array<int, array{label: string, note?: string, items: array<int, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return [
            [
                'label' => 'Operasional',
                'items' => [
                    ['label' => 'Dasbor', 'short' => 'Beranda', 'route' => 'dashboard.admin', 'match' => ['dashboard.admin', 'admin.dashboard'], 'icon' => 'dashboard'],
                    ['label' => 'Pesanan sewa', 'short' => 'Pesanan', 'route' => 'admin.rental.orders', 'match' => ['admin.rental.orders', 'admin.rental.orders.show'], 'icon' => 'orders'],
                    ['label' => 'Jadwal sewa', 'short' => 'Jadwal', 'route' => 'admin.rental.schedule', 'match' => ['admin.rental.schedule', 'admin.rental.schedule.daily-sheet'], 'icon' => 'calendar'],
                ],
            ],
            [
                'label' => 'Katalog & penawaran',
                'items' => [
                    ['label' => 'Katalog papan', 'short' => 'Katalog', 'route' => 'admin.catalog.index', 'match' => ['admin.catalog.*'], 'icon' => 'package'],
                    ['label' => 'Item tambahan', 'short' => 'Tambahan', 'route' => 'admin.rental.addons.index', 'match' => ['admin.rental.addons.*'], 'icon' => 'sparkles'],
                    ['label' => 'Universitas & tarif', 'short' => 'Universitas', 'route' => 'admin.rental.universities.index', 'match' => ['admin.rental.universities.*'], 'icon' => 'location'],
                    ['label' => 'Voucher sewa', 'short' => 'Voucher', 'route' => 'admin.rental.vouchers.index', 'match' => ['admin.rental.vouchers.*'], 'icon' => 'voucher'],
                ],
            ],
            [
                'label' => 'Keuangan',
                'items' => [
                    ['label' => 'Buku kas', 'short' => 'Buku kas', 'route' => 'admin.rental.cashbook.index', 'match' => ['admin.rental.cashbook.*'], 'icon' => 'finance'],
                    ['label' => 'Petunjuk pembayaran', 'short' => 'Pembayaran', 'route' => 'admin.rental.payment-settings.edit', 'match' => ['admin.rental.payment-settings.*'], 'icon' => 'payment'],
                ],
            ],
            [
                'label' => 'Pemantauan',
                'items' => [
                    ['label' => 'Notifikasi', 'short' => 'Notifikasi', 'route' => 'admin.notifications', 'match' => ['admin.notifications', 'admin.notifications.*'], 'icon' => 'notifikasi'],
                    ['label' => 'Jejak audit', 'short' => 'Audit', 'route' => 'admin.audit.index', 'match' => ['admin.audit.*'], 'icon' => 'audit'],
                    ['label' => 'Panduan admin', 'short' => 'Panduan', 'route' => 'admin.help', 'match' => ['admin.help'], 'icon' => 'help'],
                ],
            ],
            [
                'label' => 'Akun',
                'sidebar' => false,
                'items' => [
                    ['label' => 'Profil', 'short' => 'Profil', 'route' => 'admin.profile', 'match' => ['admin.profile'], 'icon' => 'user'],
                ],
            ],
        ];
    }

    /**
     * Daftar datar seluruh tujuan (urutan sama dengan sidebar).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function flat(): array
    {
        $items = [];

        foreach (static::groups() as $group) {
            foreach ($group['items'] as $item) {
                $item['group'] = $group['label'];
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Ambil satu butir berdasarkan label panjangnya.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $label): ?array
    {
        foreach (static::flat() as $item) {
            if ($item['label'] === $label) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Cek apakah sebuah butir sedang aktif untuk permintaan saat ini.
     *
     * @param  array<string, mixed>  $item
     */
    public static function isActive(array $item): bool
    {
        foreach ($item['match'] as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
