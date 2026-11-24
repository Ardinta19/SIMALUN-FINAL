<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
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
 *   7. setiap halaman HTML punya atribut lang,
 *   8. mode kontras paksa (Windows High Contrast) tetap punya jalur gambar.
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

    /** Satu istilah untuk satu hal: pesan validasi larangan memakai "kata
     *  sandi" (lang/id), jadi label dan tombol juga. "Password" yang berdiri
     *  sendiri sebagai kata (bukan nama variabel/atribut) dianggap sisa. */
    public function test_istilah_kata_sandi_konsisten_di_seluruh_view(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $lines = preg_split('/\R/', $this->read($file));

            foreach ($lines as $index => $line) {
                // Nama seperti updatePassword atau new-password tidak dihitung.
                if (preg_match('/(?<![A-Za-z])Password(?![A-Za-z])/', $line)) {
                    $offenders[] = $this->relative($file).':'.($index + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'Istilah “Password” masih tersisa: '.implode(', ', $offenders));
    }

    /** “Password” boleh hidup sebagai nama atribut atau nama variabel —
     *  begitu ia muncul di teks yang dibaca pengguna, istilahnya bercampur
     *  dengan “kata sandi” di kalimat sebelahnya. Pemindaian ini membuang
     *  komentar, direktif, dan atribut Blade lebih dulu, lalu menyisir teks
     *  yang benar-benar dirender (termasuk isi __('…')). */
    public function test_kata_password_tidak_tersisa_di_teks_yang_dibaca(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $source = $this->read($file);
            $text = $this->visibleText($source)."\n".$this->attributeText($source);

            if (preg_match('/(?<![A-Za-z])password(?![A-Za-z])/i', $text)) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'Kata “password” masih tampil di teks: '.implode(', ', $offenders));
    }

    /** Kata Inggris yang paling sering lolos dari templat lama (Reset,
     *  Preview, Upload, …). Tidak boleh muncul sebagai teks tampil, dan tidak
     *  boleh menjadi nilai kamus bahasa Indonesia — kamus yang isinya sama
     *  dengan kuncinya berarti belum diterjemahkan. */
    public function test_tidak_ada_label_inggris_tersisa_di_teks_atau_kamus(): void
    {
        $pattern = '/(?<![A-Za-z\$>.-])(?:reset|preview|upload|submit|save|cancel|delete|close|loading|search|login|logout|action|update|order)(?![A-Za-z])/i';
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $source = $this->read($file);

            if (preg_match($pattern, $this->visibleText($source)."\n".$this->attributeText($source))) {
                $offenders[] = $this->relative($file);
            }
        }

        // base_path, bukan lang_path: aplikasi ini menyimpan kamus di lang/.
        foreach (File::files(base_path('lang/id')) as $file) {
            preg_match_all("/=>\\s*'([^']*)'/", $file->getContents(), $matches);

            foreach ($matches[1] as $value) {
                if (preg_match($pattern, $value)) {
                    $offenders[] = 'lang/id/'.$file->getFilename().': '.$value;
                }
            }
        }

        $this->assertSame([], $offenders, 'Label Inggris masih tersisa: '.implode(', ', $offenders));
    }

    /** Label status/pembayaran/pratinjau ditulis sebagai peta di dalam view.
     *  Peta yang diindeks tanpa cadangan akan melempar "Undefined array key"
     *  begitu ada nilai baru (halaman 500), jadi setiap indeks wajib punya
     *  `?? $nilai` agar nilai tak dikenal tampil apa adanya. */
    public function test_peta_label_selalu_punya_cadangan(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $text = $this->read($file);

            if (preg_match_all('/\]\s*\[\s*(\$[A-Za-z_]+->(?:status|progress))\s*\]/', $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $after = substr($text, $match[1] + strlen($match[0]), 12);

                    if (! str_starts_with(ltrim($after), '??')) {
                        $offenders[] = $this->relative($file).': '.$match[0];
                    }
                }
            }
        }

        $this->assertSame([], $offenders, 'Peta label tanpa cadangan: '.implode(', ', $offenders));
    }

    /** View yang tidak pernah dirujuk menua tanpa ketahuan: warnanya,
     *  istilahnya, dan strukturnya tidak ikut berubah saat halaman lain
     *  dirapikan. Rujukan dihitung dari string literal di app/, routes/, dan
     *  view lain — tes tidak dihitung, karena tes yang menyebut sebuah view
     *  justru bisa membuat view mati terlihat hidup. Berkas yang dimuat
     *  Laravel sendiri (errors, vendor) dikecualikan; komponen dicek lewat
     *  pemakaian `<x-nama>`. */
    public function test_setiap_view_bisa_dijangkau(): void
    {
        $sources = '';

        foreach ([app_path(), resource_path('views'), base_path('routes')] as $dir) {
            foreach (File::allFiles($dir) as $file) {
                // Komentar tidak menjangkau view: menyebut nama view di dalam
                // komentar hanya membuat view mati terlihat hidup.
                $code = preg_replace('/\{\{--.*?--\}\}|\/\*.*?\*\//s', ' ', $file->getContents()) ?? '';
                $sources .= $code."\n";
            }
        }

        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            // Hanya view Blade; folder ini juga menyimpan peta ikon hasil
            // bangkitan (_icon_glyphs.php) yang dimuat lewat require.
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $name = str_replace(['/', '.blade.php'], ['.', ''], $file->getRelativePathname());
            $path = 'resources/views/'.$file->getRelativePathname();

            if (str_starts_with($name, 'errors.') || str_starts_with($name, 'vendor.')) {
                continue;
            }

            if (str_starts_with($name, 'components.')) {
                $tag = '<x-'.str_replace('.', '-', substr($name, strlen('components.')));

                if (! preg_match('/'.preg_quote($tag, '/').'\b/', $sources)) {
                    $offenders[] = $path;
                }

                continue;
            }

            if (! str_contains($sources, "'".$name."'") && ! str_contains($sources, '"'.$name.'"')) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders, 'View tanpa perujuk: '.implode(', ', $offenders));
    }

    /** Setiap kamus di lang/id harus punya pemakai. Kamus tanpa pemakai
     *  menua diam-diam: isinya bisa memakai istilah dari domain lain tanpa
     *  ada satu pun halaman yang terlihat salah. `validation` dikecualikan
     *  karena Laravel memuatnya sendiri berdasarkan nama berkas. */
    public function test_setiap_kamus_bahasa_punya_pemakai(): void
    {
        $sources = '';

        foreach ([app_path(), resource_path('views'), base_path('routes')] as $dir) {
            foreach (File::allFiles($dir) as $file) {
                $sources .= $file->getContents();
            }
        }

        $offenders = [];

        foreach (File::files(base_path('lang/id')) as $file) {
            $name = $file->getFilenameWithoutExtension();

            if ($name === 'validation') {
                continue;
            }

            if (! preg_match('/(?:__|trans|@lang)\(\s*[\'"]?'.preg_quote($name, '/').'\./', $sources)) {
                $offenders[] = 'lang/id/'.$file->getFilename();
            }
        }

        $this->assertSame([], $offenders, 'Kamus tanpa pemakai: '.implode(', ', $offenders));
    }

    /** Teks yang akan dibaca pengunjung: atribut, komentar, dan direktif
     *  dibuang; isi __('…')/@lang('…') dipertahankan karena ikut dirender. */
    private function visibleText(string $template): string
    {
        $text = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', ' ', $template) ?? '';
        $text = preg_replace('/@php\b.*?@endphp/s', ' ', $text) ?? '';
        $text = preg_replace('/<\?php\b.*?\?>/s', ' ', $text) ?? '';
        $text = preg_replace('/\{\{--.*?--\}\}/s', ' ', $text) ?? '';

        preg_match_all('/(?:__|@lang)\(\s*([\'"])(.*?)\1/s', $text, $matches);
        $strings = $matches[2] ?? [];

        $text = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', ' ', $text) ?? '';
        $text = preg_replace('/@\w+\s*(\((?:[^()]|\([^()]*\))*\))?/s', ' ', $text) ?? '';

        // Tag HTML boleh memuat ">" di dalam atribut (mis. $errors->get(…)).
        $text = preg_replace('/<(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/s', "\n", $text) ?? '';

        return $text."\n".implode("\n", $strings);
    }

    /** Nilai atribut yang ikut dibaca atau didengar pengunjung. Id, kelas,
     *  dan nama field tidak ikut — hanya teks yang benar-benar tampil. */
    private function attributeText(string $template): string
    {
        preg_match_all('/(?<![:\w-])(?:placeholder|aria-label|alt|title)\s*=\s*"([^"]*)"/i', $template, $matches);

        return implode("\n", $matches[1] ?? []);
    }

    /** High Contrast memaksa latar, bayangan, dan warna merek menjadi palet
     *  sistem. Tanpa aturan sendiri, busur, kotak centang, dan aksen catatan
     *  admin bisa menyatu dengan halaman. */
    public function test_mode_kontras_paksa_punya_jalur_gambar(): void
    {
        $radial = $this->read(resource_path('views/layouts/component/customer/_radial_customer.blade.php'));

        $this->assertStringContainsString('@media (forced-colors: active){', $radial);
        $this->assertStringContainsString('forced-color-adjust: none', $radial);
        $this->assertStringContainsString('background: ButtonFace; color: ButtonText;', $radial);
        $this->assertStringContainsString('border: 1px solid ButtonBorder; box-shadow: none;', $radial);
        $this->assertStringContainsString('background: Highlight; color: HighlightText; border-color: Highlight;', $radial);

        // Kotak centang kustom memakai SVG putih; di mode ini kontrolnya
        // dikembalikan ke rupa bawaan supaya tanda centang digambar sistem.
        $kit = $this->read(public_path('css/berkah-ui-kit.css'));
        $this->assertMatchesRegularExpression(
            '/@media \(forced-colors: active\)\s*\{[^@]*input\[type="checkbox"\][^{]*\{[^}]*appearance: auto;/s',
            $kit,
            'Kotak centang harus kembali ke rupa bawaan di mode kontras paksa.'
        );

        // Aksen kiri catatan admin tidak boleh hilang bersama latarnya.
        $admin = $this->read(public_path('css/berkah-admin.css'));
        $this->assertMatchesRegularExpression(
            '/@media \(forced-colors: active\)\s*\{.*?\.adm-note::before.*?background: ButtonText;/s',
            $admin,
            'Aksen catatan admin butuh warna sistem di mode kontras paksa.'
        );
        $this->assertStringContainsString('.adm-chip--link.is-active { background: Highlight;', $admin);
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
