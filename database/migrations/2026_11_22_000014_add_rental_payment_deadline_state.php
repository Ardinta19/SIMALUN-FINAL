<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->string('cancel_code', 30)->nullable();
            $table->boolean('payment_review_hold')->default(false);
            $table->boolean('payment_review_resolved')->default(false);
            $table->timestamp('deposit_forfeited_at')->nullable();
            $table->timestamp('payment_late_resumed_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('rental_order_units', fn (Blueprint $table) => $table->dropColumn(['cancel_code', 'payment_review_hold', 'payment_review_resolved', 'deposit_forfeited_at', 'payment_late_resumed_at']));
    }
};
