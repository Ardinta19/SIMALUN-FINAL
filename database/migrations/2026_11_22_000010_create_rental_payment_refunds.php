<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_payment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('reason', 500);
            $table->uuid('request_key')->unique();
            $table->timestamp('refunded_at');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('finance_entry_id')->nullable()->unique()->constrained('finance_entries')->restrictOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rental_payment_refunds'); }
};
