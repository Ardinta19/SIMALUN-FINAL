<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_duration_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_product_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('days');
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->foreignId('rental_duration_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('package_name', 120)->nullable();
            $table->unsignedSmallInteger('package_days')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_duration_package_id');
            $table->dropColumn(['package_name', 'package_days']);
        });
        Schema::dropIfExists('rental_duration_packages');
    }
};
