<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul laundry (pesanan kain, kasir walk-in, kurir, laporan, kategori,
 * layanan, rating) sudah dicabut total dari aplikasi. Migrasi asalnya ikut
 * dihapus, jadi database lama perlu dibersihkan sekali di sini supaya tidak
 * menyisakan tabel yatim —
 *
 *   order_ratings, reports, order_status_histories, driver_assignments,
 *   order_items, orders, services, service_categories
 *
 * berikut kolom penghubungnya di tabel yang masih dipakai modul sewa
 * (`finance_entries.order_id`, `voucher_usages.order_id`) dan kolom
 * round-robin kurir di `users`.
 *
 * Idempotent: semua langkah dicek dulu, jadi aman dijalankan di database
 * yang sudah bersih maupun yang masih menyimpan sisa modul lama.
 */
return new class extends Migration
{
    private array $tabelLaundry = [
        'order_ratings',
        'reports',
        'order_status_histories',
        'driver_assignments',
        'order_items',
        'orders',
        'services',
        'service_categories',
    ];

    public function up(): void
    {
        // Lepas foreign key dulu supaya drop tabel tidak diblokir MySQL.
        Schema::disableForeignKeyConstraints();

        foreach ($this->tabelLaundry as $tabel) {
            Schema::dropIfExists($tabel);
        }

        Schema::enableForeignKeyConstraints();

        $this->dropKolom('finance_entries', ['order_id']);
        $this->dropKolom('voucher_usages', ['order_id']);
        $this->dropKolom('users', ['last_assigned_at', 'zona_driver']);
    }

    public function down(): void
    {
        // Modul sudah dihapus permanen; tidak ada yang perlu dipulihkan.
    }

    private function dropKolom(string $tabel, array $kolom): void
    {
        if (! Schema::hasTable($tabel)) {
            return;
        }

        $adaKolom = array_values(array_filter($kolom, fn ($nama) => Schema::hasColumn($tabel, $nama)));

        if ($adaKolom === []) {
            return;
        }

        Schema::table($tabel, function (Blueprint $blueprint) use ($adaKolom) {
            $blueprint->dropColumn($adaKolom);
        });
    }
};
