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

        // Tiga puluh hari dibangun sekali, tujuh hari terakhir diiris dari situ.
        $this->assertStringContainsString('for ($mundur = 29; $mundur >= 0; $mundur--)', $controller);
        $this->assertStringContainsString('$arusHarian = array_slice($arusBulanan, -7);', $controller);
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

    /** GSAP, React, dan Lenis dilayani dari `public/vendor`, bukan CDN. */
    public function test_pustaka_gerak_dan_react_dilayani_sendiri(): void
    {
        // Sudah dimuat global lewat _form_controls untuk semua peran.
        foreach ([
            'vendor/gsap/gsap.min.js',
            'vendor/gsap/ScrollTrigger.min.js',
            'vendor/gsap/SplitText.min.js',
            'vendor/lenis/lenis.min.js',
        ] as $aset) {
            $this->assertFileExists(public_path($aset), 'Aset gerak hilang: '.$aset);
        }

        // React + htm khusus dasbor, tanpa langkah build (UMD, bukan JSX).
        foreach ([
            'vendor/react/react.production.min.js' => 8_000,
            'vendor/react/react-dom.production.min.js' => 100_000,
            'vendor/htm/htm.umd.js' => 1_000,
            'js/adm-dashboard.js' => 4_000,
        ] as $aset => $ukuranMinimal) {
            $this->assertFileExists(public_path($aset), 'Aset hilang: '.$aset);
            $this->assertGreaterThan($ukuranMinimal, filesize(public_path($aset)), 'Aset terlalu kecil: '.$aset);
        }

        // Tidak boleh ada CDN: seluruh permintaan tetap ke server sendiri.
        $view = $this->isiView();
        foreach (['unpkg.com', 'cdn.jsdelivr', 'cdnjs', 'esm.sh', 'skypack'] as $cdn) {
            $this->assertStringNotContainsString($cdn, $view, 'Dasbor memuat CDN: '.$cdn);
        }
    }

    public function test_skrip_dasbor_dipanggil_dalam_urutan_yang_benar(): void
    {
        $view = $this->isiView();

        $urutan = [
            'vendor/react/react.production.min.js',
            'vendor/react/react-dom.production.min.js',
            'vendor/htm/htm.umd.js',
            'vendor/gsap/SplitText.min.js',
            'js/adm-dashboard.js',
        ];

        $sebelumnya = -1;
        foreach ($urutan as $aset) {
            $posisi = strpos($view, $aset);
            $this->assertNotFalse($posisi, 'Dasbor tidak memuat '.$aset);
            $this->assertGreaterThan($sebelumnya, $posisi, $aset.' dimuat dalam urutan yang salah.');
            $sebelumnya = $posisi;
        }

        // Semua defer supaya markup server tampil lebih dulu.
        $this->assertStringContainsString("defer></script>", $view);
    }

    /** Grafik server tetap ada: React hanya menggantinya bila berhasil dimuat. */
    public function test_react_hanya_tambahan_di_atas_grafik_server(): void
    {
        $view = $this->isiView();
        $js = (string) file_get_contents(public_path('js/adm-dashboard.js'));

        $this->assertStringContainsString('data-adm-chart', $view);
        $this->assertStringContainsString('data-adm-days=\'@json($arusBulanan)\'', $view);
        // Cadangan Blade tetap dirender di dalam wadah.
        $this->assertStringContainsString('class="adm-bars" role="img"', $view);

        // Tanpa pustaka lengkap, komponen tidak dipasang.
        $this->assertStringContainsString('if (!React || !ReactDOM || !htm || !ReactDOM.createRoot) return;', $js);
        $this->assertStringContainsString('ReactDOM.createRoot(host).render(', $js);

        // Interaksi nyata, bukan hiasan: rentang dan sorotan per hari.
        $this->assertStringContainsString('[7, 14, 30]', $js);
        $this->assertStringContainsString('aria-pressed=', $js);
        $this->assertStringContainsString('onMouseEnter=', $js);
    }

    public function test_gsap_memegang_kendali_tanpa_menganimasikan_dua_kali(): void
    {
        $js = (string) file_get_contents(public_path('js/adm-dashboard.js'));
        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));

        // Endpoint gerak: kurangi gerak mematikan semuanya.
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $js);
        $this->assertStringContainsString('if (!gsap || reduced()) return;', $js);

        // GSAP menandai body, CSS mematikan keyframe lama saat itu terjadi.
        $this->assertStringContainsString("document.body.classList.add('adm-gsap')", $js);
        $this->assertStringContainsString('body.adm-gsap .adm-gauge__value', $css);

        // Tiga gerak yang diminta: busur, batang, hitungan angka.
        $this->assertStringContainsString("gsap.fromTo(busur, { strokeDashoffset: 264 }", $js);
        $this->assertStringContainsString('window.gsap.fromTo(', $js);
        $this->assertStringContainsString('window.SplitText.create(h1', $js);

        // Kait angka ada di markup, jadi GSAP tidak menebak dari teks.
        $this->assertStringContainsString('data-adm-nilai="{{ (int) $pemasukanHari }}"', $this->isiView());
        $this->assertStringContainsString('data-adm-persen="{{ $pemakaianPersen }}"', $this->isiView());
    }
}
