<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_payment_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rental_payment_settings'); }
};
