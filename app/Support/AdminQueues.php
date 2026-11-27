<?php

namespace App\Support;

use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;

/**
 * Antrean kerja admin dalam urutan alur, lengkap dengan jumlahnya.
 *
 * Daftar pesanan dulu menampilkan enam pil tanpa urutan dan tanpa angka:
 * "Menunggu konfirmasi", "Bukti pembayaran", "Ongkos", "Desain", "Tinjau
 * manual", "Lewat kembali". Admin tidak tahu mana yang lebih dulu, dan tidak
 * tahu berapa yang menunggu. Kelas ini menjadi satu sumber urutan alur itu.
 *
 * Urutan mengikuti perjalanan pesanan, bukan abjad:
 * konfirmasi → desain → ongkos → pembayaran → serah terima/kembali.
 */
class AdminQueues
{
    /**
     * @return array<int, array{key: string, label: string, note: string, status: string, count: int}>
     */
    public static function counts(): array
    {
        return [
            [
                'key' => 'pending',
                'label' => 'Menunggu konfirmasi',
                'note' => 'Tanggal ditahan sampai admin memutuskan',
                'status' => 'pending',
                'count' => RentalOrderUnit::where('status', 'pending')->count(),
            ],
            [
                'key' => 'design',
                'label' => 'Perlu desain',
                'note' => 'Belum ada pratinjau untuk pelanggan',
                'status' => 'design_initial',
                'count' => RentalOrderUnit::where('status', 'confirmed')
                    ->where('progress', 'waiting')
                    ->whereDoesntHave('previews')
                    ->count(),
            ],
            [
                'key' => 'transport',
                'label' => 'Perlu ongkos',
                'note' => 'Antar atau jemput belum ditawar',
                'status' => 'transport_quote',
                'count' => RentalOrderUnit::whereIn('status', ['pending', 'confirmed'])
                    ->where(fn ($query) => $query->where('delivery_method', 'delivery')->orWhere('return_method', 'collection'))
                    ->whereNull('transport_quote')
                    ->whereNull('transport_approved_at')
                    ->count(),
            ],
            [
                'key' => 'payment',
                'label' => 'Bukti pembayaran',
                'note' => 'Menunggu diperiksa',
                'status' => 'payment_pending',
                'count' => RentalPayment::where('status', 'pending')->count(),
            ],
            [
                'key' => 'review',
                'label' => 'Tinjau manual',
                'note' => 'Pembayaran atau jadwal ditahan',
                'status' => 'review',
                'count' => RentalOrderUnit::needsPaymentReview()->count(),
            ],
            [
                'key' => 'return_overdue',
                'label' => 'Lewat kembali',
                'note' => 'Sudah lewat tanggal, belum ditandai',
                'status' => 'return_overdue',
                'count' => RentalOrderUnit::where('status', 'confirmed')
                    ->where('progress', 'handed_over')
                    ->where('ends_on', '<', now('Asia/Jakarta')->toDateString())
                    ->count(),
            ],
        ];
    }

    /**
     * Semua nilai `?status=` yang diterima pengendali, dengan label manusia.
     *
     * Chip "Filter aktif" dulu hanya mengenal `UNIT_STATUS`, sehingga memilih
     * antrean "Bukti pembayaran" menghasilkan chip "Status: payment_pending" —
     * kunci basis data bocor ke layar.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        // Label tugas lebih dulu: untuk kunci yang sama, nama antrean
        // ("Bukti pembayaran") harus menang atas kata status yang lebih panjang
        // ("Bukti menunggu pemeriksaan") agar chip dan antrean tidak berbeda.
        return [
            'design_initial' => 'Perlu desain',
            'design_revision' => 'Revisi desain',
            'design_pending' => 'Menunggu tinjauan pelanggan',
            'transport_quote' => 'Perlu ongkos',
            'transport_approval' => 'Ongkos menunggu persetujuan',
            'payment_pending' => 'Bukti pembayaran',
            'review' => 'Tinjau manual',
            'return_overdue' => 'Lewat kembali',
            'seller_cancelled' => 'Batal oleh penjual',
            'grace' => 'Masa tenggang DP',
        ] + RentalLabels::UNIT_STATUS;
    }

    /** Label satu nilai filter; nilai asing dikembalikan apa adanya. */
    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    /** True bila nilai filter itu status unit (tiga nilai), bukan tugas. */
    public static function isUnitStatus(string $status): bool
    {
        return in_array($status, RentalLabels::REAL_STATUS, true);
    }

    /** @return int total hal (unit atau pembayaran) yang menunggu tindakan admin */
    public static function total(array $counts): int
    {
        return array_sum(array_column($counts, 'count'));
    }
}
