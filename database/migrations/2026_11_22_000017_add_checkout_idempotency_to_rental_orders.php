<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            // Pesanan historis tetap null. Kunci mewakili unit keranjang pelanggan.
            $table->string('checkout_key', 64)->nullable()->unique();
            $table->string('checkout_payload_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->dropUnique(['checkout_key']);
            $table->dropColumn(['checkout_key', 'checkout_payload_hash']);
        });
    }
};
