<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Varian yang dipilih pelanggan disimpan pada baris unit pesanan.
 *
 * Tiga kolom, bukan satu, dan semuanya bertahan walau variannya dihapus admin:
 *   · `rental_product_variant_id` — rujukan untuk laporan stok per warna;
 *     `nullOnDelete` supaya pesanan lama tidak ikut hilang.
 *   · `variant_name` / `variant_color` — salinan saat checkout, sama seperti
 *     `product_name` dan `unit_price`. Katalog boleh berganti nama warnanya
 *     tanpa menulis ulang riwayat pesanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->foreignId('rental_product_variant_id')->nullable()
                ->after('rental_product_id')
                ->constrained('rental_product_variants')->nullOnDelete();
            $table->string('variant_name', 60)->nullable()->after('product_name');
            $table->string('variant_color', 7)->nullable()->after('variant_name');
        });
    }

    public function down(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_product_variant_id');
            $table->dropColumn(['variant_name', 'variant_color']);
        });
    }
};
