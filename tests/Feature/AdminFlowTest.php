<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Putaran 43 — alur kerja panel admin.
 *
 * Dua hal yang dijaga di sini:
 *
 * 1. Panel admin hanya memuat `berkah-admin.css` + `page-transitions.css`.
 *    Setiap kelas `bk-*`/`adm-*` yang dipakai view admin WAJIB punya aturan di
 *    salah satu berkas itu. Dulu empat kelas dipinjam dari kit pelanggan tanpa
 *    pernah dimuat, sehingga `<caption class="bk-sr-only">` justru terlihat
 *    sebagai teks di buku kas dan kotak warna varian tidak tampil.
 *
 * 2. Tindakan utama per unit tidak boleh kembali terkubur di lipatan bawah:
 *    panel "Langkah berikutnya" harus ada di puncak kartu unit, dan formulir
 *    memajukan pengerjaan hanya boleh muncul sekali di halaman itu.
 */
class AdminFlowTest extends TestCase
{
    /** View yang memakai `layouts.admin`, ditambah partial bersama yang ikut dirender. */
    private function berkasAdmin(): array
    {
        $berkas = [
            'layouts/admin.blade.php',
            'catalog/product-fields.blade.php',
            'rental/_ordered_items.blade.php',
            'rental/admin-progress.blade.php',
            'rental/admin-cancellation-history.blade.php',
            'rental/admin-next-step.blade.php',
            'rental/cashbook-fields.blade.php',
            // Dirender di halaman admin yang berpaginasi (`Paginator::defaultView`).
            'vendor/pagination/token-simple.blade.php',
        ];

        // `File::allFiles` mengembalikan jalur relatif yang sudah bebas
        // pemisah direktori, jadi tetap benar di Windows (`\\`) maupun Linux
        // (`/`). Menyusun jalur relatif sendiri dengan `str_replace` gagal di
        // Windows karena `resource_path()` memakai garis miring terbalik.
        foreach (File::allFiles(resource_path('views')) as $berkasView) {
            $relatif = str_replace('\\', '/', $berkasView->getRelativePathname());
            if (str_contains((string) file_get_contents($berkasView->getPathname()), "@extends('layouts.admin')")) {
                $berkas[] = $relatif;
            }
        }

        foreach (File::allFiles(resource_path('views/layouts/component/admin')) as $berkasShell) {
            $berkas[] = 'layouts/component/admin/'.str_replace('\\', '/', $berkasShell->getRelativePathname());
        }

        return array_values(array_unique($berkas));
    }

    public function test_setiap_kelas_di_view_admin_punya_aturan_gaya(): void
    {
        $css = (string) file_get_contents(public_path('css/berkah-admin.css'))
            .(string) file_get_contents(public_path('css/page-transitions.css'));

        $dipakai = [];
        foreach ($this->berkasAdmin() as $relatif) {
            $jalur = resource_path('views/'.$relatif);
            $this->assertFileExists($jalur, "View {$relatif} hilang.");

            preg_match_all('/class="([^"]*)"/', (string) file_get_contents($jalur), $cocok);
            foreach ($cocok[1] as $daftar) {
                foreach (preg_split('/\s+/', trim($daftar)) ?: [] as $kelas) {
                    if (preg_match('/^(bk|adm|btn|card|notice|muted|stack|row|wrap)\b/', $kelas) === 1) {
                        $dipakai[$kelas] = true;
                    }
                }
            }
        }

        $tanpaGaya = [];
        foreach (array_keys($dipakai) as $kelas) {
            if (preg_match('/\.'.preg_quote($kelas, '/').'(?![-\w])/', $css) !== 1) {
                $tanpaGaya[] = $kelas;
            }
        }

        sort($tanpaGaya);
        $this->assertSame([], $tanpaGaya,
            'Kelas ini dipakai view admin tetapi tidak punya aturan di CSS yang dimuat admin: '.implode(', ', $tanpaGaya));
    }

