<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Dasbor admin: uang hari ini, arus tujuh hari, dan jam pemakaian papan.
 *
 * Sebelum putaran ini dasbor hanya menampilkan pemasukan — pengeluaran tidak
 * pernah muncul, sehingga "masuk besar" bisa menyembunyikan "keluar juga
 * besar". Tiga kartu angka (Unit aktif / Menunggu / Tinjau manual) juga
 * mengulang isi "Perlu tindakanmu" tepat di atasnya.
 *
 * Tes ini menjaga isi dan bentuknya tanpa memerlukan basis data: kueri agregat
 * diperiksa dari sumbernya, tampilan diperiksa dari markup.
 */
class AdminDashboardFinanceTest extends TestCase
{
    private function isiView(): string
    {
        return (string) file_get_contents(resource_path('views/roles/admin/dashboard.blade.php'));
    }

    private function isiController(): string
    {
        return (string) file_get_contents(app_path('Http/Controllers/DashboardController.php'));
    }

    public function test_pemasukan_dan_pengeluaran_dihitung_untuk_hari_dan_bulan(): void
    {
        $controller = $this->isiController();

        foreach (['pemasukanHari', 'pengeluaranHari', 'pemasukanBulan', 'pengeluaranBulan'] as $variabel) {
            $this->assertStringContainsString('$'.$variabel.' =', $controller, $variabel.' tidak dihitung.');
        }

        $view = $this->isiView();
        $this->assertStringContainsString('Uang hari ini', $view);
        $this->assertStringContainsString('Pengeluaran', $view);
        $this->assertStringContainsString('Selisih hari ini', $view);
        $this->assertStringContainsString("route('admin.rental.cashbook.index')", $view);
    }

    public function test_arus_tujuh_hari_memakai_satu_kueri_agregat_dan_pembagi_aman(): void
    {
        $controller = $this->isiController();

        // Satu kueri agregat, bukan tujuh kueri per hari.
        $this->assertStringContainsString("selectRaw('entry_date, entry_type, SUM(amount) as total')", $controller);
        $this->assertStringContainsString('->groupBy(', $controller);

        // Pembagi grafik minimal 1: minggu kosong tidak boleh memicu pembagian nol.
        $this->assertStringContainsString('max(1, max(array_map(', $controller);

        // Tujuh titik data, hari ini di ujung.
        $this->assertStringContainsString('for ($mundur = 6; $mundur >= 0; $mundur--)', $controller);
    }

    public function test_grafik_batang_punya_angka_pendamping_dan_label_aksesibilitas(): void
    {
        $view = $this->isiView();

        $this->assertStringContainsString('adm-bars', $view);
        $this->assertStringContainsString('role="img"', $view);
        $this->assertStringContainsString('Grafik batang arus kas tujuh hari', $view);

        // Angka tiap hari tetap tersedia sebagai tabel, bukan hanya batang warna.
        $this->assertStringContainsString('Lihat angka tiap hari', $view);
        $this->assertStringContainsString('<caption class="bk-sr-only">', $view);

        // Tinggi batang dihitung dari hari terbesar, bukan angka tetap.
        $this->assertStringContainsString("/ \$arusPuncak * 100", $view);
    }

    public function test_jam_pemakaian_menyebut_angka_nyata_bukan_persen_saja(): void
    {
        $view = $this->isiView();
        $controller = $this->isiController();

        $this->assertStringContainsString('adm-gauge', $view);
        $this->assertStringContainsString('--arc:{{ $pemakaianPersen }}', $view);
        $this->assertStringContainsString('{{ $papanKeluar }} dari {{ $stokTotal }}', $view);
        $this->assertStringContainsString('Pemakaian stok hari ini', $view);

        // Stok dihitung per warna bila papan punya warna, sama seperti katalog.
        $this->assertStringContainsString("RentalProduct::with('variants')", $controller);
        $this->assertStringContainsString('min(100, (int) round($papanKeluar / $stokTotal * 100))', $controller);

        // Angka harus bisa ditindaklanjuti: tautan ke daftar papan yang keluar.
        $this->assertStringContainsString("route('admin.rental.orders', ['status' => 'confirmed'])", $view);

        // Stok nol tidak boleh menjadi pembagi.
        $this->assertStringContainsString('$stokTotal > 0 ?', $controller);
    }

    public function test_kartu_angka_lama_tidak_lagi_mengulang_perlu_tindakanmu(): void
    {
        $view = $this->isiView();

        // Grid tiga kartu (Unit aktif / Menunggu / Tinjau manual) sudah dihapus;
        // angka itu kini ada di "Perlu tindakanmu" dan di jam pemakaian.
        $this->assertStringNotContainsString('class="adm-stats', $view);
        $this->assertStringNotContainsString('data-adm-reveal="stats"', $view);
        $this->assertStringNotContainsString('adm-revenue-title', $view);

        // "Perlu tindakanmu" tetap ada dan tetap di atas daftar.
        $this->assertStringContainsString('Perlu tindakanmu', $view);
    }

    public function test_animasi_grafik_tetap_terbaca_tanpa_javascript(): void
    {
        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));

        // Batang dan busur punya nilai akhir di aturan dasar; animasi hanya
        // tambahan saat `is-in` muncul, jadi tanpa skrip grafik tidak kosong.
        $this->assertMatchesRegularExpression('/\.adm-bars__fill \{[^}]*min-height: 3px;/s', $css);
        $this->assertMatchesRegularExpression('/\.adm-gauge__value \{[^}]*stroke-dashoffset: calc\(/s', $css);
        $this->assertStringContainsString('@keyframes adm-bar-grow', $css);
        $this->assertStringContainsString('@keyframes adm-gauge-sweep', $css);

        // Gerak dihormati: tanpa animasi bila pengguna memilih kurangi gerak.
        $this->assertMatchesRegularExpression('/prefers-reduced-motion: reduce[\s\S]{0,400}animation: none;/', $css);
    }

    public function test_tanpa_pustaka_animasi_eksternal(): void
    {
        $view = $this->isiView();
        $layout = (string) file_get_contents(resource_path('views/layouts/admin.blade.php'));

        // Aset hanya dari server sendiri: tidak ada CDN, tidak ada bundel baru.
        // Animasi dasbor memakai CSS + IntersectionObserver yang sudah ada.
        foreach (['gsap', 'lenis', 'react', 'unpkg', 'cdn.jsdelivr'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, strtolower($view), $terlarang.' muncul di dasbor.');
            $this->assertStringNotContainsString($terlarang, strtolower($layout), $terlarang.' muncul di layout admin.');
        }
    }
}
