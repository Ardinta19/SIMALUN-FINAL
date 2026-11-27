<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sheet pilihan (pengembalian, tanggal, daftar opsi) harus bisa digulir dan
 * tidak melempar halaman ke atas saat ditutup.
 *
 * Dua cacat nyata yang dilaporkan pemilik usaha:
 *
 * 1. Isi sheet "tidak bisa di-slide naik/turun". Penyebabnya: penanda
 *    `data-lenis-prevent` hanya dipasang sekali untuk elemen statis saat
 *    Lenis dimuat, sedangkan sheet dibuat belakangan. Tanpa penanda itu,
 *    roda mouse/trackpad tetap menggulirkan halaman di belakangnya.
 * 2. Setelah sheet ditutup, halaman kembali ke paling atas. Penyebabnya:
 *    `.lenis-stopped` (vendor) memasang `overflow: clip` pada <html>,
 *    sementara `scrollTo()` dipanggil sebelum MutationObserver menyalakan
 *    Lenis kembali — permintaan gulirnya diabaikan.
 */
class SheetScrollTest extends TestCase
{
    private function js(): string
    {
        return (string) File::get(public_path('js/berkah-controls.js'));
    }

    private function css(): string
    {
        return (string) File::get(public_path('css/berkah-controls.css'));
    }

    private function fungsi(string $nama): string
    {
        $js = $this->js();
        $mulai = strpos($js, 'function '.$nama);
        $this->assertNotFalse($mulai, 'Fungsi '.$nama.' tidak ditemukan di berkah-controls.js.');

        $berikut = strpos($js, "\n    function ", $mulai + 10);

        return substr($js, $mulai, ($berikut === false ? strlen($js) : $berikut) - $mulai);
    }

    public function test_layer_yang_dibuat_dinamis_ditandai_agar_bisa_digulir(): void
    {
        $js = $this->js();

        $this->assertStringContainsString("panel.setAttribute('data-lenis-prevent', '')", $js);
        $this->assertStringContainsString("body.setAttribute('data-lenis-prevent', '')", $js);
    }

    public function test_isi_sheet_bisa_digulir_sampai_batas_viewport(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression('/\.fc-sheet\s*\{[^}]*max-height:\s*86vh/s', $css, 'Cadangan 86vh untuk WebView lama harus tetap ada.');
        $this->assertMatchesRegularExpression('/\.fc-sheet\s*\{[^}]*max-height:\s*min\(86dvh, 720px\)/s', $css);
        $this->assertMatchesRegularExpression('/\.fc-layer__body\s*\{[^}]*overflow-y:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.fc-layer__body\s*\{[^}]*touch-action:\s*pan-y/s', $css);
        $this->assertMatchesRegularExpression('/\.fc-layer__body\s*\{[^}]*overscroll-behavior:\s*contain/s', $css);
    }

    public function test_menutup_sheet_menyalakan_lenis_sebelum_memulihkan_posisi(): void
    {
        $unlock = $this->fungsi('unlockScroll');

        $nyalakan = strpos($unlock, 'lenis.start()');
        $pulihkan = strpos($unlock, 'lenis.scrollTo(');

        $this->assertNotFalse($nyalakan, 'unlockScroll harus menyalakan Lenis lebih dulu.');
        $this->assertNotFalse($pulihkan, 'unlockScroll harus memulihkan posisi gulir.');
        $this->assertLessThan($pulihkan, $nyalakan, 'Lenis harus dinyalakan sebelum scrollTo(); kalau tidak, gulir diabaikan (.lenis-stopped → overflow: clip).');
        $this->assertStringContainsString('requestAnimationFrame', $unlock, 'Pemulihan posisi perlu diperiksa lagi setelah tinggi dokumen kembali.');
    }

    public function test_fokus_dikembalikan_tanpa_menggeser_halaman(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('focus({ preventScroll: true })', $js);
        $this->assertStringNotContainsString('scrollTo(0, 0)', $js, 'Tidak boleh ada gulir paksa ke paling atas saat sheet ditutup.');
    }
}
