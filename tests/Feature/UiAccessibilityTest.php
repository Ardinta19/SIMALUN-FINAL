<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pemeriksaan UI lintas peran yang tidak butuh database.
 *
 * Semua pemeriksaan membaca langsung berkas view/CSS, jadi tes ini juga
 * menjaga agar perbaikan aksesibilitas tidak hilang saat halaman baru
 * ditambahkan:
 *   1. halaman tidak mengunci zoom (user-scalable=no / maximum-scale),
 *   2. tidak ada sisa merek lama "Azka Laundry" pada view,
 *   3. setiap tautan tab baru (target="_blank") memakai rel,
 *   4. setiap gambar punya atribut alt,
 *   5. setiap halaman peran punya landmark main,
 *   6. CSS UI kit menyediakan cincin fokus keyboard,
 *   7. setiap halaman HTML punya atribut lang.
 */
class UiAccessibilityTest extends TestCase
{
    /** @var list<string>|null */
    private ?array $viewFiles = null;

    /**
     * @return list<string>
     */
    private function viewFiles(): array
    {
        if ($this->viewFiles !== null) {
            return $this->viewFiles;
        }

        $files = [];
        $base = base_path('resources/views');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $this->viewFiles = $files;
    }

    private function read(string $path): string
    {
        return (string) file_get_contents($path);
    }

    private function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }

    /**
     * Ambil potongan tag pembuka (mis. <img ...>) tanpa terpotong oleh
     * operator Blade/PHP `->` pada atribut.
     *
     * @return list<string>
     */
    private function openTags(string $content, string $name): array
    {
        $tags = [];
        $offset = 0;
        $length = strlen($content);
        $needle = '<'.$name;

        while (($start = strpos($content, $needle, $offset)) !== false) {
            $next = $content[$start + strlen($needle)] ?? ' ';
            // Pastikan bukan tag lain yang berawalan sama (mis. <imgx).
            if (! in_array($next, [' ', "\n", "\r", "\t", '>', '/'], true)) {
                $offset = $start + strlen($needle);

                continue;
            }

            $end = $start + strlen($needle);
            while ($end < $length) {
                if ($content[$end] === '>' && $content[$end - 1] !== '-') {
                    break;
                }
                $end++;
            }

            $tags[] = substr($content, $start, $end - $start + 1);
            $offset = $end + 1;
        }

        return $tags;
    }

    public function test_halaman_tidak_mengunci_zoom(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $content = $this->read($file);

            if (str_contains($content, 'user-scalable=no') || str_contains($content, 'maximum-scale')) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'Halaman berikut memblokir zoom: '.implode(', ', $offenders));
    }

    public function test_tidak_ada_sisa_merek_lama_pada_view(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            if (str_contains($this->read($file), 'Azka Laundry')) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'Merek lama masih tampil di: '.implode(', ', $offenders));
    }

    public function test_tautan_tab_baru_memakai_rel(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            foreach ($this->openTags($this->read($file), 'a') as $tag) {
                if (str_contains($tag, 'target="_blank"') && ! str_contains($tag, 'rel=')) {
                    $offenders[] = $this->relative($file).' → '.preg_replace('/\s+/', ' ', substr($tag, 0, 80));
                }
            }
        }

        $this->assertSame([], $offenders, "Tautan tab baru tanpa rel:\n".implode("\n", $offenders));
    }

    public function test_setiap_gambar_punya_teks_alternatif(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            foreach ($this->openTags($this->read($file), 'img') as $tag) {
                if (! str_contains($tag, 'alt=')) {
                    $offenders[] = $this->relative($file).' → '.preg_replace('/\s+/', ' ', substr($tag, 0, 80));
                }
            }
        }

        $this->assertSame([], $offenders, "Gambar tanpa alt:\n".implode("\n", $offenders));
    }

    public function test_halaman_peran_punya_landmark_main(): void
    {
        $base = base_path('resources/views/roles');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        $offenders = [];

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Partial (diawali _) dan view turunan layout tidak perlu landmark sendiri.
            if (str_starts_with($file->getFilename(), '_')) {
                continue;
            }

            $content = $this->read($file->getPathname());

            if (str_contains($content, '@extends(')) {
                continue;
            }

            if (preg_match('/<main\b/', $content) || str_contains($content, 'role="main"')) {
                continue;
            }

            $offenders[] = $this->relative($file->getPathname());
        }

        $this->assertSame([], $offenders, 'Halaman peran tanpa landmark main: '.implode(', ', $offenders));
    }

    public function test_css_ui_kit_menyediakan_cincin_fokus(): void
    {
        $css = $this->read(public_path('css/berkah-ui-kit.css'));

        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertMatchesRegularExpression(
            '/:is\([^)]*input[^)]*\):focus-visible\s*\{[^}]*outline:/s',
            $css,
            'Aturan fokus global harus memberi outline pada input.'
        );
    }

    public function test_setiap_halaman_html_punya_atribut_bahasa(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $content = $this->read($file);

            foreach ($this->openTags($content, 'html') as $tag) {
                if (! str_contains($tag, 'lang=')) {
                    $offenders[] = $this->relative($file);
                }
            }
        }

        $this->assertSame([], $offenders, 'Halaman tanpa atribut lang: '.implode(', ', $offenders));
    }
}
