<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('rental_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('size', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('base_price');
            $table->enum('price_unit', ['day', 'event']);
            $table->unsignedInteger('stock')->default(1);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_products');
        Schema::dropIfExists('rental_categories');
    }
};
