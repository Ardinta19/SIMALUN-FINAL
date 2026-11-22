<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_payment_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_payment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('old_amount');
            $table->unsignedInteger('new_amount');
            $table->string('reason', 500);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rental_payment_corrections'); }
};
