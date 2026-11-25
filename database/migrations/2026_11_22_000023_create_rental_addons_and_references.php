<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item tambahan (add-on) dan foto referensi desain.
 *
 * - `rental_addons`         : daftar yang diisi admin (nama, foto, harga, aktif).
 * - `rental_order_unit_addons` : pilihan pelanggan per unit, menyimpan nama dan
 *   harga saat checkout supaya perubahan katalog tidak menulis ulang tagihan lama.
 * - `rental_order_units.addon_amount` : total item tambahan unit, dijumlahkan ke
 *   tagihan (payableAmount) sehingga ikut DP, pelunasan, dan pengingat.
 * - `rental_order_references` : foto contoh desain yang diunggah pelanggan saat
 *   checkout, berlaku untuk satu pesanan (bukan per unit), disimpan di disk privat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('rental_order_unit_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->constrained('rental_order_units')->cascadeOnDelete();
            // Nama dan harga tetap tersimpan walau item tambahan dihapus admin.
            $table->foreignId('rental_addon_id')->nullable()->constrained('rental_addons')->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('total');
            $table->timestamps();
        });

        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->unsignedInteger('addon_amount')->default(0);
        });

        Schema::create('rental_order_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained('rental_orders')->cascadeOnDelete();
            $table->string('image_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_order_references');

        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropColumn('addon_amount');
        });

        Schema::dropIfExists('rental_order_unit_addons');
        Schema::dropIfExists('rental_addons');
    }
};
