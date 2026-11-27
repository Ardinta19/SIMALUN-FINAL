<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjadwal adalah satu-satunya yang membatalkan unit kedaluwarsa, menghanguskan
 * DP, mengirim pengingat, dan menutup unit yang menggantung. Bila satu tugas
 * kehilangan `withoutOverlapping()` atau batas waktunya, kegagalan proses bisa
 * membuat tugas berhenti berjalan berhari-hari tanpa gejala di halaman mana pun.
 */
class ScheduleGuardTest extends TestCase
{
    private function sumber(): string
    {
        return File::get(base_path('routes/console.php'));
    }

    public function test_empat_tugas_terjadwal_dengan_pengaman_overlap(): void
    {
        $baris = [];

        foreach (explode("\n", $this->sumber()) as $isi) {
            if (str_contains($isi, 'Schedule::command(')) {
                $baris[] = trim($isi);
            }
        }

        $this->assertCount(4, $baris, 'Jumlah tugas terjadwal berubah tanpa sengaja.');

        foreach ($baris as $isi) {
            $this->assertMatchesRegularExpression(
                '/withoutOverlapping\(\d+\)/',
                $isi,
                'Tugas terjadwal harus memakai withoutOverlapping(menit): '.$isi
            );
            $this->assertStringContainsString('onOneServer()', $isi, 'Tugas terjadwal harus onOneServer(): '.$isi);
        }
    }

    public function test_setiap_perintah_terjadwal_benar_benar_didefinisikan(): void
    {
        $sumber = $this->sumber();

        preg_match_all("/Artisan::command\\('rental:([a-z-]+)'/", $sumber, $definisi);
        preg_match_all("/Schedule::command\\('rental:([a-z-]+)'/", $sumber, $jadwal);

        $this->assertSame(
            $definisi[1],
            $jadwal[1],
            'Nama perintah yang dijadwalkan harus sama dengan yang didefinisikan, termasuk urutannya.'
        );
    }
}
