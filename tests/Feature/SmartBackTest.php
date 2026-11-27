<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tombol "kembali" pintar tidak boleh berbohong tentang tujuannya.
 *
 * Sebelum §9.138, `history.back()` dipakai untuk SETIAP referrer dalam situs.
 * Akibatnya tombol berlabel "Kembali ke keranjang" di halaman checkout bisa
 * membawa pengunjung ke halaman papan bunga — bukan keranjang — karena asal
 * navigasi itulah yang dibuka riwayat. Sekarang riwayat hanya dipakai bila
 * jalur tujuan sama dengan jalur di href; selain itu href yang menang.
 */
class SmartBackTest extends TestCase
{
    private function source(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/public/js/berkah-smart-back.js');
    }

    public function test_riwayat_hanya_dipakai_bila_jalur_tujuan_sama_dengan_href(): void
    {
        $js = $this->source();

        $this->assertStringContainsString('function samePath(', $js);
        $this->assertStringContainsString("if (!samePath(referrer.pathname, href.pathname)) return false;", $js,
            'Riwayat harus ditolak bila halaman asalnya berbeda dari tujuan yang tertulis di tombol.');

        // Perbandingan harus mengabaikan garis miring di ujung.
        $this->assertStringContainsString("replace(/\\/+$/, '')", $js);
    }

    public function test_halaman_formulir_tetap_dilewati(): void
    {
        $js = $this->source();

        $this->assertStringContainsString("'/login'", $js);
        $this->assertStringContainsString("'/checkout'", $js);
        $this->assertStringContainsString("'/register'", $js);
    }

    /** Setiap tombol harus punya href yang bisa diikuti tanpa JavaScript. */
    public function test_setiap_tombol_kembali_punya_href_cadangan(): void
    {
        $root = dirname(__DIR__, 2).'/resources/views';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        $masalah = [];
        $jumlah = 0;

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $isi = (string) file_get_contents($file->getPathname());

            if (! preg_match_all('/<a\b[^>]*data-smart-back[^>]*>/i', $isi, $cocok)) {
                continue;
            }

            foreach ($cocok[0] as $tag) {
                $jumlah++;

                if (! preg_match('/\bhref="([^"]*)"/i', $tag, $href)) {
                    $masalah[] = str_replace($root.'/', '', $file->getPathname()).': tanpa href';
                    continue;
                }

                if (trim($href[1]) === '' || trim($href[1]) === '#') {
                    $masalah[] = str_replace($root.'/', '', $file->getPathname()).': href kosong';
                }
            }
        }

        $this->assertGreaterThan(10, $jumlah, 'Pemindaian view tampaknya tidak menemukan tombol kembali.');
        $this->assertSame([], $masalah, "Tombol kembali tanpa href cadangan:\n".implode("\n", $masalah));
    }
}
