<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Mencegah bug visual berulang:
 *  1. Setiap ukuran viewport `dvh` wajib punya fallback `vh` (WebView lama
 *     mengabaikan dvh sehingga dialog tumbuh melebihi layar dan terpotong).
 *  2. Dialog/lembar filter harus punya batas tinggi + padding safe area.
 *  3. Header ringkas tidak boleh memakai flag permanen yang membuat logo
 *     menghilang setelah diketuk lalu halaman digulir.
 */
class ResponsiveLayoutTest extends TestCase
{
    /** @return array<string, string> nama sumber => isi CSS */
    private function cssSources(): array
    {
        $sources = [];

        foreach (File::files(public_path('css')) as $file) {
            if (str_ends_with($file->getFilename(), '.css')) {
                $sources['public/css/'.$file->getFilename()] = File::get($file->getPathname());
            }
        }

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $text = File::get($file->getPathname());
            if (! str_contains($text, '<style')) {
                continue;
            }
            preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $text, $matches);
            foreach ($matches[1] as $index => $css) {
                $sources[str_replace('\\', '/', $file->getRelativePathname()).'#style'.$index] = $css;
            }
        }

        return $sources;
    }

    public function test_every_dvh_declaration_has_a_vh_fallback(): void
    {
        $problems = [];

        foreach ($this->cssSources() as $name => $css) {
            $css = preg_replace('!/\*.*?\*/!s', '', $css);
            preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $blocks, PREG_SET_ORDER);

            foreach ($blocks as $block) {
                $body = $block[2];
                if (! str_contains($body, 'dvh')) {
                    continue;
                }

                preg_match_all('/([a-z-]+)\s*:\s*([^;{}]+)/i', $body, $declarations, PREG_SET_ORDER);
                $seen = [];

                foreach ($declarations as $declaration) {
                    $property = strtolower(trim($declaration[1]));
                    $value = trim($declaration[2]);

                    if (str_contains($value, 'dvh')) {
                        $hasFallback = false;
                        foreach ($seen as $previous) {
                            if ($previous['property'] === $property && str_contains($previous['value'], 'vh')) {
                                $hasFallback = true;
                                break;
                            }
                        }
                        if (! $hasFallback) {
                            $problems[] = $name.' → '.$property.': '.$value;
                        }
                    }

                    $seen[] = ['property' => $property, 'value' => $value];
                }
            }
        }

        $this->assertSame([], $problems, "Deklarasi dvh tanpa fallback vh:\n".implode("\n", $problems));
    }

    public function test_filter_dialog_stays_inside_the_viewport_on_every_screen(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertMatchesRegularExpression(
            '/\.bk-dialog\s*\{[^}]*max-height:\s*calc\(100vh - [^)]*\)[^}]*max-height:\s*calc\(100dvh/',
            $css,
            'Dialog harus dibatasi dengan fallback vh lalu dvh.'
        );
        $this->assertStringContainsString('padding-bottom: calc(clamp(16px, 4.2vw, 26px) + env(safe-area-inset-bottom', $css);
        $this->assertStringContainsString('overscroll-behavior: contain', $css);
        $this->assertMatchesRegularExpression('/@media \(max-width: 700px\)\s*\{\s*\.bk-dialog\s*\{[^}]*width:\s*calc\(100% - 16px\)/s', $css);
        $this->assertStringContainsString('[data-filter-form] > p:last-of-type', $css);
        $this->assertStringContainsString('position: sticky', $css);
    }

    public function test_compact_header_cannot_disappear_after_becoming_pinned(): void
    {
        $script = File::get(public_path('js/berkah-experience.js'));
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertStringContainsString('is-pinned', $script);
        $this->assertStringContainsString('is-pinned', $css);
        $this->assertStringContainsString('.bk-header.is-pinned', $css);
        $this->assertStringNotContainsString('holdOpen', $script, 'Flag holdOpen permanen membuat logo menghilang setelah digulir.');

        // Header ringkas tetap bisa muncul lagi setelah menu ditutup.
        $this->assertStringContainsString('radialMenu.addEventListener(\'toggle\'', $script);
        $this->assertStringContainsString("header.classList.add('is-compact'", $script);
        $this->assertStringContainsString("header.classList.remove('is-compact', 'is-pinned')", $script);
    }

    public function test_customer_bottom_navigation_does_not_cover_content(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\)\s*\{\s*body\.bk-customer\s*\{\s*padding-bottom:\s*calc\(84px \+ env\(safe-area-inset-bottom/',
            $css,
            'Navigasi bawah pelanggan (68 px + safe area) butuh padding body.'
        );
    }

    public function test_catalog_header_respects_the_notch_and_keeps_the_theme_slot(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));
        $kit = File::get(public_path('css/berkah-ui-kit.css'));
        $nav = File::get(resource_path('views/catalog/customer-navigation.blade.php'));

        // Header melayang di atas hero: jarak atas dan ruang cadangannya harus
        // ikut safe-area, kalau tidak header masuk ke bawah status bar/poni.
        $this->assertStringContainsString('top:calc(env(safe-area-inset-top, 0px) + 16px)', $css);
        $this->assertStringContainsString('height:calc(env(safe-area-inset-top, 0px) + 96px)', $css);
        $this->assertStringContainsString('top:calc(env(safe-area-inset-top, 0px) + 12px)', $css);

        // Di ponsel slot tema tetap berada di header. Dulu seluruh aksi desktop
        // disembunyikan sehingga tombol tema jatuh ke dok mengambang dan
        // menutupi kartu produk di bawahnya.
        $this->assertStringContainsString('.bk-desktop-actions{display:flex;align-items:center;gap:3px}', $css);
        $this->assertMatchesRegularExpression(
            '/\.bk-desktop-actions > a \{\s*display: none;/',
            $css,
            'Tautan akun di header ponsel harus disembunyikan; slot tema tetap tampil.'
        );
        $this->assertStringContainsString('.bk-header.is-compact .bk-desktop-actions{display:flex}', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/@media\s*\(max-width:\s*700px\)\s*\{\s*\.bk-desktop-actions\s*\{\s*display:\s*none/',
            $css,
            'Wadah aksi tidak boleh disembunyikan seluruhnya di ponsel; slot tema ada di dalamnya.'
        );
        $this->assertMatchesRegularExpression(
            '/\.bk-theme-host \{\s*display: flex;/',
            $kit,
            'Slot tema harus tampil di header ponsel.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/@media \(max-width: 700px\) \{\s*\.bk-theme-host \{\s*display: none/',
            $kit,
            'Slot tema tidak boleh disembunyikan lagi di ponsel.'
        );
        $this->assertStringContainsString('data-bk-theme-host', $nav);
    }

    public function test_mobile_catalog_rhythm_uses_scroll_snap_and_44px_targets(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));
        $kit = File::get(public_path('css/berkah-ui-kit.css'));
        $motion = File::get(public_path('js/berkah-motion.js'));

        // Strip tiga langkah bergulir mendatar: snap + tepi kanan memudar
        // sebagai isyarat bahwa masih ada langkah berikutnya.
        $this->assertStringContainsString('scroll-snap-type:x proximity', $css);
        $this->assertStringContainsString('mask-image:linear-gradient(90deg,#000 calc(100% - 26px),transparent)', $css);

        // Sasaran sentuh minimum 44 px di chip kategori dan stepper jumlah.
        $this->assertMatchesRegularExpression(
            '/\.bk-category-strip a \{\s*min-height: 44px;/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\) \{\s*\.bk-qty__btn \{\s*width: 44px;\s*height: 44px;/',
            $kit
        );

        // Lenis tidak boleh menahan gulir mendatar pada dua wadah itu.
        $this->assertStringContainsString("'.bk-how', '.bk-category-strip'", $motion);
    }

    public function test_catalog_filter_uses_a_single_clear_title(): void
    {
        $catalog = File::get(resource_path('views/catalog/index.blade.php'));

        $this->assertStringContainsString('id="bk-filter-title">Cari papan<', $catalog);
        $this->assertStringContainsString('<label for="catalog-search">Kata kunci</label>', $catalog);
        $this->assertStringNotContainsString('<label for="catalog-search">Cari papan</label>', $catalog);
    }
}
