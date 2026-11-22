<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->string('progress', 20)->default('waiting');
        });
        Schema::create('rental_unit_progress_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->constrained()->restrictOnDelete();
            $table->string('progress', 20);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rental_unit_progress_events');
        Schema::table('rental_order_units', fn (Blueprint $table) => $table->dropColumn('progress'));
    }
};
