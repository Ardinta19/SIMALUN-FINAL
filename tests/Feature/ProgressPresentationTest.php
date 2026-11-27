<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Putaran 42 — rumah progres pelanggan.
 *
 * Stepper lima langkah ("Menunggu pengerjaan" … "Sudah dikembalikan") pindah ke
 * halaman pesanan: detail satu pesanan dan halaman satu unit. Beranda pelanggan
 * hanya memuat ringkasan satu baris supaya kartunya tidak menjulang di ponsel.
 *
 * Tes ini membaca berkas view langsung sehingga tetap berjalan tanpa basis data
 * (di sandbox tes Eloquent dilewati).
 */
class ProgressPresentationTest extends TestCase
{
    private function isiView(string $path): string
    {
        $full = base_path('resources/views/'.$path);
        $this->assertFileExists($full, "View {$path} hilang.");

        return (string) file_get_contents($full);
    }

    public function test_stepper_lengkap_ada_di_halaman_pesanan_dan_bukan_di_beranda(): void
    {
        $detail = $this->isiView('rental/show.blade.php');
        $this->assertStringContainsString("rental._progress_steps", $detail);
        $this->assertStringNotContainsString("'compact' => true", $detail, 'Detail pesanan harus memakai stepper lengkap.');

        $beranda = $this->isiView('roles/customer/dashboard.blade.php');
        $this->assertStringNotContainsString("rental._progress_steps", $beranda, 'Beranda tidak boleh lagi memuat stepper lima lingkaran.');
        $this->assertStringContainsString("rental._progress_bar", $beranda);
    }

    public function test_halaman_unit_tetap_memakai_stepper_lengkap(): void
    {
        $unit = $this->isiView('rental/unit.blade.php');
        $this->assertStringContainsString("rental._progress_steps", $unit);
        $this->assertStringNotContainsString("'compact' => true", $unit);
    }

    public function test_ringkasan_tahap_menyebut_nomor_dan_total(): void
    {
        $partial = $this->isiView('rental/_progress_bar.blade.php');

        $this->assertStringContainsString('PROGRESS_STEPS', $partial);
        $this->assertStringContainsString('Tahap', $partial);
        $this->assertStringContainsString('bk-stage__track', $partial);
        $this->assertStringContainsString('Dibatalkan', $partial);
    }

    public function test_jumlah_segmen_ringkasan_sepakat_dengan_daftar_langkah(): void
    {
        // `.bk-stage__track` menulis `repeat(5, …)` di CSS. Bila daftar langkah
        // bertambah tanpa mengubah CSS, segmennya akan berdesakan tanpa galat.
        $this->assertCount(5, \App\Support\RentalLabels::PROGRESS_STEPS);

        $css = (string) file_get_contents(base_path('public/css/berkah-experience.css'));
        $this->assertStringContainsString('repeat(5, minmax(0, 1fr))', $css);
    }

    public function test_stepper_layar_sempit_tidak_pecah_menjadi_dua_baris(): void
    {
        $css = (string) file_get_contents(base_path('public/css/berkah-experience.css'));

        // Blok media 520 px tidak boleh lagi memaksa tiga kolom, karena langkah
        // keempat dan kelima turun ke baris kedua dan garisnya tampak menyilang.
        $awal = strpos($css, '@media (max-width: 520px)');
        $this->assertNotFalse($awal, 'Blok media stepper 520 px hilang.');

        $blok = substr($css, $awal, 600);
        $this->assertStringNotContainsString('repeat(3', $blok);

        // Lima segmen ringkasan tahap selalu satu baris.
        $this->assertStringContainsString('.bk-stage__track', $css);
        $this->assertStringContainsString('repeat(5, minmax(0, 1fr))', $css);
    }
}
