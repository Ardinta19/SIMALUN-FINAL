<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Catatan pengingat per unit. Kunci uniknya yang membuat perintah
        // pengingat aman dijalankan berkali-kali (termasuk tiap lima menit).
        Schema::create('rental_unit_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);
            $table->string('recipient', 20);
            $table->date('sent_on');
            $table->timestamps();
            $table->unique(['rental_order_unit_id', 'kind', 'recipient', 'sent_on'], 'rental_unit_reminders_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rental_unit_reminders');
    }
};
