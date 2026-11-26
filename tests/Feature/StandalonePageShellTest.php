<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Halaman yang punya <head> sendiri wajib punya pengaman yang sama.
 *
 * `catalog.layout` dan `layouts.admin` masing-masing memuat
 * `berkah-experience.css` / `berkah-admin.css` yang sudah memotong luapan
 * mendatar. Halaman auth, dasbor admin mandiri, alamat/profil pelanggan,
 * lembar cetak, dan halaman galat tidak lewat layout itu — dulu hanya
 * mengandalkan `overflow-x: hidden` pada <body>, yang (a) menjadikan <body>
 * kotak gulir sehingga `position: sticky`/`fixed` ikut bergeser dan
 * (b) mewariskan nilainya ke viewport sehingga tidak memotong apa pun.
 *
 * Sekarang semua halaman mandiri memuat `css/berkah-page-shell.css` lewat
 * `layouts/component/_page_shell.blade.php`.
 */
class StandalonePageShellTest extends TestCase
{
    /** Layout yang sudah punya pengamannya sendiri. */
    private const PUNYA_SENDIRI = [
        'catalog/layout.blade.php',
        'layouts/admin.blade.php',
        'emails/layout.blade.php',
    ];

    /** @return array<int, string> nama relatif view yang punya <head> sendiri */
    private function halamanMandiri(): array
    {
        $mandiri = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (! str_ends_with($relative, '.blade.php') || in_array($relative, self::PUNYA_SENDIRI, true)) {
                continue;
            }

            if (stripos((string) File::get($file->getPathname()), '<!doctype') !== false) {
                $mandiri[] = $relative;
            }
        }

        sort($mandiri);

