<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voucher untuk alur sewa papan bunga.
 *
 * Tabel `vouchers` sudah ada sejak modul laundry dan bentuknya masih cocok
 * (persen/nominal, minimum belanja, batas potongan, kuota, masa berlaku).
 * Migrasi ini menambah dua aturan yang dapat diatur admin saat membuat
 * voucher, lalu menyimpan potongan pada pesanan dan tiap unit sewa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // 'rental'           = potongan hanya untuk harga sewa
            // 'rental_transport' = potongan juga menutup ongkos antar/jemput
            $table->string('scope', 20)->default('rental');
            // null = pelanggan yang sama boleh memakai berkali-kali.
            // 1    = sekali per pelanggan (bawaan).
            $table->unsignedSmallInteger('per_customer_limit')->nullable()->default(1);
        });

        Schema::table('rental_orders', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('voucher_code', 40)->nullable();
            $table->unsignedInteger('discount_amount')->default(0);
        });

        Schema::table('rental_order_units', function (Blueprint $table) {
            // Potongan dibagi rata per unit agar tagihan, pembayaran, dan
            // pengembalian tetap dihitung per unit seperti sebelumnya.
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('transport_discount_amount')->default(0);
        });

        Schema::create('rental_voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('discount_amount')->default(0);
            $table->timestamps();

            // Satu pesanan sewa hanya boleh memakai satu kode yang sama sekali.
            $table->unique(['voucher_id', 'rental_order_id']);
            $table->index(['voucher_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_voucher_usages');

        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'transport_discount_amount']);
        });

        Schema::table('rental_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['voucher_code', 'discount_amount']);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['scope', 'per_customer_limit']);
        });
    }
};
