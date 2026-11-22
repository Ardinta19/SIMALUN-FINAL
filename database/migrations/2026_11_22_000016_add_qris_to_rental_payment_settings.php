<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_payment_settings', function (Blueprint $table) {
            $table->string('qris_path')->nullable();
            $table->string('whatsapp_number', 15)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_payment_settings', fn (Blueprint $table) => $table->dropColumn(['qris_path', 'whatsapp_number']));
    }
};
