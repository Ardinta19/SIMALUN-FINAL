<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menjaga lapisan gerak bersama: Lenis (gulir halus), GSAP + ScrollTrigger
 * (kemunculan, parallax, hitungan), dan micro-interaction.
 *
 * Semua pustaka harus disajikan dari public/vendor (tanpa CDN) dan dimuat
 * lewat satu titik: layouts/component/_form_controls.blade.php, sehingga
 * guest, auth, customer, maupun admin mendapat perilaku yang sama.
 */
class MotionAssetsTest extends TestCase
{
    private function source(string $relative): string
    {
        $path = public_path($relative);

        $this->assertFileExists($path, "Aset gerak hilang: public/{$relative}");

        return (string) file_get_contents($path);
    }

    public function test_animation_libraries_are_vendored_locally(): void
    {
        foreach ([
            'vendor/gsap/gsap.min.js',
            'vendor/gsap/ScrollTrigger.min.js',
            'vendor/lenis/lenis.min.js',
            'vendor/lenis/lenis.css',
            'js/berkah-motion.js',
            'css/berkah-motion.css',
        ] as $asset) {
            $this->assertFileExists(public_path($asset), "Aset gerak hilang: public/{$asset}");
        }

        $this->assertGreaterThan(20_000, filesize(public_path('vendor/gsap/ScrollTrigger.min.js')));
        $this->assertGreaterThan(8_000, filesize(public_path('vendor/lenis/lenis.min.js')));
    }

    public function test_motion_partial_loads_every_library_with_cache_busting(): void
    {
        $partial = (string) file_get_contents(
            resource_path('views/layouts/component/_form_controls.blade.php')
        );

        // Urutan penting: GSAP → ScrollTrigger → Lenis → mesin gerak.
        $expected = [
            'vendor/gsap/gsap.min.js',
            'vendor/gsap/ScrollTrigger.min.js',
            'vendor/lenis/lenis.min.js',
            'js/berkah-motion.js',
        ];

        $previous = -1;
        foreach ($expected as $asset) {
            $position = strpos($partial, $asset);
            $this->assertNotFalse($position, "Partial tidak memuat {$asset}.");
            $this->assertGreaterThan($previous, $position, "{$asset} dimuat dalam urutan yang salah.");
            $previous = $position;
        }

        $this->assertStringContainsString("vendor/lenis/lenis.css", $partial);
        $this->assertStringContainsString("css/berkah-motion.css", $partial);
        // Versi aset mengikuti filemtime, bukan nomor manual.
        $this->assertStringContainsString('$fcAsset(', $partial);
    }

    public function test_motion_engine_guards_accessibility_and_failure_modes(): void
    {
        $js = $this->source('js/berkah-motion.js');

        foreach ([
            "prefers-reduced-motion: reduce",
            'matchMedia',
            'saveData',
            "pointer: fine",
            'fc-scroll-lock',
            'MutationObserver',
            'pagehide',
            'window.gsap',
            'window.ScrollTrigger',
            'window.Lenis',
            'data-bk-reveal',
            'data-bk-press',
            'data-bk-tilt',
            'window.BkMotion',
        ] as $needle) {
            $this->assertStringContainsString($needle, $js, "berkah-motion.js kehilangan: {$needle}");
        }
    }

    public function test_motion_styles_never_hide_content_without_javascript(): void
    {
        $css = $this->source('css/berkah-motion.css');

        // Keadaan tersembunyi hanya boleh bergantung pada kelas yang dipasang
        // skrip; tanpa JS tidak ada satu pun aturan opacity: 0 yang berlaku.
        $this->assertStringNotContainsString('opacity: 0;', $css);
        $this->assertStringContainsString('touch-action: manipulation', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('var(--bk-touch)', $css);
    }

    public function test_design_scales_are_shared_tokens(): void
    {
        $kit = $this->source('css/berkah-ui-kit.css');
        foreach (['--bk-fs-2xs: .75rem', '--bk-space-1: 4px', '--bk-space-10: 64px', '--bk-lh-body', '--bk-touch: 44px'] as $token) {
            $this->assertStringContainsString($token, $kit, "Token skala hilang: {$token}");
        }

        $admin = $this->source('css/berkah-admin.css');
        foreach (['--adm-fs-2xs: .75rem', '--adm-space-9: 48px', '--adm-touch: 44px', '--adm-safe-b'] as $token) {
            $this->assertStringContainsString($token, $admin, "Token skala admin hilang: {$token}");
        }

        // Batas bawah keterbacaan: tidak ada ukuran huruf di bawah 12px.
        preg_match_all('/font-size:\s*\.(\d+)rem/', $admin, $matches);
        foreach ($matches[1] as $fraction) {
            $rem = (float) ('0.'.$fraction);
            $this->assertGreaterThanOrEqual(
                0.75,
                $rem,
                "Ada teks di bawah 12px pada berkah-admin.css (font-size: .{$fraction}rem)."
            );
        }
    }
}
