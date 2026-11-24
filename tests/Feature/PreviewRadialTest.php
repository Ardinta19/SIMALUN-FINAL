<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pratinjau radial (docs/preview/radial.html) harus memakai kode asli, bukan
 * salinan: CSS diambil dari partial Blade, gerak dari public/js/berkah-radial.js,
 * dan posisi tiap tujuan sama dengan hasil hitungan server. Tes ini menghitung
 * ulang geometri itu dan membandingkannya dengan yang tertulis di pratinjau,
 * supaya pratinjau tidak pernah menyimpang tanpa ketahuan.
 */
class PreviewRadialTest extends TestCase
{
    /** Rumus yang sama dengan partial: busur 180° → 79°, jarak antarpusat 50 px. */
    private function offsets(int $slots): array
    {
        $span = 101;
        $step = $slots > 1 ? $span / ($slots - 1) : 0;
        $radius = $step > 0 ? (int) round(25 / sin(deg2rad($step / 2))) : 86;
        $radius = max(86, $radius);

        $offsets = [];
        for ($slot = 0; $slot < $slots; $slot++) {
            $angle = deg2rad(180 - ($slot * $step));
            $offsets[] = [(int) round($radius * cos($angle)), (int) round($radius * sin($angle))];
        }

        return $offsets;
    }

    private function preview(): string
    {
        return File::get(base_path('docs/preview/radial.html'));
    }

    public function test_pratinjau_memakai_css_dan_skrip_asli(): void
    {
        $html = $this->preview();

        // CSS diambil dari partial, bukan disalin.
        $this->assertStringContainsString('resources/views/layouts/component/customer/_radial_customer.blade.php', $html);
        $this->assertStringContainsString('<script src="../../public/js/berkah-radial.js" defer></script>', $html);
        $this->assertStringContainsString('<script src="../../public/vendor/gsap/gsap.min.js"></script>', $html);
        $this->assertStringContainsString('<script src="../../public/vendor/lenis/lenis.min.js"></script>', $html);

        // Pratinjau adalah HTML statis: sintaks Blade akan tercetak apa adanya
        // sebagai teks di halaman (pernah terjadi pada komentar {{-- --}}).
        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('@include', $html);
        $this->assertStringNotContainsString('@php', $html);
        $this->assertStringNotContainsString('@endforeach', $html);

        // Tanpa CDN.
        $this->assertStringNotContainsString('cdn.', $html);
        $this->assertStringNotContainsString('unpkg.com', $html);
        $this->assertStringNotContainsString('jsdelivr', $html);
        $this->assertStringNotContainsString('fonts.googleapis', $html);
    }

    public function test_posisi_pelanggan_di_pratinjau_sama_dengan_hitungan_server(): void
    {
        $html = $this->preview();

        // Enam slot: lima tujuan pelanggan + slot tema.
        foreach ($this->offsets(6) as [$x, $y]) {
            $this->assertStringContainsString(
                'style="--rx:'.$x.'px;--ry:'.$y.'px"',
                $html,
                'Pratinjau harus memakai offset busur 6 titik yang sama dengan partial.'
            );
        }
    }

    public function test_pratinjau_juga_menampilkan_busur_tamu(): void
    {
        $html = $this->preview();

        $this->assertStringContainsString('data-demo-role="customer"', $html);
        $this->assertStringContainsString('data-demo-role="guest"', $html);
        $this->assertStringContainsString('?embed=1&amp;tamu=1', $html);

        // Empat tujuan tamu, sama seperti partial untuk pengunjung.
        foreach (['Katalog', 'Keranjang', 'Daftar akun', 'Masuk'] as $label) {
            $this->assertStringContainsString('aria-label="'.$label.'"', $html);
        }

        // Busur 5 titik (radius 114 px), bukan 6 titik.
        foreach ($this->offsets(5) as [$x, $y]) {
            $this->assertStringContainsString(
                'style="--rx:'.$x.'px;--ry:'.$y.'px"',
                $html,
                'Pratinjau tamu harus memakai offset busur 5 titik.'
            );
        }
    }

    public function test_pratinjau_menampilkan_tiga_bentuk_busur(): void
    {
        $html = $this->preview();

        // Tiga penempatan: kanan (server), tengah (U), kiri (cermin).
        $this->assertStringContainsString('?embed=1&amp;tamu=1', $html, 'Bingkai tamu.');
        $this->assertStringContainsString('?embed=1&amp;sisi=kiri', $html, 'Bingkai tombol di sisi kiri.');
        $this->assertStringContainsString('body.demo-kiri .mock-header { justify-content: flex-start; }', $html);
        $this->assertStringContainsString('is-arc-opposite', $html);
        $this->assertStringContainsString('bentuk U', $html);
    }

    /** Pratinjau harus jujur soal lebar layar: busur hanya ada <=700 px.
     *  Bingkai ponsel memaksa tombol tampil supaya bisa diklik, tetapi bingkai
     *  760 px tidak — di situlah aturan aslinya terbukti. */
    public function test_pratinjau_membuktikan_busur_hilang_di_layar_lebar(): void
    {
        $html = $this->preview();

        // Pemaksaan hanya untuk halaman luar, bukan untuk isi bingkai.
        $this->assertStringContainsString('body.demo-page:not(.demo-embed) .bk-header-radial { display: block; }', $html);
        $this->assertStringNotContainsString('.demo-page .bk-header-radial { display: block; }', $html);

        // Bingkai lebar + tautan header yang menggantikan busur.
        $this->assertStringContainsString('width="760"', $html);
        $this->assertStringContainsString('Layar lebar: tombol busur tidak dirender', $html);
        $this->assertStringContainsString('class="mock-nav"', $html);
        $this->assertStringContainsString('@media (max-width: 700px) { .mock-nav { display: none; } }', $html);
    }

    public function test_pratinjau_tidak_menghidupkan_lagi_cincin_atau_gumpalan(): void
    {
        $html = $this->preview();

        // §9.26: cincin orbit dan gumpalan goo tidak boleh kembali lewat pratinjau.
        $this->assertStringNotContainsString('--bk-orbit', $html);
        $this->assertStringNotContainsString('customer-radial__links::before', $html);
    }
}
