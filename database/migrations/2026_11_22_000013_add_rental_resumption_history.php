<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_order_units', fn (Blueprint $table) => $table->string('status_before_cancel', 20)->nullable());
        Schema::create('rental_unit_cancellation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->constrained()->restrictOnDelete();
            $table->string('action', 20);
            $table->string('previous_status', 20);
            $table->string('new_status', 20);
            $table->text('note');
            $table->string('responsibility', 20)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rental_unit_cancellation_events');
        Schema::table('rental_order_units', fn (Blueprint $table) => $table->dropColumn('status_before_cancel'));
    }
};
