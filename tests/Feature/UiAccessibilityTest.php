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
        // Windows memakai backslash: pesan galat dan pengecualian folder
        // (mis. `emails/`) harus tetap terbaca sama di kedua sistem.
        return str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
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
        $pattern = '/(?<![A-Za-z\$>.-])(?:reset|preview|upload|submit|save|cancel|delete|close|loading|search|login|logout|action|update|order|dashboard)(?![A-Za-z])/i';
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
    /** Tidak boleh ada aset pihak ketiga (CDN) di kepala halaman. Huruf merek
     *  dihosting sendiri: public/fonts + public/css/berkah-fonts.css, dipasang
     *  oleh _theme_head. Komentar blade dibuang dulu supaya catatan yang
     *  menyebut nama domain tidak dianggap tautan. */
    public function test_tidak_ada_aset_pihak_ketiga_di_view(): void
    {
        $hosts = ['fonts.googleapis.com', 'fonts.gstatic.com', 'fonts.bunny.net',
                  'cdn.jsdelivr.net', 'unpkg.com', 'cdnjs.cloudflare.com'];
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $text = preg_replace('/\{\{--.*?--\}\}/s', '', $this->read($file));

            foreach ($hosts as $host) {
                if (str_contains(strtolower($text), $host)) {
                    $offenders[] = $this->relative($file).': '.$host;
                }
            }
        }

        $this->assertSame([], $offenders, 'Aset pihak ketiga masih dimuat: '.implode(', ', $offenders));
    }

    /** Huruf merek: dua berkas woff2 variabel, satu stylesheet, dan setiap
     *  `url()` di dalamnya harus menunjuk berkas yang benar-benar ada. */
    public function test_huruf_merek_dihosting_sendiri(): void
    {
        $css = public_path('css/berkah-fonts.css');
        $this->assertFileExists($css);

        $text = File::get($css);
        $this->assertSame(2, substr_count($text, '@font-face'), 'Harus ada dua @font-face (latin, latin-ext).');
        $this->assertStringContainsString('font-display: swap', $text);
        $this->assertStringContainsString('font-weight: 200 800', $text);

        preg_match_all("/url\('([^']+)'\)/", $text, $m);
        $this->assertCount(2, $m[1], 'Setiap @font-face harus punya tepat satu url().');

        foreach ($m[1] as $relative) {
            // url('../fonts/x.woff2') dihitung dari public/css/, jadi '..' dibuang.
            $path = public_path(preg_replace('#^\.\./#', '', $relative));
            $this->assertFileExists($path, 'Berkas huruf hilang: '.$relative);
            $this->assertSame('wOF2', substr(File::get($path), 0, 4), 'Berkas bukan woff2: '.$relative);
        }
    }

    /** Setiap berkas di public/css dan public/js harus disebut minimal sekali
     *  oleh berkas lain (view atau skrip lain). Isi berkas itu sendiri tidak
     *  dihitung, jadi komentar yang menyebut namanya sendiri tidak menyelamatkan
     *  aset yatim. */
    public function test_setiap_aset_css_dan_js_punya_pemakai(): void
    {
        $sources = [];

        foreach (array_merge(File::allFiles(resource_path('views')), File::allFiles(public_path('js')), File::allFiles(public_path('css'))) as $file) {
            $sources[$file->getPathname()] = $file->getContents();
        }

        $offenders = [];

        foreach (['css', 'js'] as $folder) {
            foreach (File::files(public_path($folder)) as $file) {
                $name = $file->getFilename();
                $path = $file->getPathname();
                $referenced = false;

                foreach ($sources as $otherPath => $content) {
                    if ($otherPath === $path) {
                        continue;
                    }
                    if (str_contains($content, $name)) {
                        $referenced = true;
                        break;
                    }
                }

                if (! $referenced) {
                    $offenders[] = 'public/'.$folder.'/'.$name;
                }
            }
        }

        $this->assertSame([], $offenders, 'Aset tanpa pemakai: '.implode(', ', $offenders));
    }

    /** Setiap ekspresi Blade harus bisa diurai PHP.
     *
     *  `php -l` tidak memeriksa isi `{{ }}`: template baru dikompilasi saat
     *  halaman diminta, jadi kurung yang kurang baru muncul sebagai 500 di
     *  tes halaman — bukan saat berkas disimpan. Tokenizer dengan flag
     *  TOKEN_PARSE memberi pemeriksaan sintaks tanpa menjalankan kode. */
    public function test_semua_ekspresi_blade_bisa_diurai(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            // Komentar tidak pernah dikompilasi menjadi PHP.
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $this->read($file)) ?? '';

            preg_match_all('/\{\{(.*?)\}\}/s', $source, $echoed);
            preg_match_all('/\{!!(.*?)!!\}/s', $source, $raw);
            preg_match_all('/@php(.*?)@endphp/s', $source, $blocks);

            $snippets = [];

            // `{{ }}` dikompilasi menjadi echo yang sudah di-escape.
            foreach (array_merge($echoed[1], $raw[1]) as $expression) {
                $snippets[] = 'echo ('.$expression.');';
            }

            foreach ($blocks[1] as $block) {
                $snippets[] = $block;
            }

            foreach ($snippets as $snippet) {
                try {
                    token_get_all('<?php '.$snippet, TOKEN_PARSE);
                } catch (\ParseError $error) {
                    $offenders[] = $this->relative($file).': '.$error->getMessage();
                    break;
                }
            }
        }

        $this->assertSame([], $offenders, 'Ekspresi Blade tidak bisa diurai: '.implode(' | ', $offenders));
    }

    /** Form checkout mengirim berkas: tanpa enctype, foto referensi tidak
     *  pernah sampai ke server walau inputnya ada. */
    public function test_form_checkout_siap_mengunggah_foto_referensi(): void
    {
        $view = $this->read(resource_path('views/rental/checkout.blade.php'));

        $this->assertStringContainsString('enctype="multipart/form-data"', $view, 'Form checkout harus bisa mengirim berkas.');
        $this->assertStringContainsString('name="reference_images[]"', $view);
        $this->assertStringContainsString('accept="image/jpeg,image/png,image/webp"', $view);
        $this->assertMatchesRegularExpression('/name="reference_images\[\]"[^>]*multiple/', $view, 'Foto referensi harus bisa lebih dari satu.');
        $this->assertStringContainsString('25 MB per foto', $view, 'Petunjuk batas ukuran harus sama dengan aturan validasi (max:25600).');

        // Kontrol jumlah item tambahan tetap setinggi sasaran ketuk.
        $css = $this->read(public_path('css/berkah-experience.css'));
        $this->assertMatchesRegularExpression('/\.bk-addon__qty input \{[^}]*min-height:\s*var\(--bk-touch\)/s', $css);
    }

    /** Judul halaman memakai satu pemisah, ` · `, bukan campuran ` – ` dan
     *  ` | `. Surel dikecualikan: klien surel memakai nama merek saja. */
    public function test_judul_halaman_memakai_pemisah_titik_tengah(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $relative = $this->relative($file);
            if (str_contains($relative, 'emails/')) {
                continue;
            }

            preg_match_all('#<title>(.*?)</title>#s', $this->read($file), $m);

            foreach ($m[1] as $title) {
                if (str_contains($title, ' – ') || str_contains($title, ' | ')) {
                    $offenders[] = $relative.': '.$title;
                } elseif (! str_contains($title, ' · ') && ! str_contains($title, '@if')) {
                    $offenders[] = $relative.' (tanpa pemisah): '.$title;
                }
            }
        }

        $this->assertSame([], $offenders, 'Judul belum seragam: '.implode(', ', $offenders));
    }

    /** Netral dingin sisa tema biru (navy/slate/biru-kelabu) tidak boleh
     *  kembali ke view. Palet merek memakai cokelat hangat: tinta #321d24,
     *  tinta kedua #6f5960, tinta redup #806975, garis #f0e4e0, bayangan
     *  rgba(84,5,31,…). Nilai di daftar ini tetapan lama yang pernah
     *  tertinggal di halaman admin, halaman alamat, dan halaman profil. */
    public function test_tidak_ada_netral_dingin_sisa_tema_biru(): void
    {
        $cool = ['#112030', '#1a2332', '#46586c', '#3d5066', '#5f7082', '#e4ecf4', '#f0f5fa', '#d3ecfb', 'rgba(0,47,92'];
        $offenders = [];

        foreach ($this->viewFiles() as $file) {
            $text = strtolower($this->read($file));

            foreach ($cool as $value) {
                if (str_contains($text, $value)) {
                    $offenders[] = $this->relative($file).': '.$value;
                }
            }
        }

        $this->assertSame([], $offenders, 'Netral dingin masih terpakai: '.implode(', ', $offenders));
    }

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

            // getRelativePathname() memakai backslash di Windows; tanpa
            // normalisasi, nama view menjadi `auth\login` dan tidak pernah
            // cocok dengan `'auth.login'` — semua view dilaporkan yatim.
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $name = str_replace(['/', '.blade.php'], ['.', ''], $relative);
            $path = 'resources/views/'.$relative;

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

    /** Pesan galat isian harus diumumkan pembaca layar.
     *
     *  `.field-error` dan `.bk-field-error` dirender server setelah validasi
     *  gagal. Tanpa `role="alert"` atau `aria-live`, pembaca layar tidak
     *  memberi tahu apa pun: pengguna kembali ke atas formulir tanpa tahu
     *  kolom mana yang salah. Audit 2026-11-25 menemukan semua wadah galat di
     *  profil/alamat pelanggan dan checkout dalam keadaan bisu.
     */
    public function test_pesan_galat_isian_selalu_diumumkan(): void
    {
        $bisu = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());

            // Panah Blade (`$preview->id`) membuat `[^>]*` berhenti di tengah
            // ekspresi, jadi `->` diganti panah tipografis yang panjangnya sama
            // sebelum tag dipindai.
            $scan = str_replace('->', "\u{2192}", $html);

            preg_match_all('/<(?:div|p|span)\b[^>]*class\s*=\s*"[^"]*\b(?:field-error|bk-field-error|auth-error)\b[^"]*"[^>]*>/i', $scan, $found);

            foreach ($found[0] as $tag) {
                if (! preg_match('/\b(?:role\s*=\s*"alert"|aria-live\s*=)/i', $tag)) {
                    $bisu[] = $relative.' → '.$tag;
                }
            }
        }

        $this->assertSame([], $bisu, 'Pesan galat tanpa pengumuman: '.implode(' | ', $bisu));
    }

    /** Isian angka & telepon memakai papan ketik yang tepat di ponsel.
     *
     *  `type="number"` di Android/iOS masih memunculkan tombol `e`, `-`, dan
     *  `+` yang tidak berguna untuk nominal rupiah; `type="tel"` memunculkan
     *  papan ketik penuh. `inputmode="numeric"` dan `inputmode="tel"`
     *  mempersempit papan ketik tanpa mengubah nama field atau validasi.
     */
    public function test_isian_angka_dan_telepon_punya_inputmode(): void
    {
        $kurang = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            // Panah Blade diganti agar tag tidak terpotong di tengah ekspresi.
            $html = str_replace('->', "\u{2192}", (string) File::get($file->getPathname()));

            preg_match_all('/<input\b[^>]*>/i', $html, $found);

            foreach ($found[0] as $tag) {
                if (! preg_match('/type\s*=\s*"(?:number|tel)"/i', $tag)) {
                    continue;
                }

                if (! preg_match('/inputmode\s*=\s*"(?:numeric|decimal|tel)"/i', $tag)) {
                    $kurang[] = $relative.' → '.preg_replace('/\s+/', ' ', $tag);
                }
            }
        }

        $this->assertSame([], $kurang, 'Isian tanpa inputmode: '.implode(' | ', $kurang));
    }
}
