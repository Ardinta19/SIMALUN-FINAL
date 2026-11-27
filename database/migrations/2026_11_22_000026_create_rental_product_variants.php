<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Varian papan: warna (atau tipe) yang bisa dipilih pelanggan.
 *
 * - `color`      : kode heksa `#rrggbb` untuk cincin swatch di katalog.
 *   Disimpan sebagai heksa, bukan nama kelas CSS, karena warna dipilih admin
 *   lewat pemilih warna bawaan peramban; nilainya divalidasi ulang di server.
 * - `image_path` : foto contoh varian (opsional). Bila kosong, katalog tetap
 *   menampilkan foto papan dan hanya cincin warnanya yang berganti.
 * - `stock`      : stok dihitung PER VARIAN. Papan tanpa varian tetap memakai
 *   `rental_products.stock` seperti sebelumnya, jadi semua produk lama tidak
 *   berubah perilakunya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_product_id')->constrained('rental_products')->cascadeOnDelete();
            $table->string('name', 60);
            // Tujuh karakter: # + enam digit heksa. Validasi di controller.
            $table->string('color', 7)->default('#85092d');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['rental_product_id', 'name']);
            $table->index(['rental_product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_product_variants');
    }
};
