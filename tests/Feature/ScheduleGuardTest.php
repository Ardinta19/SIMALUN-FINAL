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

    /** @return array<int, string> */
    private function barisJadwal(): array
    {
        $baris = [];
        foreach (explode("\n", $this->sumber()) as $isi) {
            if (str_contains($isi, 'Schedule::command(')) {
                $baris[] = trim($isi);
            }
        }

        return $baris;
    }

    public function test_semua_tugas_terjadwal_punya_pengaman_overlap(): void
    {
        $baris = $this->barisJadwal();

        $this->assertCount(5, $baris, 'Jumlah tugas terjadwal berubah tanpa sengaja.');

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

        // Perintah gaya lama didefinisikan sebagai closure di routes/console.php,
        // sedangkan perintah baru berupa kelas di app/Console/Commands.
        $closure = [];
        preg_match_all("/Artisan::command\('([a-z:-]+)'/", $sumber, $definisi);
        foreach ($definisi[1] as $nama) {
            $closure[] = $nama;
        }

        $kelas = [];
        foreach (glob(app_path('Console/Commands/*.php')) ?: [] as $berkas) {
            $isi = (string) file_get_contents($berkas);
            if (preg_match('/protected \$signature = \'([a-z:-]+)/', $isi, $cocok)) {
                $kelas[] = $cocok[1];
            }
        }

        preg_match_all("/Schedule::command\('([a-z:-]+)'/", $sumber, $jadwal);

        $this->assertNotEmpty($jadwal[1], 'Tidak ada tugas terjadwal yang terbaca.');

        foreach ($jadwal[1] as $nama) {
            $this->assertTrue(
                in_array($nama, $closure, true) || in_array($nama, $kelas, true),
                'Perintah terjadwal tidak didefinisikan: '.$nama
            );
        }
    }
}