        return $mandiri;
    }

    public function test_setiap_halaman_mandiri_memuat_pengaman_luapan(): void
    {
        $mandiri = $this->halamanMandiri();

        $this->assertNotEmpty($mandiri, 'Tidak ada halaman mandiri yang terdeteksi — periksa pemindaian view.');

        $tanpaPengaman = [];

        foreach ($mandiri as $relative) {
            $html = (string) File::get(resource_path('views/'.$relative));

            if (! str_contains($html, "layouts.component._page_shell")) {
                $tanpaPengaman[] = $relative;
            }
        }

        $this->assertSame([], $tanpaPengaman, 'Halaman mandiri tanpa pengaman luapan: '.implode(', ', $tanpaPengaman));
        $this->assertGreaterThanOrEqual(16, count($mandiri), 'Jumlah halaman mandiri menyusut — ada halaman yang pindah layout tanpa pengaman.');
    }

    public function test_halaman_mandiri_tidak_lagi_memakai_overflow_x_hidden(): void
    {
        $pelanggaran = [];

        foreach ($this->halamanMandiri() as $relative) {
            $html = (string) File::get(resource_path('views/'.$relative));

            if (preg_match('/overflow-x\s*:\s*hidden/i', $html)) {
                $pelanggaran[] = $relative;
            }
        }

        $this->assertSame(
            [],
            $pelanggaran,
            'Pakai `overflow-x: clip` (atau biarkan pengaman halaman bekerja), bukan `hidden`: '
                .implode(', ', $pelanggaran)
        );
    }

    /** Pengguna papan ketik harus bisa melewati header.
     *
     *  Sebelumnya hanya halaman ber-layout yang punya tautan "Langsung ke isi"
     *  (`.bk-skip` / `.adm-skip`). Halaman mandiri — termasuk 5 halaman admin —
     *  memaksa pengguna menekan Tab melewati seluruh navigasi lebih dulu.
     */
    public function test_setiap_halaman_mandiri_punya_tautan_lompat_ke_isi(): void
    {
        $masalah = [];

        foreach ($this->halamanMandiri() as $relative) {
            $html = (string) File::get(resource_path('views/'.$relative));

            $punyaTautan = str_contains($html, '_skip_link')
                || str_contains($html, 'catalog.customer-navigation')
                || str_contains($html, 'bk-skip')
                || str_contains($html, 'adm-skip');

            $punyaTarget = (bool) preg_match('/<(?:main|div)\b[^>]*\bid="(?:main-content|page)"[^>]*\btabindex="-1"/', $html);

            if (! $punyaTautan || ! $punyaTarget) {
                $masalah[] = $relative
                    .($punyaTautan ? '' : ' → tanpa tautan lompat')
                    .($punyaTarget ? '' : ' → tanpa target id + tabindex="-1"');
            }
        }

        $this->assertSame([], $masalah, 'Halaman mandiri yang sulit dilewati: '.implode(', ', $masalah));
    }

    public function test_berkas_pengaman_berisi_aturan_kunci(): void
    {
        $css = preg_replace('/\s+/', ' ', (string) File::get(public_path('css/berkah-page-shell.css')));

        $this->assertStringContainsString('@supports (overflow: clip) { @media screen { html { overflow-x: clip; } } }', $css,
            'Pengaman wajib memotong luapan mendatar, dan hanya di layar (cetakan tidak boleh terpotong).');
        $this->assertStringContainsString('body { overflow-wrap: break-word; }', $css);
        $this->assertStringContainsString('min-width: 0; }', $css,
            'Anak grid/flex harus boleh menyusut.');
        $this->assertStringContainsString('.adm-scroll-x', $css,
            'Tabel lebar tetap menggulir di pembungkusnya.');
        $this->assertStringContainsString(':focus-visible { outline: 3px solid #bc5b75;', $css,
            'Cincin fokus harus sama di halaman yang tidak punya aturan fokus sendiri.');
    }

    /** Halaman admin mandiri dipatok terang sampai dimigrasikan.
     *
     *  Kelimanya memakai palet terang `_tokens` dan kartu putih. Tema global
     *  (`html[data-theme="dark"] body`, `:is(h1..h6)`) tetap berlaku di sana,
     *  sehingga judul menjadi terang di atas kartu putih. Dua variabel tema
     *  dipatok ke nilai terang per halaman; setelah migrasi ke
     *  `layouts.admin` (`.adm-app`) pinning ini bisa dihapus.
     */
    public function test_halaman_admin_mandiri_dipatok_terang(): void
    {
        $halaman = [
            'roles/admin/dashboard.blade.php',
            'roles/admin/notifications.blade.php',
            'roles/admin/profile.blade.php',
            'roles/admin/profile_edit.blade.php',
            'roles/admin/audit/index.blade.php',
        ];

        foreach ($halaman as $relative) {
            $html = (string) File::get(resource_path('views/'.$relative));

            $this->assertStringContainsString('class="adm-standalone"', $html, $relative.' belum menandai <body>.');
            $this->assertStringContainsString(
                'html[data-theme="dark"] body.adm-standalone {',
                $html,
                $relative.' belum mematok variabel tema terang.'
            );
            $this->assertStringContainsString('--theme-ink: var(--brand-900);', $html, $relative.' teks judul bisa jadi terang di kartu putih.');
        }
    }

    /** Tema gelap tidak boleh "gagal" di halaman pelanggan mandiri.
     *
     *  Tiga halaman ini memakai `<style>` sendiri dengan variabel lokal
     *  (`--surface`, `--card`, `--ink`, `--border`) dan latar putih lepas,
     *  sehingga tetap menyala terang walau pengguna memilih tema gelap di
     *  header. Pemetaan gelap ditulis per halaman agar tidak mengubah halaman
     *  lain yang memakai nama variabel sama untuk palet terangnya.
     */
    public function test_halaman_pelanggan_mandiri_mengikuti_tema_gelap(): void
    {
        $halaman = [
            'roles/customer/addresses/create.blade.php',
            'roles/customer/addresses/edit.blade.php',
            'roles/customer/profile/edit.blade.php',
        ];

        foreach ($halaman as $relative) {
            $html = (string) File::get(resource_path('views/'.$relative));

            $this->assertStringContainsString('html[data-theme="dark"] {', $html, $relative.' belum punya pemetaan variabel gelap.');
            $this->assertStringContainsString('html[data-theme="dark"] .section-card', $html, $relative.' belum menggelapkan kartu.');
            $this->assertStringContainsString('html[data-theme="dark"] .field-input', $html, $relative.' belum menggelapkan kolom isian.');
        }
    }

    public function test_area_admin_punya_pengaman_sendiri(): void
    {
        $css = preg_replace('/\s+/', ' ', (string) File::get(public_path('css/berkah-admin.css')));

        $this->assertStringContainsString('html:has(> body.adm-app) { overflow-x: clip; }', $css,
            'Halaman ber-layout admin tidak memuat berkah-experience.css, jadi pengamannya ada di berkah-admin.css.');
        $this->assertStringContainsString('.adm-app :where(.adm-content, .adm-toolbar, .adm-field-grid, .adm-card, .adm-stack) > * { min-width: 0; }', $css);
    }
}
