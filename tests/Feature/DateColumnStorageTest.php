<?php

namespace Tests\Feature;

use App\Casts\DateOnly;
use App\Models\FinanceEntry;
use App\Models\RentalOrderUnit;
use App\Models\RentalUnitReminder;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kolom DATE harus disimpan sebagai tanggal, bukan tanggal-jam.
 *
 * Cast bawaan `date` menulis lewat `$dateFormat` model (`Y-m-d H:i:s`).
 * Di MySQL kolom DATE memotong bagian jam, tetapi di SQLite (basis data
 * tes) nilainya tersimpan `2026-11-30 00:00:00`. Akibatnya
 * `where('entry_date', '<=', '2026-11-30')` melewatkan hari terakhir,
 * filter tanggal admin, lembar harian, buku kas periode harian,
 * `rental:expire-payments`, dan `rental:remind` mengembalikan hasil kosong
 * — hanya di mesin pengujian. Cast App\Casts\DateOnly menutup selisih itu.
 */
class DateColumnStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_date_disimpan_sebagai_tanggal_tanpa_jam(): void
    {
        $entry = FinanceEntry::create([
            'entry_date' => '2026-11-30',
            'period_key' => '2026-11',
            'entry_type' => 'income',
            'category' => 'Modal',
            'amount' => 12500,
            'source_type' => 'manual',
        ]);

        $this->assertSame(
            '2026-11-30',
            (string) DB::table('finance_entries')->where('id', $entry->id)->value('entry_date'),
            'Kolom DATE harus tersimpan "Y-m-d" agar perbandingan tanggal konsisten di semua basis data.'
        );

        // Nilai yang di-cast tetap objek tanggal pada awal hari.
        $this->assertSame('2026-11-30', $entry->fresh()->entry_date->toDateString());
        $this->assertSame('00:00:00', $entry->fresh()->entry_date->format('H:i:s'));
    }

    public function test_rentang_dan_kesamaan_tanggal_menyertakan_hari_terakhir(): void
    {
        $buat = fn (string $tanggal, int $nominal) => FinanceEntry::create([
            'entry_date' => $tanggal,
            'period_key' => substr($tanggal, 0, 7),
            'entry_type' => 'income',
            'category' => 'Modal',
            'amount' => $nominal,
            'source_type' => 'manual',
        ]);

        $buat('2026-11-29', 1000);
        $batas = $buat('2026-11-30', 2000);
        $buat('2026-12-01', 3000);

        $dalamRentang = FinanceEntry::where('entry_date', '>=', '2026-11-30')
            ->where('entry_date', '<=', '2026-11-30')
            ->pluck('id')->all();

        $this->assertSame([$batas->id], $dalamRentang, 'Hari terakhir rentang tanggal tidak boleh hilang.');
        $this->assertSame([$batas->id], FinanceEntry::where('entry_date', '2026-11-30')->pluck('id')->all());
        $this->assertCount(2, FinanceEntry::where('entry_date', '<=', '2026-11-30')->get());
    }

    public function test_semua_model_berkolom_date_memakai_cast_date_only(): void
    {
        $harapan = [
            FinanceEntry::class => ['entry_date'],
            Voucher::class => ['valid_from', 'valid_until'],
            RentalOrderUnit::class => ['starts_on', 'event_on', 'ends_on'],
            RentalUnitReminder::class => ['sent_on'],
        ];

        foreach ($harapan as $kelas => $kolom) {
            $casts = (new $kelas)->getCasts();

            foreach ($kolom as $nama) {
                $this->assertSame(DateOnly::class, $casts[$nama] ?? null, $kelas.'::'.$nama.' wajib memakai cast DateOnly.');
            }
        }
    }
}
