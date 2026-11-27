<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kosongkan riwayat pesanan sewa untuk memulai UAT dari nol.
 *
 * Kenapa perintah, bukan tombol di panel:
 *
 *  - Satu tombol "hapus semua" di panel admin terlalu mudah tersentuh. Di sini
 *    ada `--dry-run` untuk melihat dampaknya lebih dulu, konfirmasi interaktif,
 *    dan `--force` yang wajib di produksi.
 *  - Yang dihapus hanya **data pesanan**. Katalog (kategori, papan, warna,
 *    paket, item tambahan), voucher, universitas, pengaturan pembayaran, dan
 *    akun pengguna tidak disentuh — supaya setelah dikosongkan, aplikasi
 *    langsung bisa dipakai lagi tanpa menyiapkan ulang katalog.
 *  - `migrate:fresh` tidak dipakai: perintah itu menghapus seluruh basis data
 *    termasuk katalog dan akun, dan tidak boleh dijalankan pada basis data
 *    berisi data (lihat docs/PENJADWAL-SEWA.md).
 *
 * Urutan hapus mengikuti foreign key: anak dulu, induk belakangan. Beberapa
 * tabel memakai `restrictOnDelete` (pembayaran, desain, riwayat), jadi urutan
 * ini bukan pilihan gaya — salah urutan berarti galat SQL.
 */
class PurgeRentalOrders extends Command
{
    protected $signature = 'rental:purge-orders
        {--dry-run : Hitung dampaknya saja, tidak menghapus apa pun}
        {--force : Jalan tanpa pertanyaan konfirmasi (wajib di produksi)}
        {--keep-finance : Pertahankan catatan buku kas yang lahir dari pembayaran sewa}
        {--with-notifications : Ikut hapus notifikasi yang menunjuk ke pesanan sewa}';

    protected $description = 'Hapus seluruh pesanan sewa beserta unit, pembayaran, desain, dan riwayatnya — katalog tetap utuh';

    /**
     * Urutan hapus. Setiap baris: [tabel, keterangan singkat].
     * Keterangan dipakai pada laporan supaya admin tahu apa yang hilang.
     */
    private const LANGKAH = [
        ['rental_payment_corrections', 'koreksi nominal pembayaran'],
        ['rental_payment_refunds', 'pengembalian dana'],
        ['rental_payments', 'bukti pembayaran'],
        ['rental_design_previews', 'versi pratinjau desain'],
        ['rental_unit_progress_events', 'riwayat tahap pengerjaan'],
        ['rental_unit_cancellation_events', 'riwayat pembatalan & pelanjutan'],
        ['rental_product_reviews', 'rating per papan'],
        ['rental_order_reviews', 'rating per pesanan'],
        ['rental_order_unit_addons', 'item tambahan pada pesanan'],
        ['rental_order_references', 'foto referensi pesanan'],
        ['rental_order_units', 'unit pesanan'],
        ['rental_voucher_usages', 'catatan pemakaian voucher'],
        ['rental_orders', 'pesanan'],
    ];

    /**
     * Aturan penjagaan produksi — fungsi murni supaya bisa diuji tanpa mengubah
     * `APP_ENV` saat tes berjalan (mengubah env di tengah tes pernah membuat
     * perintah lain ikut berperilaku produksi).
     */
    public static function wajibForce(bool $produksi, bool $force): bool
    {
        return $produksi && ! $force;
    }

    public function handle(): int
    {
        if (self::wajibForce($this->getLaravel()->isProduction(), (bool) $this->option('force'))) {
            $this->error('Basis data produksi: jalankan dengan --force setelah yakin, dan cadangkan basis data lebih dulu.');

            return self::FAILURE;
        }

        $jumlah = $this->hitung();
        $total = array_sum($jumlah);

        if ($total === 0) {
            $this->info('Tidak ada pesanan sewa. Tidak ada yang perlu dihapus.');

            return self::SUCCESS;
        }

        $this->table(['Tabel', 'Isi', 'Baris'], collect(self::LANGKAH)
            ->map(fn (array $baris) => [$baris[0], $baris[1], number_format($jumlah[$baris[0]] ?? 0, 0, ',', '.')])
            ->push(['finance_entries', 'catatan buku kas dari sewa', number_format($jumlah['finance_entries'] ?? 0, 0, ',', '.')])
            ->all());

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('Mode uji: tidak ada yang dihapus. Jalankan tanpa --dry-run untuk menghapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm('Hapus SEMUA pesanan sewa di atas? Tindakan ini tidak bisa dibatalkan.')) {
            $this->warn('Dibatalkan. Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        $terhapus = DB::transaction(function () use ($jumlah) {
            $riwayat = [];
            foreach (self::LANGKAH as [$tabel, $keterangan]) {
                $riwayat[$tabel] = DB::table($tabel)->delete();
            }

            if (! $this->option('keep-finance')) {
                $riwayat['finance_entries'] = DB::table('finance_entries')
                    ->whereIn('source_type', ['rental_payment', 'rental_refund'])
                    ->delete();
            }

            if ($this->option('with-notifications')) {
                $riwayat['notifications'] = DB::table('notifications')
                    ->where(fn ($query) => $query->where('data', 'like', '%/sewa/%')->orWhere('data', 'like', '%rental%'))
                    ->delete();
            }

            return $riwayat;
        });

        $this->newLine();
        foreach ($terhapus as $tabel => $baris) {
            $this->line(sprintf('  %-34s %s baris', $tabel, number_format($baris, 0, ',', '.')));
        }

        $this->newLine();
        $this->info('Pesanan sewa dikosongkan. Katalog, voucher, universitas, dan akun tidak berubah.');
        $this->line('Langkah berikutnya: buka /admin/sewa untuk memastikan daftar kosong, lalu mulai pesanan uji dari katalog.');

        return self::SUCCESS;
    }

    /**
     * Hitung isi tiap tabel sebelum menghapus, supaya laporan dan konfirmasi
     * menyebut angka yang sebenarnya — bukan perkiraan.
     *
     * @return array<string, int>
     */
    private function hitung(): array
    {
        $jumlah = [];
        foreach (self::LANGKAH as [$tabel]) {
            $jumlah[$tabel] = (int) DB::table($tabel)->count();
        }
        $jumlah['finance_entries'] = (int) DB::table('finance_entries')
            ->whereIn('source_type', ['rental_payment', 'rental_refund'])
            ->count();

        return $jumlah;
    }
}
