<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // Unique index lama menyebut kolom order_id; di SQLite (dan MySQL
        // strict) kolom itu tidak bisa dibuang selama masih dirujuk index.
        $this->dropIndex('finance_entries', 'uniq_finance_order_type_source');

        // Lepas foreign key dulu supaya drop tabel tidak diblokir MySQL.
        Schema::disableForeignKeyConstraints();

        foreach ($this->tabelLaundry as $tabel) {
            Schema::dropIfExists($tabel);
        }

        Schema::enableForeignKeyConstraints();

        $this->dropKolom('finance_entries', ['order_id']);
        $this->dropKolom('voucher_usages', ['order_id']);
        $this->dropKolom('users', ['last_assigned_at', 'zona_driver']);

        // Database lama belum punya kunci anti-dobel berbasis sumber sewa.
        if (Schema::hasTable('finance_entries') && ! $this->indexAda('finance_entries', 'uniq_finance_source_type_entry')) {
            try {
                Schema::table('finance_entries', function (Blueprint $table) {
                    $table->unique(['source_type', 'source_id', 'entry_type'], 'uniq_finance_source_type_entry');
                });
            } catch (\Throwable $e) {
                // Ada baris lama yang sudah duplikat: jangan gagalkan migrasi,
                // catat supaya bisa dirapikan manual lewat buku kas.
                Log::warning('Index uniq_finance_source_type_entry tidak dibuat: '.$e->getMessage());
            }
        }
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

    private function dropIndex(string $tabel, string $index): void
    {
        if (! $this->indexAda($tabel, $index)) {
            return;
        }

        Schema::table($tabel, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropUnique($index);
        });
    }

    /**
     * Cek keberadaan index lintas driver tanpa bergantung versi Schema builder.
     */
    private function indexAda(string $tabel, string $index): bool
    {
        if (! Schema::hasTable($tabel)) {
            return false;
        }

        $driver = Schema::getConnection()->getDriverName();

        $hasil = match ($driver) {
            'sqlite' => DB::selectOne(
                "select name from sqlite_master where type = 'index' and tbl_name = ? and name = ?",
                [$tabel, $index]
            ),
            'mysql', 'mariadb' => DB::selectOne(
                'select index_name from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
                [$tabel, $index]
            ),
            'pgsql' => DB::selectOne(
                'select indexname from pg_indexes where tablename = ? and indexname = ? limit 1',
                [$tabel, $index]
            ),
            default => null,
        };

        return $hasil !== null;
    }
};
