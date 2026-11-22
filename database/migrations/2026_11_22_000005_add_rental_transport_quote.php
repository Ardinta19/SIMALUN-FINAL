<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->unsignedInteger('transport_quote')->nullable();
            $table->unsignedInteger('transport_quote_version')->default(0);
            $table->text('transport_quote_note')->nullable();
            $table->timestamp('transport_approved_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropColumn(['transport_quote', 'transport_quote_version', 'transport_quote_note', 'transport_approved_at']);
        });
    }
};
