<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk kolom yang menjadi saringan antrean admin dan penjadwal.
 *
 * Dasbor admin menghitung antrean dengan `count()` pada setiap kunjungan:
 * unit `pending`, unit `confirmed` yang belum kembali, pembayaran `pending`,
 * unit yang tanggal acaranya lewat, dan seterusnya. Tanpa indeks, tiap
 * hitungan memindai seluruh tabel — tidak terasa saat data masih puluhan
 * baris, tetapi menjadi lambat begitu riwayat pesanan setahun menumpuk.
 *
 * Indeks yang sudah ada (`rental_units_availability`) berawalan
 * `rental_product_id`, sehingga tidak bisa dipakai untuk saringan berbasis
 * status saja.
 */
return new class extends Migration
{
    /** @var array<int, array{0: string, 1: array<int, string>, 2: string}> */
    private array $indeks = [
        ['rental_order_units', ['status', 'progress'], 'rental_units_status_progress'],
        ['rental_order_units', ['event_on'], 'rental_units_event_on'],
        ['rental_order_units', ['ends_on'], 'rental_units_ends_on'],
        ['rental_payments', ['status'], 'rental_payments_status'],
    ];

    public function up(): void
    {
        foreach ($this->indeks as [$tabel, $kolom, $nama]) {
            if (! Schema::hasTable($tabel) || Schema::hasIndex($tabel, $nama)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) use ($kolom, $nama) {
                $table->index($kolom, $nama);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indeks as [$tabel, $kolom, $nama]) {
            if (! Schema::hasTable($tabel) || ! Schema::hasIndex($tabel, $nama)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) use ($nama) {
                $table->dropIndex($nama);
            });
        }
    }
};
