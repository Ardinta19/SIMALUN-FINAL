<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks `starts_on` untuk jadwal bulanan admin, pengingat H-1, dan
 * pemeriksaan tenggat penjadwal.
 *
 * Migrasi 000024 menambahkan indeks `event_on` dan `ends_on`, tetapi
 * `starts_on` — kolom yang paling sering disaring (rentang bulan pada jadwal,
 * pengingat pelunasan, unit yang tanggal acaranya sudah lewat) — belum.
 * Saringan tanggal kini memakai perbandingan biasa, bukan `whereDate()`,
 * sehingga indeks ini benar-benar terpakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_order_units') || Schema::hasIndex('rental_order_units', 'rental_units_starts_on')) {
            return;
        }

        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->index(['starts_on'], 'rental_units_starts_on');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental_order_units') || ! Schema::hasIndex('rental_order_units', 'rental_units_starts_on')) {
            return;
        }

        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropIndex('rental_units_starts_on');
        });
    }
};
