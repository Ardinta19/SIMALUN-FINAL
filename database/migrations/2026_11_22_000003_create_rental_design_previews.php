<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_design_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('image_path');
            $table->text('admin_note')->nullable();
            $table->enum('status', ['pending', 'revision', 'approved', 'superseded'])->default('pending');
            $table->text('customer_note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['rental_order_unit_id', 'version']);
        });
    }
    public function down(): void { Schema::dropIfExists('rental_design_previews'); }
};
