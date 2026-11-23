<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Keputusan admin memulai pengerjaan tanpa persetujuan pelanggan setelah
        // tenggat respons desain lewat. Disimpan per versi preview supaya preview
        // baru otomatis membatalkan keputusan lama.
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->timestamp('design_override_at')->nullable();
            $table->foreignId('design_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('design_override_version')->nullable();
            $table->text('design_override_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_order_units', function (Blueprint $table) {
            $table->dropForeign(['design_override_by']);
            $table->dropColumn(['design_override_at', 'design_override_by', 'design_override_version', 'design_override_note']);
        });
    }
};
