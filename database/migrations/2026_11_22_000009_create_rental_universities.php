<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_universities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160)->unique();
            $table->unsignedInteger('delivery_fee');
            $table->unsignedInteger('collection_fee');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->string('delivery_university', 160)->nullable();
            $table->unsignedInteger('delivery_university_fee')->nullable();
            $table->string('return_university', 160)->nullable();
            $table->unsignedInteger('return_university_fee')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('rental_order_units', fn (Blueprint $table) => $table->dropColumn(['delivery_university', 'delivery_university_fee', 'return_university', 'return_university_fee']));
        Schema::dropIfExists('rental_universities');
    }
};
