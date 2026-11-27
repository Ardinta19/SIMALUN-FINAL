<?php

namespace App\Support;

/**
 * Label bahasa Indonesia untuk nilai mentah di basis data.
 *
 * Sebelum ini peta status dan progres ditulis ulang di tujuh view dengan
 * ejaan yang sedikit berbeda ("Preview" di satu tempat, "Pratinjau" di
 * tempat lain), dan notifikasi otomatis menampilkan nilai mentah seperti
 * "masih berstatus "waiting"". Satu sumber di sini menjaga semuanya sama.
 */
class RentalLabels
{
    /** Status unit pesanan sewa. */
    public const UNIT_STATUS = [
        'pending' => 'Menunggu konfirmasi',
        'confirmed' => 'Dikonfirmasi',
        'cancelled' => 'Dibatalkan',
        'seller_cancelled' => 'Dibatalkan karena penjual',
        'payment_pending' => 'Bukti menunggu pemeriksaan',
        'design_pending' => 'Pratinjau menunggu respons',
        'design_initial' => 'Perlu pratinjau awal',
        'design_revision' => 'Revisi desain diminta',
        'transport_quote' => 'Perlu penawaran ongkos',
        'transport_approval' => 'Ongkos menunggu persetujuan',
        'review' => 'Peninjauan pembayaran',
    ];

    /** Subset status yang boleh dipilih pelanggan (lihat RentalOrderController). */
    public const CUSTOMER_STATUS = [
        'pending' => 'Menunggu konfirmasi',
        'confirmed' => 'Dikonfirmasi',
        'cancelled' => 'Dibatalkan',
    ];

    /** Subset status untuk papan jadwal (lihat RentalScheduleController). */
    public const SCHEDULE_STATUS = [
        'pending' => 'Menunggu konfirmasi',
        'confirmed' => 'Dikonfirmasi',
    ];

    /** Progres pengerjaan unit. */
    public const PROGRESS = [
        'waiting' => 'Menunggu pengerjaan',
        'production' => 'Dalam pengerjaan',
        'ready' => 'Siap diserahkan',
        'handed_over' => 'Sudah diserahkan',
        'returned' => 'Sudah dikembalikan',
    ];

    /**
     * Kalimat penjelas untuk tiap progres.
     *
     * Dipisah dari label supaya notifikasi bisa menjawab "lalu saya harus apa?"
     * tanpa admin menulis pesan manual — inilah yang membuat pelanggan tidak
     * perlu membuka WhatsApp hanya untuk menanyakan status.
     */
    public const PROGRESS_HINT = [
        'waiting' => 'Tidak ada yang perlu kamu lakukan. Admin menyiapkan bahan dan pratinjau desain.',
        'production' => 'Papan sedang dikerjakan. Kami kabari lagi begitu siap diserahkan.',
        'ready' => 'Papan sudah siap. Serah terima menunggu jadwal dan pembayaran yang lunas.',
        'handed_over' => 'Papan sudah kamu terima. Pengembalian mengikuti tanggal yang disepakati.',
        'returned' => 'Unit sudah kembali ke kami. Terima kasih sudah memakai Berkah Kreatif.',
    ];

    /** Urutan progres yang dilihat pelanggan, dari awal sampai selesai. */
    public const PROGRESS_STEPS = ['waiting', 'production', 'ready', 'handed_over', 'returned'];

    /** Status bukti pembayaran. */
    public const PAYMENT_STATUS = [
        'pending' => 'Menunggu verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];

    /** Status pratinjau desain. */
    public const DESIGN_STATUS = [
        'pending' => 'Menunggu respons',
        'revision' => 'Revisi diminta',
        'approved' => 'Disetujui',
        'superseded' => 'Diganti versi baru',
    ];

    public static function unitStatus(?string $value): string
    {
        return self::label(self::UNIT_STATUS, $value);
    }

    public static function progress(?string $value): string
    {
        return self::label(self::PROGRESS, $value);
    }

    public static function progressHint(?string $value): string
    {
        return self::PROGRESS_HINT[$value] ?? '';
    }

    /** Indeks langkah (0-4) untuk stepper; null bila progres tidak dikenal. */
    public static function progressStep(?string $value): ?int
    {
        $index = array_search($value, self::PROGRESS_STEPS, true);

        return $index === false ? null : (int) $index;
    }

    public static function paymentStatus(?string $value): string
    {
        return self::label(self::PAYMENT_STATUS, $value);
    }

    public static function designStatus(?string $value): string
    {
        return self::label(self::DESIGN_STATUS, $value);
    }

    private static function label(array $map, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return $map[$value] ?? $value;
    }
}