    public function test_paginasi_admin_punya_tata_letak_bukan_hanya_warna(): void
    {
        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));

        // `.bk-page-nav` aslinya hanya ada di berkah-ui-kit.css (tidak dimuat
        // admin). Tanpa aturan wadah di sini, tautan halaman menumpuk ke bawah.
        $posisi = strpos($css, '.adm-app .bk-page-nav {');
        $this->assertNotFalse($posisi, 'Wadah paginasi admin kehilangan aturan tata letak.');

        $blok = substr($css, $posisi, 400);
        $this->assertStringContainsString('display: flex', $blok);
        $this->assertStringContainsString('flex-wrap: wrap', $blok);

        // Butirnya harus tetap 44 px seperti tombol lain di panel admin.
        $butir = substr($css, (int) strpos($css, '.adm-app .bk-page-nav__link,'), 500);
        $this->assertStringContainsString('min-height: 44px', $butir);
        $this->assertStringContainsString('min-width: 44px', $butir);
    }

    public function test_tindakan_utama_berada_di_puncak_kartu_unit(): void
    {
        $detail = (string) file_get_contents(resource_path('views/rental/admin-detail.blade.php'));

        // Panel dipanggil tepat setelah judul unit, bukan di dalam lipatan.
        $this->assertStringContainsString("@include('rental.admin-next-step', ['unit' => \$unit])", $detail);
        $this->assertLessThan(
            strpos($detail, '<summary>Ongkos dan tagihan</summary>'),
            strpos($detail, 'admin-next-step'),
            'Panel langkah berikutnya harus muncul sebelum lipatan ongkos.'
        );

        // Formulir memajukan pengerjaan tidak lagi dirender di dalam riwayat.
        $this->assertStringContainsString("@include('rental.admin-progress', ['admin' => true, 'actions' => false])", $detail);
        $this->assertSame(1, substr_count($detail, "route('admin.rental.progress.advance', \$unit)") + 1);
        $this->assertSame(1, substr_count((string) file_get_contents(resource_path('views/rental/admin-next-step.blade.php')), "route('admin.rental.progress.advance', \$unit)"));
    }

    public function test_panel_menyebut_tahap_hambatan_dan_bagian_terkait(): void
    {
        $panel = (string) file_get_contents(resource_path('views/rental/admin-next-step.blade.php'));

        foreach (['Mulai pengerjaan', 'Tandai siap diserahkan', 'Tandai sudah diserahkan', 'Tandai sudah dikembalikan'] as $label) {
            $this->assertStringContainsString($label, $panel, "Label tindakan {$label} hilang.");
        }

        // Penghalang diberi tautan ke bagian yang harus dibereskan lebih dulu.
        $this->assertStringContainsString('Pratinjau desain belum disetujui pelanggan.', $panel);
        $this->assertStringContainsString("['desain', 'Buka pratinjau desain']", $panel);
        $this->assertStringContainsString("['pembayaran', 'Buka pembayaran']", $panel);
        $this->assertStringContainsString('disabled aria-describedby="blocker-', $panel);

        // Tombol batal/lanjut tidak lagi berulang tiga kali di halaman.
        $detail = (string) file_get_contents(resource_path('views/rental/admin-detail.blade.php'));
        $this->assertSame(0, substr_count($detail, "route('admin.rental.resume', \$unit)"), 'Formulir melanjutkan harus tunggal, yaitu di panel.');
    }

    public function test_daftar_pesanan_menyebut_tugas_yang_menunggu(): void
    {
        $daftar = (string) file_get_contents(resource_path('views/rental/admin.blade.php'));

        // Kartu menyebut tugas + jangkar bagian terkait, bukan hanya badge status.
        $this->assertStringContainsString('$tugas', $daftar);
        $this->assertStringContainsString('adm-tasklist', $daftar);
        // Kutip tunggal: dengan kutip ganda, `$unit` akan disisipkan PHP dan
        // tesnya gagal karena variabel itu tidak ada di dalam tes.
        $this->assertStringContainsString('\'pembayaran-\'.$unit->id', $daftar);
        $this->assertStringContainsString('\'desain-\'.$unit->id', $daftar);
        $this->assertStringContainsString('Tidak ada yang menunggu admin di pesanan ini.', $daftar);
        $this->assertStringContainsString('+{{ $sisaTugas }} tugas lain', $daftar);

        // Hitungannya memakai relasi yang sudah dimuat, bukan kueri per unit.
        $kontroler = (string) file_get_contents(app_path('Http/Controllers/RentalOrderController.php'));
        $this->assertStringContainsString("RentalOrder::with(['customer', 'units.payments', 'units.previews'])", $kontroler);

        // Badge samar "perlu tindakan" digantikan baris tugas yang konkret.
        $this->assertStringNotContainsString('perlu tindakan', $daftar);
    }

    public function test_daftar_pesanan_bisa_diurutkan_menurut_tenggat_dan_acara(): void
    {
        $daftar = (string) file_get_contents(resource_path('views/rental/admin.blade.php'));

        $this->assertStringContainsString('name="sort"', $daftar);
        $this->assertStringContainsString('Tenggat konfirmasi terdekat', $daftar);
        $this->assertStringContainsString('Tanggal acara terdekat', $daftar);
        // Tidak ada dua pilihan untuk hal yang sama: "terbaru" hanya satu.
        $this->assertSame(1, substr_count($daftar, '>Bawaan</option>'));
        $this->assertStringNotContainsString('>Terbaru (tetap)</option>', $daftar);
        $this->assertStringContainsString('\'Urutan: \'.$labelUrutan', $daftar);

        $kontroler = (string) file_get_contents(app_path('Http/Controllers/RentalOrderController.php'));
        $this->assertStringContainsString("Rule::in(['tenggat', 'acara'])", $kontroler);
        $this->assertStringContainsString("withMin('units', 'event_on')", $kontroler);
        $this->assertStringContainsString("orderByRaw('confirmation_deadline IS NULL')", $kontroler);

        // Kolom baru wajib punya nama Indonesia agar pesan galat tidak
        // menampilkan nama variabel.
        $this->assertStringContainsString("'sort' => 'urutan',", (string) file_get_contents(base_path('lang/id/validation.php')));
    }

    public function test_buku_kas_membandingkan_dengan_periode_sebelumnya_tanpa_pembagian_nol(): void
    {
        $view = (string) file_get_contents(resource_path('views/rental/cashbook.blade.php'));

        $this->assertStringContainsString('$banding = function (int $sekarang, int $sebelum): ?string', $view);
        // Pembagi nol dicegat sebelum dibagi.
        $this->assertStringContainsString('if ($sebelum === 0)', $view);
        $this->assertStringContainsString('Dibanding {{ $previousStart->format', $view);
        // Kutip tunggal: dengan kutip ganda, `$bandingMasuk` disisipkan PHP.
        $this->assertStringContainsString('pemasukan {{ $bandingMasuk', $view);
    }

    public function test_chip_tugas_menjadi_44_px_di_perangkat_sentuh(): void
    {
        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(pointer: coarse\) \{[^}]*\.adm-tasklist__item a \{[^}]*min-height: 44px;/s',
            $css,
            'Chip tugas adalah tautan; di perangkat sentuh sasarannya harus 44 px.'
        );
    }

    public function test_bagian_berjangkar_dan_gaya_panel_ada(): void
    {
        $detail = (string) file_get_contents(resource_path('views/rental/admin-detail.blade.php'));
        foreach (['ongkos-', 'pembayaran-', 'desain-', 'riwayat-'] as $awalan) {
            $this->assertStringContainsString('id="'.$awalan.'{{ $unit->id }}"', $detail, "Jangkar {$awalan} hilang.");
        }

        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));
        foreach (['.adm-next {', '.adm-next--blocked', '.adm-next__title', '.adm-chip--stage', '.adm-stages {', '.bk-sr-only {', '.bk-swatch {', '.bk-items__list {', '.bk-disclosure {', '.adm-app .bk-page-nav {'] as $aturan) {
            $this->assertStringContainsString($aturan, $css, "Aturan {$aturan} hilang dari berkah-admin.css.");
        }
    }
}
