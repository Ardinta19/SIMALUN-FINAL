<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            // Kode dipakai customer saat checkout sewa — wajib unik dan
            // disimpan UPPERCASE supaya pencarian gampang.
            $table->string('code', 30)->unique();
            $table->string('description', 200);
            // 'percent' = potong sekian persen dari subtotal
            // 'fixed'   = potong nominal tetap
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedInteger('value');
            // Minimum subtotal supaya voucher bisa dipakai (0 = tanpa minimum)
            $table->unsignedInteger('min_order')->default(0);
            // Hanya untuk type=percent: cap diskon maksimum (null = tanpa cap)
            $table->unsignedInteger('max_discount')->nullable();
            // null = tanpa batas total pemakaian (untuk semua customer gabungan)
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'valid_until']);
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('discount_amount');
            $table->timestamps();

            $table->index(['voucher_id', 'customer_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('vouchers');
    }
};
