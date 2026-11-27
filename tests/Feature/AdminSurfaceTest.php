<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menjaga "unslop" panel admin: seluruh jarak, warna, dan ukuran hidup di
 * public/css/berkah-admin.css, bukan di atribut style sebaris atau blok
 * <style> dalam view. Dengan begitu tema admin cukup diubah di satu tempat.
 *
 * Pengecualian yang sah: nilai yang baru diketahui saat render (mis. warna
 * varian dari database) tetap boleh lewat style sebaris karena tidak mungkin
 * ditulis sebagai kelas tetap.
 */
class AdminSurfaceTest extends TestCase
{
    /** @return array<string, string> nama berkas relatif => isi */
    private function adminViews(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->mapWithKeys(function ($file) {
                $path = str_replace('\\', '/', $file->getRelativePathname());
                // Komentar Blade/HTML bisa menyebut `<style>` atau `style="…"`
                // sebagai penjelasan; itu bukan kode yang dirender.
                $body = preg_replace(
                    ['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'],
                    ' ',
                    (string) file_get_contents($file->getPathname())
                ) ?? '';

                return [$path => $body];
            })
            ->filter(fn (string $body) => str_contains($body, "layouts.admin"))
            ->all();
    }

    private function adminCss(): string
    {
        return (string) file_get_contents(public_path('css/berkah-admin.css'));
    }

    public function test_view_admin_tidak_memakai_style_sebaris_kecuali_nilai_dinamis(): void
    {
        $views = $this->adminViews();
        $this->assertNotEmpty($views, 'Tidak ada view yang memakai layouts.admin.');

        $offenders = [];
        foreach ($views as $path => $body) {
            preg_match_all('/\sstyle="([^"]*)"/', $body, $matches);
            foreach ($matches[1] as $value) {
                // `{{ … }}` dan `{!! … !!}` berarti nilainya dinamis (warna,
                // lebar bar) — itu memang tidak bisa jadi kelas tetap.
                if (str_contains($value, '{{') || str_contains($value, '{!!')) {
                    continue;
                }
                $offenders[] = $path . ' → style="' . trim($value) . '"';
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)), 'Atribut style sebaris harus jadi kelas .adm-* di berkah-admin.css.');
    }

    public function test_view_admin_tidak_menyimpan_blok_style(): void
    {
        $offenders = [];
        foreach ($this->adminViews() as $path => $body) {
            if (preg_match('/<style[\s>]/i', $body)) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders, 'Gaya admin harus di public/css/berkah-admin.css, bukan di dalam view.');
    }

    public function test_judul_view_admin_tanpa_gaya_sebaris(): void
    {
        // Penjaga lama di UiDefaultsTest hanya membaca satu baris, sehingga
        // <h2> yang atributnya bersambung ke baris berikutnya lolos.
        $offenders = [];
        foreach ($this->adminViews() as $path => $body) {
            preg_match_all('/<h[1-6]\b[^>]*>/is', $body, $matches);
            foreach ($matches[0] as $tag) {
                if (preg_match('/\sstyle\s*=/i', $tag)) {
                    $offenders[] = $path . ' → ' . preg_replace('/\s+/', ' ', $tag);
                }
            }
        }

        $this->assertSame([], $offenders, 'Judul admin memakai kelas, bukan style sebaris.');
    }

    public function test_kelas_admin_yang_dipakai_view_benar_benar_ada(): void
    {
        $css = $this->adminCss();
        $missing = [];

        foreach ($this->adminViews() as $path => $body) {
            // Ekspresi Blade bisa berisi tanda kutip ganda, jadi disamarkan
            // dulu supaya class="… {{ $x ? 'a' : 'b' }} …" tetap terbaca.
            $masked = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', ' ', $body);
            preg_match_all('/\bclass="([^"]*)"/i', (string) $masked, $matches);

            foreach ($matches[1] as $list) {
                foreach (preg_split('/\s+/', $list) ?: [] as $class) {
                    $class = trim($class);
                    if ($class === '' || ! str_starts_with($class, 'adm-')) {
                        continue;
                    }
                    if (! str_contains($css, '.' . $class)) {
                        $missing[$class] = $path;
                    }
                }
            }
        }

        ksort($missing);
        $this->assertSame([], $missing, 'Ada kelas .adm-* yang dipakai view tapi tidak punya aturan di berkah-admin.css.');
    }

    public function test_kelas_utilitas_hasil_penyapuan_tersedia(): void
    {
        $css = $this->adminCss();

        // Kelas pengganti atribut style yang paling sering muncul; kalau salah
        // satunya terhapus, tata letak panel admin ikut rusak tanpa galat.
        foreach ([
            '.adm-flat',
            '.adm-min0',
            '.adm-strong',
            '.adm-wrapany',
            '.adm-push',
            '.adm-mt-3',
            '.adm-mt-6',
            '.adm-mt-gap',
            '.adm-divider-top',
            '.adm-divider-row',
            '.adm-meta',
            '.adm-block-gap',
            '.adm-row--top',
            '.adm-row--center',
            '.adm-row--between',
            '.adm-row--wrap',
            '.adm-row--tight',
            '.adm-list-indent',
            '.adm-thumb',
            '.adm-avatar-img',
            '.adm-avatar--lg',
            '.adm-preview-img',
            '.admin-photo',
            '.adm-amount-hero',
            '.adm-amount-hero__unit',
        ] as $selector) {
            $this->assertStringContainsString($selector . ' ', $css . ' ', "Kelas {$selector} hilang dari berkah-admin.css.");
        }
    }

    public function test_gaya_admin_tidak_memakai_ukuran_di_bawah_dua_belas_piksel(): void
    {
        $css = $this->adminCss();

        preg_match_all('/font-size\s*:\s*([^;}]+)/', $css, $declarations);
        $undersized = [];
        foreach ($declarations[1] as $value) {
            preg_match_all('/(\d*\.?\d+)(px|rem|em)/', $value, $units, PREG_SET_ORDER);
            foreach ($units as $unit) {
                $px = in_array($unit[2], ['rem', 'em'], true) ? (float) $unit[1] * 16 : (float) $unit[1];
                if ($px < 12) {
                    $undersized[] = trim($value);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($undersized)), 'Ada teks di bawah 12px pada berkah-admin.css.');
    }
}
