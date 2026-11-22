<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_product_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['rental_product_id', 'user_id']);
        });
        Schema::create('rental_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('rental_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
        });
        Schema::create('rental_order_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
        });
        Schema::create('rental_order_like_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamps();
        });
        Schema::create('rental_order_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['rental_order_id', 'user_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rental_order_likes');
        Schema::dropIfExists('rental_order_like_shares');
        Schema::dropIfExists('rental_order_reviews');
        Schema::dropIfExists('rental_product_reviews');
        Schema::dropIfExists('rental_product_likes');
    }
};
