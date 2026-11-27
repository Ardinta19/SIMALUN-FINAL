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

    public function test_bagian_berjangkar_dan_gaya_panel_ada(): void
    {
        $detail = (string) file_get_contents(resource_path('views/rental/admin-detail.blade.php'));
        foreach (['ongkos-', 'pembayaran-', 'desain-', 'riwayat-'] as $awalan) {
            $this->assertStringContainsString('id="'.$awalan.'{{ $unit->id }}"', $detail, "Jangkar {$awalan} hilang.");
        }

        $css = (string) file_get_contents(public_path('css/berkah-admin.css'));
        foreach (['.adm-next {', '.adm-next--blocked', '.adm-next__title', '.adm-chip--stage', '.bk-sr-only {', '.bk-swatch {', '.bk-items__list {', '.bk-disclosure {'] as $aturan) {
            $this->assertStringContainsString($aturan, $css, "Aturan {$aturan} hilang dari berkah-admin.css.");
        }
    }
}
