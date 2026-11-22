<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 36)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmation_deadline');
            $table->timestamps();
        });
        Schema::create('rental_order_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('rental_product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('unit_price');
            $table->string('price_unit', 10);
            $table->unsignedInteger('rental_amount');
            $table->date('starts_on');
            $table->date('event_on');
            $table->date('ends_on');
            $table->string('recipient_name', 160);
            $table->text('greeting');
            $table->text('design_notes')->nullable();
            $table->enum('delivery_method', ['delivery', 'pickup']);
            $table->text('delivery_address')->nullable();
            $table->enum('return_method', ['collection', 'dropoff']);
            $table->text('return_address')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('cancel_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['rental_product_id', 'status', 'starts_on', 'ends_on'], 'rental_units_availability');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rental_order_units');
        Schema::dropIfExists('rental_orders');
    }
};
