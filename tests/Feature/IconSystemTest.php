<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menjaga sistem ikon tetap satu keluarga, bersumber resmi, dan bebas emoji.
 *
 * Latar: seluruh ikon memakai Lucide. Geometrinya tidak lagi disalin tangan
 * ke dalam Blade, melainkan diambil dari paket `lucide-static` yang disalin ke
 * public/vendor/lucide/icons lalu dirakit menjadi _icon_glyphs.php oleh
 * tools/build-lucide-icons.mjs. Tes ini memastikan rantai itu tidak putus:
 * berkas sumber ada, peta glyph sinkron, semua nama yang dipakai view dapat
 * diselesaikan, dan tidak ada emoji pictograf yang tersisa di tampilan.
 */
class IconSystemTest extends TestCase
{
    private function glyphs(): array
    {
        $path = resource_path('views/layouts/component/_icon_glyphs.php');
        $this->assertFileExists($path, 'Peta glyph belum dibangkitkan.');

        $glyphs = require $path;
        $this->assertIsArray($glyphs);

        return $glyphs;
    }

    /** @return array<string, string> */
    private function aliases(): array
    {
        $blade = File::get(resource_path('views/layouts/component/_icon.blade.php'));
        $start = strpos($blade, '$aliases = [');
        $this->assertNotFalse($start, 'Peta alias hilang dari partial ikon.');
        $block = substr($blade, $start, strpos($blade, '];', $start) - $start);

        preg_match_all("/'([a-z0-9-]+)'\\s*=>\\s*'([a-z0-9-]+)'/", $block, $matches, PREG_SET_ORDER);

        $aliases = [];
        foreach ($matches as $match) {
            $aliases[$match[1]] = $match[2];
        }

        return $aliases;
    }

    /** @return array<int, string> */
    private function viewFiles(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();
    }

    public function test_glyphs_come_from_vendored_lucide_source(): void
    {
        $glyphs = $this->glyphs();
        $this->assertGreaterThanOrEqual(60, count($glyphs), 'Peta glyph terlalu sedikit.');

        foreach ($glyphs as $name => $markup) {
            $this->assertIsString($markup, "Glyph {$name} bukan string.");
            $this->assertStringContainsString('<', $markup, "Glyph {$name} tidak berisi markup SVG.");
        }

        // Sumber lokal + lisensi, bukan CDN.
        $this->assertFileExists(public_path('vendor/lucide/LICENSE'));
        $this->assertGreaterThanOrEqual(
            60,
            count(File::files(public_path('vendor/lucide/icons'))),
            'Berkas SVG sumber Lucide kurang lengkap.'
        );

        $partial = File::get(resource_path('views/layouts/component/_icon.blade.php'));
        $this->assertStringContainsString('_icon_glyphs.php', $partial);
        $this->assertStringNotContainsString('cdnjs', $partial);
        $this->assertStringNotContainsString('unpkg', $partial);
    }

    public function test_generated_map_matches_the_vendored_svgs(): void
    {
        $glyphs = array_keys($this->glyphs());
        $sources = collect(File::files(public_path('vendor/lucide/icons')))
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->sort()
            ->values()
            ->all();

        sort($glyphs);

        $this->assertSame(
            $sources,
            $glyphs,
            'Peta glyph tidak sinkron dengan public/vendor/lucide/icons. Jalankan: node tools/build-lucide-icons.mjs'
        );
    }

    public function test_every_icon_name_used_in_views_resolves(): void
    {
        $glyphs = $this->glyphs();
        $aliases = $this->aliases();

        foreach ($aliases as $concept => $glyph) {
            $this->assertArrayHasKey($glyph, $glyphs, "Alias '{$concept}' menunjuk glyph yang tidak ada: {$glyph}.");
        }

        $missing = [];
        foreach ($this->viewFiles() as $file) {
            $blade = File::get($file);
            preg_match_all(
                "/@include\\(\\s*'layouts\\.component\\._icon'\\s*,\\s*\\[\\s*'name'\\s*=>\\s*'([^']+)'/",
                $blade,
                $matches
            );

            foreach ($matches[1] as $name) {
                if (! array_key_exists($name, $glyphs) && ! array_key_exists($name, $aliases)) {
                    $missing[] = str_replace(resource_path('views').'/', '', $file).": {$name}";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), "Nama ikon tanpa glyph:\n".implode("\n", $missing));
    }

    public function test_views_no_longer_use_emoji_pictographs(): void
    {
        // Rentang yang dicakup: emotikon, simbol teknika, panah dekoratif, dan
        // variation selector. Panah tipografis biasa (← →) dan tanda × tidak
        // termasuk, jadi tetap boleh dipakai di teks.
        $pattern = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u';

        $offenders = [];
        foreach ($this->viewFiles() as $file) {
            $blade = File::get($file);
            if (preg_match_all($pattern, $blade, $matches)) {
                $offenders[] = str_replace(resource_path('views').'/', '', $file).': '.implode(' ', array_unique($matches[0]));
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Masih ada emoji di view; ganti dengan @include('layouts.component._icon', ...):\n".implode("\n", $offenders)
        );
    }

    public function test_designated_fallback_glyph_still_exists(): void
    {
        $glyphs = $this->glyphs();
        $partial = File::get(resource_path('views/layouts/component/_icon.blade.php'));

        $this->assertStringContainsString("\$fallbackGlyph = 'circle-dot'", $partial);
        $this->assertArrayHasKey('circle-dot', $glyphs);
    }
}
