<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Satu tangga tipe untuk semua peran, dengan batas bawah 12px.
 *
 * Latar belakang: audit ritme visual menemukan ukuran teks lepas 9–11px
 * (.55–.74rem) yang ditulis langsung di CSS dan di dalam <style> view. Mata
 * cepat lelah dan label terlihat berdesakan. Perbaikan menyapukan seluruh
 * ukuran kecil ke token --bk-fs-* / --adm-fs-*; tes ini menjaga hasilnya:
 *
 *  1. kedua peran punya token yang sama nilainya,
 *  2. tidak ada lagi ukuran literal di bawah 12px (termasuk di dalam clamp),
 *  3. setiap referensi var() menunjuk token yang benar-benar didefinisikan,
 *  4. view memakai token, bukan angka lepas.
 *
 * Berkas email & PDF dikecualikan: klien surel tidak mengenal custom property,
 * jadi ukuran di sana memang harus literal.
 */
class TypographyScaleTest extends TestCase
{
    private const SCALE = [
        '--bk-fs-2xs' => '.75rem',
        '--bk-fs-xs' => '.8125rem',
        '--bk-fs-sm' => '.875rem',
        '--bk-fs-md' => '.9375rem',
        '--bk-fs-lg' => '1.0625rem',
    ];

    private const ADMIN_SCALE = [
        '--adm-fs-2xs' => '.75rem',
        '--adm-fs-xs' => '.8125rem',
        '--adm-fs-sm' => '.875rem',
        '--adm-fs-md' => '.9375rem',
        '--adm-fs-lg' => '1.0625rem',
    ];

    /** Berkas CSS aplikasi yang memakai token (bukan email/PDF). */
    private const CSS_FILES = [
        'berkah-ui-kit.css',
        'berkah-theme.css',
        'berkah-admin.css',
        'berkah-experience.css',
        'berkah-auth.css',
        'berkah-controls.css',
        'berkah-motion.css',
        'berkah-login-splash.css',
        'customer-forms.css',
    ];

    public function test_both_roles_share_the_same_type_scale(): void
    {
        $kit = File::get(public_path('css/berkah-ui-kit.css'));
        $admin = File::get(public_path('css/berkah-admin.css'));

        foreach (self::SCALE as $token => $value) {
            $this->assertStringContainsString(
                $token.': '.$value,
                $kit,
                'Token tipe bersama hilang atau berubah: '.$token
            );
        }

        foreach (self::ADMIN_SCALE as $token => $value) {
            $this->assertStringContainsString(
                $token.': '.$value,
                $admin,
                'Token tipe admin hilang atau berubah: '.$token
            );
        }

        // Batas bawah 12px = .75rem. Bila token terkecil dinaikkan, batas ini
        // ikut bergerak; yang dilarang adalah turun di bawahnya.
        $this->assertSame('.75rem', self::SCALE['--bk-fs-2xs']);
    }

    public function test_no_literal_text_size_below_twelve_pixels(): void
    {
        $problems = [];

        foreach ($this->sources() as $name => $css) {
            preg_match_all('/font-size\s*:\s*([^;{}]+)/i', $css, $declarations);

            foreach ($declarations[1] as $value) {
                // clamp() diperiksa lewat angka pertamanya (batas bawah nyata);
                // semua kandidat ukuran tetap dinilai supaya clamp(.5rem, …,
                // .62rem) tidak lolos hanya karena bentuknya clamp.
                foreach ($this->sizesIn($value) as $size) {
                    if ($size['rem'] !== null && $size['rem'] < 0.75) {
                        $problems[] = $name.' → '.trim($value).' ('.$size['raw'].')';
                    }

                    if ($size['px'] !== null && $size['px'] < 12) {
                        $problems[] = $name.' → '.trim($value).' ('.$size['raw'].')';
                    }
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($problems)),
            "Ada teks di bawah 12px (batas bawah keterbacaan):\n".implode("\n", array_unique($problems))
        );
    }

    public function test_every_type_scale_reference_is_defined(): void
    {
        $defined = array_merge(
            array_keys(self::SCALE),
            array_keys(self::ADMIN_SCALE)
        );

        $missing = [];

        foreach ($this->sources() as $name => $css) {
            preg_match_all('/var\(\s*(--(?:bk|adm)-fs-[a-z0-9-]+)/i', $css, $matches);

            foreach ($matches[1] as $token) {
                if (! in_array($token, $defined, true)) {
                    $missing[] = $name.' → '.$token;
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($missing)),
            "Referensi token tipe yang tidak didefinisikan:\n".implode("\n", array_unique($missing))
        );
    }

    public function test_views_write_type_with_tokens_not_loose_numbers(): void
    {
        $loose = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (str_starts_with($relative, 'emails/') || str_contains($relative, 'pdf')) {
                continue;
            }

            $text = File::get($file->getPathname());
            preg_match_all('/font-size\s*:\s*(?:0?\.\d+rem)/i', $text, $matches);

            foreach ($matches[0] as $literal) {
                // Ukuran kecil harus lewat token; angka lepas di bawah 1rem
                // adalah tanda view memilih ukurannya sendiri lagi.
                if (preg_match('/(?:0?\.\d+)rem/', $literal, $value) && (float) $value[1] < 1.0) {
                    $loose[] = $relative.' → '.preg_replace('/\s+/', ' ', $literal);
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($loose)),
            "View masih menulis ukuran teks lepas (pakai --bk-fs-* / --adm-fs-*):\n".implode("\n", array_unique($loose))
        );
    }

    /** @return array<string, string> nama sumber => isi CSS */
    private function sources(): array
    {
        $sources = [];

        foreach (self::CSS_FILES as $name) {
            $path = public_path('css/'.$name);
            if (File::exists($path)) {
                $sources['public/css/'.$name] = File::get($path);
            }
        }

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (str_starts_with($relative, 'emails/') || str_contains($relative, 'pdf')) {
                continue;
            }

            $text = File::get($file->getPathname());
            preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $text, $matches);

            foreach ($matches[1] as $index => $css) {
                $sources[$relative.'#style'.$index] = $css;
            }
        }

        return $sources;
    }

    /**
     * Semua ukuran di dalam satu nilai deklarasi font-size.
     *
     * @return list<array{raw: string, rem: float|null, px: float|null}>
     */
    private function sizesIn(string $value): array
    {
        $sizes = [];

        preg_match_all('/(\d*\.?\d+)(rem|px)\b/i', $value, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $amount = (float) $match[1];
            $unit = strtolower($match[2]);

            $sizes[] = [
                'raw' => $match[0],
                'rem' => $unit === 'rem' ? $amount : null,
                'px' => $unit === 'px' ? $amount : null,
            ];
        }

        return $sizes;
    }
}
