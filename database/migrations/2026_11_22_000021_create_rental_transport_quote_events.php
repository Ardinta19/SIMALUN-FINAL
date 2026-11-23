<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Riwayat penawaran ongkos: admin mengirim versi, pelanggan menyetujui
        // atau menolak. Menolak mengosongkan penawaran sehingga admin dapat
        // mengirim versi baru selama belum ada pembayaran sama sekali.
        Schema::create('rental_transport_quote_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_unit_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20);
            $table->unsignedSmallInteger('version');
            $table->unsignedBigInteger('amount')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['rental_order_unit_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_transport_quote_events');
    }
};
