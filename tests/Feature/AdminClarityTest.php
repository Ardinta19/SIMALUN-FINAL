<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kejelasan panel admin (putaran 41).
 *
 * Pemilik usaha menyebut panel admin "masih sangat membingungkan". Tiga akar
 * yang bisa dibuktikan dari kode:
 *
 *  1. filter diubah tetapi belum dikirim — kolom Bulan berkata November,
 *     daftar masih Januari;
 *  2. tidak ada ringkasan "hari ini", padahal itu yang dicari saat panel dibuka;
 *  3. dua tombol kembali (panah bilah atas dan tautan besar "← Kembali").
 *
 * Tes ini menahan perbaikannya supaya tidak kembali lagi.
 */
class AdminClarityTest extends TestCase
{
    /** @return array<string, string> nama relatif => isi */
    private function adminViews(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->mapWithKeys(fn ($file) => [
                str_replace('\\', '/', $file->getRelativePathname()) => (string) file_get_contents($file->getPathname()),
            ])
            ->filter(fn (string $body) => str_contains($body, 'layouts.admin'))
            ->all();
    }

    public function test_filter_langsung_berlaku_tanpa_menekan_tombol(): void
    {
        $js = (string) file_get_contents(public_path('js/berkah-admin.js'));

        $this->assertStringContainsString("each('.adm-toolbar'", $js, 'Pemantau filter otomatis hilang.');
        $this->assertStringContainsString("form.setAttribute('data-adm-autofilter', 'on')", $js);
        $this->assertStringContainsString('requestSubmit', $js);
        // Penjaga nilai awal: kendali yang memicu `change` saat disiapkan tidak
        // boleh membuat halaman memuat ulang sendiri.
        $this->assertStringContainsString('var awal = serialize(form);', $js);

        // Setiap halaman berfilter wajib menjelaskan bahwa pilihan langsung berlaku.
        $toolbars = collect($this->adminViews())->filter(fn (string $body) => str_contains($body, 'class="adm-toolbar"'));
        $this->assertGreaterThanOrEqual(4, $toolbars->count());

        foreach ($toolbars as $path => $body) {
            $this->assertStringContainsString('adm-toolbar__hint', $body, 'Halaman tanpa petunjuk filter: '.$path);
        }
    }

    public function test_filter_aktif_ditulis_ulang_sebagai_chip_yang_bisa_dilepas(): void
    {
        $partial = resource_path('views/layouts/component/admin/_filter_state.blade.php');
        $this->assertFileExists($partial);

        $isi = (string) file_get_contents($partial);
        $this->assertStringContainsString('Filter aktif', $isi);
        $this->assertStringContainsString('adm-filterstate__x', $isi);

        foreach ([
            'rental/admin.blade.php',
            'rental/schedule.blade.php',
            'rental/cashbook.blade.php',
            'roles/admin/audit/index.blade.php',
        ] as $path) {
            $body = (string) file_get_contents(resource_path('views/'.$path));
            $this->assertStringContainsString('admin._filter_state', $body, 'Halaman tanpa ringkasan filter: '.$path);
        }
    }

    public function test_dasbor_menampilkan_hari_ini_dan_tidak_membuang_separuh_layar(): void
    {
        $controller = (string) file_get_contents(app_path('Http/Controllers/DashboardController.php'));
        $this->assertStringContainsString('hariIniTerima', $controller);
        $this->assertStringContainsString('hariIniKembali', $controller);

        $view = (string) file_get_contents(resource_path('views/roles/admin/dashboard.blade.php'));
        $this->assertStringContainsString('Hari ini', $view);
        $this->assertStringContainsString('adm-today', $view);
        $this->assertStringContainsString("route('admin.rental.schedule.daily-sheet'", $view);

        // Dulu `adm-grid--split` hanya berisi satu anak sehingga separuh kanan
        // layar kosong. Sekarang grid dua kolom dipakai apa adanya.
        $this->assertStringNotContainsString('adm-grid--split', $view, 'Dasbor memakai kolom terbelah tanpa pasangan.');
        $this->assertStringContainsString('adm-grid adm-grid--2', $view);
    }

    public function test_jadwal_hanya_menampilkan_tanggal_terisi(): void
    {
        $view = (string) file_get_contents(resource_path('views/rental/schedule.blade.php'));

        $this->assertStringContainsString('$hariTerisi', $view);
        $this->assertStringContainsString('$hariKosong', $view);
        $this->assertStringContainsString('adm-quietdays', $view);
        $this->assertStringContainsString('tanggal lain tanpa unit', $view);
        // Hari kosong tidak lagi menjadi kartu penuh selebar layar.
        $this->assertStringNotContainsString('Tidak ada unit terjadwal', $view);
        // Keadaan kosong menyebut langkah berikutnya, bukan hanya "kosong".
        $this->assertStringContainsString('Pesanan menunggu konfirmasi', $view);
        $this->assertStringContainsString("'hari-ini'", $view);
        $this->assertStringContainsString('href="#hari-ini"', $view);
    }

    public function test_hanya_ada_satu_kendali_kembali_di_panel_admin(): void
    {
        $topbar = (string) file_get_contents(resource_path('views/layouts/component/admin/_topbar.blade.php'));
        $this->assertStringContainsString('adm-topbar__back', $topbar);

        $offenders = [];
        foreach ($this->adminViews() as $path => $body) {
            if (str_contains($body, 'class="bk-back"')) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders, 'Tautan "← Kembali" besar menduplikasi panah bilah atas.');
    }

    public function test_judul_halaman_tidak_terpotong_di_ponsel(): void
    {
        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 560px\) \{[^}]*\.adm-crumbs a \{ display: none; \}/s',
            $css,
            'Remah roti di ponsel harus menyisakan judul halaman saja.'
        );
        $this->assertStringContainsString('overflow-x: auto', $css);
    }

    public function test_pencarian_pesanan_menyebut_isi_pesanan(): void
    {
        $view = (string) file_get_contents(resource_path('views/rental/admin.blade.php'));

        $this->assertStringContainsString('$namaPapan', $view);
        $this->assertStringContainsString('papan lain', $view);
        $this->assertStringContainsString('>Tampilkan</button>', $view);
    }
}
