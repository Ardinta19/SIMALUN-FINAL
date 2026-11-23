<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci anti-dobel pencatatan kas.
     *
     * Dulu kombinasi yang dijaga adalah (order_id, entry_type, source_type)
     * karena sumber pemasukan utamanya pesanan laundry. Setelah modul laundry
     * dicabut, sumber pencatatan bergeser ke pembayaran & pengembalian sewa,
     * jadi kuncinya memakai pasangan sumber yang sebenarnya:
     * (source_type, source_id, entry_type).
     *
     * Catatan manual tidak punya source_id (NULL), dan NULL tidak dianggap
     * duplikat oleh unique index — beberapa catatan manual tetap boleh ada.
     */
    public function up(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->unique(
                ['source_type', 'source_id', 'entry_type'],
                'uniq_finance_source_type_entry'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->dropUnique('uniq_finance_source_type_entry');
        });
    }
};
