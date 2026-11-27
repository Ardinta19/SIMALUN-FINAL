<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menjaga standar UI/UX default: seluruh halaman memakai komponen form
 * kustom (select, tanggal, bulan, stepper, segmented) dan animasi GSAP
 * lokal, termasuk splash halaman login.
 */
class UiDefaultsTest extends TestCase
{
    private const LAYOUTS_WITH_CONTROLS = [
        'catalog.layout',
        'layouts.guest',
        'errors.layout',
    ];

    private const SKIP_PATHS = [
        'emails/',
        'rental/receipt',
        'rental/daily-sheet',
        'rental/public-like',
    ];

    /** @return array<int, string> */
    private function bladeFiles(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            // Partial (mis. _ui_kit, _theme_head, _form_controls) bukan halaman
            // yang punya dokumen sendiri, walau komentarnya menyebut </body>.
            ->filter(fn ($file) => ! str_starts_with($file->getFilename(), '_'))
            ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            ->reject(fn (string $path) => collect(self::SKIP_PATHS)->contains(
                fn (string $skip) => str_starts_with($path, $skip) || $path === $skip
            ))
            ->values()
            ->all();
    }

    public function test_every_page_with_its_own_document_loads_the_shared_form_controls(): void
    {
        foreach ($this->bladeFiles() as $path) {
            $text = File::get(resource_path('views/'.$path));

            if (! str_contains($text, '</body>')) {
                continue;
            }

            $includes = str_contains($text, '_form_controls');
            $extends = preg_match("/@extends\('([^']+)'\)/", $text, $matches) === 1
                && in_array($matches[1], self::LAYOUTS_WITH_CONTROLS, true);

            $this->assertTrue(
                $includes || $extends,
                $path.' belum memuat layouts.component._form_controls.'
            );
        }
    }

    public function test_select_date_and_month_inputs_are_all_reached_by_the_kit(): void
    {
        $kit = File::get(resource_path('views/layouts/component/_form_controls.blade.php'));
        $script = File::get(public_path('js/berkah-controls.js'));

        $this->assertStringContainsString('berkah-controls.css', $kit);
        $this->assertStringContainsString('berkah-controls.js', $kit);
        $this->assertStringContainsString('vendor/gsap/gsap.min.js', $kit);
        $this->assertStringContainsString('?v=', $kit);
        $this->assertStringContainsString('filemtime', $kit);

        // Auto-enhancement: tidak bergantung pada atribut data-fc-* manual.
        $this->assertStringContainsString("each(scope, 'select'", $script);
        $this->assertStringContainsString('input[type="date"]', $script);
        $this->assertStringContainsString('input[type="month"]', $script);
        $this->assertStringContainsString('enhanceMonth', $script);
        $this->assertStringContainsString('enhanceSegmented', $script);
        $this->assertStringContainsString('enhanceStepper', $script);
    }

    public function test_custom_controls_are_accessible_and_animation_is_restrained(): void
    {
        $script = File::get(public_path('js/berkah-controls.js'));
        $css = File::get(public_path('css/berkah-controls.css'));

        foreach ([
            'prefers-reduced-motion',
            'preventScroll',
            'fc-scroll-lock',
            'data-fc-required',
            'aria-selected',
            "role: 'listbox'",
            "role: 'dialog'",
            'Escape',
            'window.initFormControls',
            'window.fcPick',
            'window.gsap',
        ] as $needle) {
            $this->assertStringContainsString($needle, $script, 'berkah-controls.js kehilangan: '.$needle);
        }

        // Sheet tidak boleh menggeser posisi baca: body dikunci dengan offset.
        $this->assertStringContainsString('position: fixed', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('.fc-month', $css);
        $this->assertStringContainsString('.fc-cal__day.is-selected', $css);
    }

    public function test_login_has_a_branded_splash_with_an_escape_hatch(): void
    {
        $login = File::get(resource_path('views/auth/login.blade.php'));
        $splashCss = File::get(public_path('css/berkah-login-splash.css'));
        $splashJs = File::get(public_path('js/berkah-login-splash.js'));

        $this->assertStringContainsString('id="bk-splash"', $login);
        $this->assertStringContainsString('Berkah Kreatif', $login);
        $this->assertStringContainsString('berkah-login-splash.css', $login);
        $this->assertStringContainsString('berkah-login-splash.js', $login);
        $this->assertStringContainsString('data-no-splash', $login);

        // Splash tampil di setiap kunjungan: tidak ada pembatas waktu per sesi.
        $this->assertStringNotContainsString('bk-login-splash-at', $login);
        $this->assertStringNotContainsString('sessionStorage', $login);
        $this->assertStringContainsString("get('splash')", $login);
        $this->assertStringContainsString('role="dialog"', $login);

        // Splash tidak boleh muncul ulang setelah galat validasi.
        $this->assertStringContainsString("\$errors->any()", $login);

        $this->assertStringContainsString('prefers-reduced-motion', $splashCss);
        $this->assertStringContainsString('.bk-splash__logo', $splashCss);
        $this->assertStringContainsString('timeline', $splashJs);
        $this->assertStringContainsString('inert', $splashJs);
        $this->assertStringContainsString("'Escape'", $splashJs);
        // Logo splash berputar satu putaran penuh.
        $this->assertStringContainsString('rotate: -360', $splashJs);
    }

    public function test_animation_library_is_served_locally_everywhere(): void
    {
        $this->assertFileExists(public_path('vendor/gsap/gsap.min.js'));

        foreach ($this->bladeFiles() as $path) {
            $this->assertStringNotContainsString(
                'cdnjs.cloudflare.com',
                File::get(resource_path('views/'.$path)),
                $path.' masih memuat aset dari CDN.'
            );
        }
    }

    public function test_map_library_is_served_locally(): void
    {
        foreach (['leaflet.js', 'leaflet.css', 'images/marker-icon.png', 'images/layers.png'] as $asset) {
            $this->assertFileExists(
                public_path('vendor/leaflet/'.$asset),
                'Aset peta belum dilokalkan: vendor/leaflet/'.$asset
            );
        }

        $this->assertFileExists(public_path('vendor/leaflet/LICENSE'));
        $this->assertFileExists(public_path('vendor/leaflet/VERSION.txt'));

        foreach (['roles/customer/addresses/create.blade.php', 'roles/customer/addresses/edit.blade.php'] as $path) {
            $blade = File::get(resource_path('views/'.$path));

            $this->assertStringContainsString('vendor/leaflet/leaflet.js', $blade, $path.' tidak memakai peta lokal.');
            $this->assertStringContainsString('vendor/leaflet/leaflet.css', $blade, $path.' tidak memakai gaya peta lokal.');
            $this->assertStringContainsString('filemtime(public_path(', $blade, $path.' belum memakai ?v=filemtime.');
            $this->assertStringNotContainsString('unpkg.com', $blade, $path.' masih memuat peta dari CDN.');
        }

        foreach ($this->bladeFiles() as $path) {
            $this->assertStringNotContainsString(
                'unpkg.com',
                File::get(resource_path('views/'.$path)),
                $path.' masih memuat aset dari unpkg.'
            );
        }
    }

    public function test_old_wine_shade_never_returns(): void
    {
        // #75102e adalah wine lama: nyaris kembar dengan #85092d, dan hanya
        // mencapai 1.5:1 bila jatuh di atas permukaan gelap. Seluruh pemakaian
        // sudah disatukan, jadi nilai ini tidak boleh muncul lagi.
        $offenders = [];

        $files = array_merge(File::allFiles(public_path('css')), File::allFiles(resource_path('views')));

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['css', 'php'], true)) {
                continue;
            }

            $source = File::get($file->getPathname());
            $source = preg_replace('!/\*.*?\*/!s', '', $source);
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);

            if (stripos((string) $source, '#75102e') !== false) {
                $offenders[] = str_replace('\\', '/', $file->getRelativePathname());
            }
        }

        $this->assertSame([], $offenders, 'Warna wine lama (#75102e) muncul lagi.');
    }

    public function test_browser_chrome_color_is_one_brand_value(): void
    {
        // Warna bilah peramban hanya boleh punya satu nilai merek; skrip tema
        // (berkah-ui-kit.js) yang menukarnya ke gelap dan memulihkannya.
        $definitions = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all('/<meta name="theme-color" content="([^"]+)"/', File::get($file->getPathname()), $matches);

            foreach ($matches[1] as $value) {
                $path = str_replace('\\', '/', $file->getRelativePathname());
                $definitions[$path][] = $value;
            }
        }

        $this->assertSame(
            ['layouts/component/_theme_head.blade.php' => ['#54051f']],
            $definitions,
            'theme-color harus didefinisikan sekali dengan warna merek wine.'
        );
    }

    public function test_admin_mobile_drawer_has_a_controller(): void
    {
        // Shell admin menyembunyikan sidebar di layar kecil dan membukanya
        // lewat tombol topbar. Tanpa pengendali di berkah-admin.js, tombol itu
        // mati dan seluruh menu admin tak bisa dijangkau di ponsel — cacat
        // yang dulu dijaga lewat lembar "Lainnya" pada bilah bawah mandiri.
        // Bilah itu sudah dicabut setelah kelima halaman pindah ke
        // `layouts.admin`, jadi penjaganya kini mengunci drawer sidebar.
        $topbar = File::get(resource_path('views/layouts/component/admin/_topbar.blade.php'));
        $sidebar = File::get(resource_path('views/layouts/component/admin/_sidebar.blade.php'));
        $js = File::get(public_path('js/berkah-admin.js'));

        $this->assertStringContainsString('data-adm-nav-toggle', $topbar);
        $this->assertStringContainsString('id="adm-sidebar"', $sidebar);
        $this->assertStringContainsString('data-adm-close', $sidebar);

        $this->assertStringContainsString("'.adm-sidebar'", $js);
        $this->assertStringContainsString("'[data-adm-nav-toggle]'", $js);
        $this->assertStringContainsString("'[data-adm-close]'", $js);
        $this->assertStringContainsString("setAttribute('aria-expanded'", $js);
    }

    public function test_shell_admin_memuat_modal_konfirmasi(): void
    {
        // Halaman profil admin memakai `showConfirmModal` untuk tombol keluar.
        // Tanpa `_confirm_modal` di shell, fungsinya tidak ada dan tombol
        // diam-diam langsung mengirim formulir — pengguna kehilangan
        // kesempatan membatalkan. (Modal ini juga dipakai dialog hapus lain
        // di halaman admin yang menyusul.)
        $layout = File::get(resource_path('views/layouts/admin.blade.php'));
        $profile = File::get(resource_path('views/roles/admin/profile.blade.php'));

        $this->assertStringContainsString("@include('layouts.component._confirm_modal')", $layout);
        $this->assertStringContainsString("window.showConfirmModal({", $profile);
        $this->assertStringContainsString('id="form-logout"', $profile);
        $this->assertStringContainsString("route('logout')", $profile);
    }

    public function test_label_isian_memakai_huruf_kecil_sesuai_kalimat(): void
    {
        // Judul halaman, judul kartu, dan tombol memakai kalimat biasa
        // ("Masuk sekarang"), jadi LABEL ISIAN pun memakai
        // huruf kecil sesuai kalimat — sama dengan "Kata sandi" di login dan
        // daftar. Halaman konfirmasi sandi dan profil pelanggan sempat memakai
        // "Kata Sandi Saat Ini", sehingga satu formulir punya dua gaya.
        $konfirmasi = File::get(resource_path('views/auth/confirm-password.blade.php'));
        $verifikasi = File::get(resource_path('views/auth/verify-email.blade.php'));
        $profil = File::get(resource_path('views/roles/customer/profile/edit.blade.php'));

        $this->assertStringContainsString("__('Kata sandi')", $konfirmasi);
        // "Email" adalah kata biasa dalam bahasa Indonesia (KBBI: surel/email),
        // jadi judul ini ikut huruf kecil kalimat.
        $this->assertStringContainsString("__('Verifikasi email kamu')", $verifikasi);

        foreach (['Kata sandi saat ini', 'Kata sandi baru', 'Konfirmasi kata sandi baru'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $profil, 'Label "'.$label.'" hilang dari profil pelanggan.');
        }

        $this->assertStringNotContainsString('>Kata Sandi', $profil, 'Label isian tidak boleh Judul Kapital.');
        $this->assertStringContainsString('>Ganti kata sandi<', $profil, 'Judul kartu ikut kalimat biasa.');
        $this->assertStringNotContainsString('>Ganti Kata Sandi<', $profil, 'Judul kartu tidak boleh Judul Kapital.');
    }

    public function test_semua_waktu_tampil_menyebut_wib(): void
    {
        // Setiap jam yang dibaca pengguna ditulis dalam WIB dan diberi label
        // zona. Jejak audit sempat menjadi satu-satunya halaman admin yang
        // mencetak `d/m/Y H:i` tanpa "WIB", padahal semua halaman lain
        // (pesanan, jadwal, pembatalan, buku kas) menyebutnya.
        $audit = File::get(resource_path('views/roles/admin/audit/index.blade.php'));

        $this->assertStringContainsString("timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB", $audit);
        $this->assertStringContainsString('datetime="{{ $log->created_at->toIso8601String() }}"', $audit, 'Waktu mesin harus tetap ISO untuk pembaca layar dan mesin.');
    }

    public function test_no_view_prints_the_word_array(): void
    {
        // Beberapa tes fitur memakai kata "Array" sebagai penanda bahwa suatu
        // halaman mencetak larik mentah (mis. old('name') saat isian dikirim
        // sebagai name[]). Penanda itu hanya berguna kalau kata tersebut tidak
        // pernah muncul sah di HTML — termasuk dari skrip sebaris. Karena itu
        // skrip memakai `[].forEach.call`, bukan nama konstruktor larik.
        $root = dirname(__DIR__, 2).'/resources/views';
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            // Komentar Blade dibuang saat kompilasi, jadi tidak ikut terkirim.
            $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file->getPathname()));
            if (str_contains($source, 'Array')) {
                $offenders[] = $file->getFilename();
            }
        }

        $this->assertSame([], $offenders, 'Kata "Array" akan mengacaukan penanda tes.');
    }

    public function test_buttons_inside_forms_declare_their_type(): void
    {
        // <button> tanpa type di dalam <form> selalu berarti submit. Itu tidak
        // salah untuk tombol simpan, tetapi membuat tombol bantu (tutup, pilih
        // berkas, aksi JS) ikut mengirim formulir secara tak sengaja. Menulis
        // type secara eksplisit mengunci niat dan membuat tinjauan mudah.
        $root = dirname(__DIR__, 2).'/resources/views';
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            // "->" di dalam atribut (mis. $product->name) bukan penutup tag.
            $source = str_replace('->', '\u{2794}', (string) file_get_contents($file->getPathname()));
            if (! preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $source, $forms)) {
                continue;
            }
            foreach ($forms[1] as $body) {
                if (preg_match_all('/<button\b(?![^>]*\btype=)[^>]*>/i', $body, $matches)) {
                    foreach ($matches[0] as $tag) {
                        $offenders[] = $file->getPathname().' → '.preg_replace('/\s+/', ' ', $tag);
                    }
                }
            }
        }

        $this->assertSame([], $offenders, 'Tombol di dalam formulir perlu type eksplisit.');
    }

    public function test_the_old_second_bottom_sheet_is_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/layouts/component/_bottom_sheet_select.blade.php'));

        foreach ($this->bladeFiles() as $path) {
            $this->assertStringNotContainsString(
                'showBottomSelect',
                File::get(resource_path('views/'.$path)),
                $path.' masih memakai bottom sheet lama.'
            );
        }
    }

    /** Bilah peramban mengikuti tema sejak lukisan pertama.
     *
     *  `<meta name="theme-color">` hanya diganti oleh `berkah-ui-kit.js`.
     *  Halaman masuk dan daftar tidak memuat skrip itu, dan halaman lain pun
     *  sempat menampilkan bilah merah merek di atas halaman gelap sampai
     *  skrip selesai. Sekarang skrip pra-lukis di `_theme_head` yang mengatur.
     */
    public function test_bilah_peramban_mengikuti_tema_saat_lukisan_pertama(): void
    {
        $head = File::get(resource_path('views/layouts/component/_theme_head.blade.php'));

        $this->assertStringContainsString('<meta name="theme-color" content="#54051f">', $head);
        $this->assertStringContainsString("bar.setAttribute('content', dark ? '#140f12' : '#54051f')", $head);

        // Nilai gelap di skrip pra-lukis dan di berkah-ui-kit.js harus sama,
        // supaya tidak berkedip saat sakelar tema dipakai.
        $kit = File::get(public_path('js/berkah-ui-kit.js'));
        $this->assertStringContainsString("const DARK_THEME_COLOR = '#140f12';", $kit);
    }

    /** Satu bentuk tanggal-jam untuk halaman yang menyebut jam.
     *
     *  Seluruh panel admin dan halaman pesanan memakai `d/m/Y H:i` (tanggal
     *  hari ini cukup `d/m H:i`). Notifikasi admin sempat memakai
     *  `translatedFormat('d M Y H:i')` — "22 Nov 2026 14.30" di antara
     *  "22/11/2026 14:30" pada halaman lain. Campuran itu membuat kolom
     *  waktu tidak sejajar dan lebih sulit dipindai.
     */
    /** Subjudul kartu unit memakai jarak yang sama meski tingkatnya h3.
     *
     *  `rental/unit` dulu memakai h4 (melompat dari h2) sehingga bergantung
     *  pada aturan `.unit-section h4`. Setelah naik ke h3, aturan jarak harus
     *  mencakup keduanya — kalau tidak, subjudul baru menempel ke paragraf
     *  di atasnya.
     */
    public function test_jarak_subjudul_kartu_unit_mencakup_h3(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertStringContainsString('.unit-section :is(h3, h4) {', $css);
        $this->assertStringContainsString('.unit-section :is(h3, h4):first-of-type {', $css);
    }

    public function test_format_tanggal_jam_seragam(): void
    {
        $izin = ['d/m/Y H:i', 'd/m H:i'];
        $menyimpang = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());
            preg_match_all("/(?:->format|translatedFormat|isoFormat)\(\s*'([^']*H:i[^']*)'/", $html, $found);

            foreach (array_unique($found[1]) as $format) {
                if (! in_array($format, $izin, true)) {
                    $menyimpang[] = $relative.' → '.$format;
                }
            }
        }

        $this->assertSame([], $menyimpang, implode(' | ', $menyimpang));
    }

    /** Satu sapaan untuk seluruh aplikasi.
     *
     *  Copy pelanggan memakai "kamu" (30 kali) tetapi 13 tempat masih
     *  "Anda", tiga di antaranya bercampur dalam satu halaman
     *  (`rental/unit`, `rental/show`): "Anda dapat membayar bertahap" lalu
     *  "Kamu masih dapat mengirim bukti". Nada yang berganti di tengah
     *  halaman terbaca seperti dua orang yang menulis. Sekarang semua
     *  "kamu", termasuk halaman verifikasi email, konfirmasi sandi, dan
     *  dialog konfirmasi.
     */
    public function test_sapaan_pengguna_seragam(): void
    {
        $menyimpang = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());

            if (preg_match_all('/\bAnda\b/', $html, $found) > 0) {
                $menyimpang[] = $relative.' ('.count($found[0]).'×)';
            }

            $kamu = preg_match_all('/\b[Kk]amu\b/', $html);
            $anda = count($found[0]);

            if ($kamu > 0 && $anda > 0) {
                $menyimpang[] = $relative.' mencampur "Anda" dan "kamu"';
            }
        }

        $this->assertSame([], $menyimpang, 'Sapaan tidak seragam: '.implode(', ', $menyimpang));
    }

    /** Setiap kolom yang divalidasi punya nama Indonesia.
     *
     *  Laravel memakai kunci mentah sebagai `:attribute` kalau tidak ada di
     *  `lang/id/validation.php`. Akibatnya pelanggan membaca
     *  "units.0.event_on wajib diisi." dan admin membaca "entry_date wajib
     *  diisi." — 22 kolom dalam keadaan itu sebelum audit 2026-11-25.
     *  Sekarang semua kunci `validate()`/`validateWithBag()` di `app/`
     *  memiliki label, termasuk kunci bertingkat `units.*.<field>`.
     */
    public function test_setiap_kolom_validasi_punya_label_indonesia(): void
    {
        $kunci = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $php = (string) File::get($file->getPathname());

            preg_match_all("/validate(?:WithBag)?\(\s*(?:\[[^\]]*\]\s*,\s*)?\[(.*?)\]\s*(?:,|\))/s", $php, $blok);

            foreach ($blok[1] as $isi) {
                preg_match_all("/'([a-z0-9_.*]+)'\s*=>/", $isi, $fields);

                foreach ($fields[1] as $field) {
                    $kunci[$field] = true;
                }
            }
        }

        $lang = File::get(base_path('lang/id/validation.php'));
        preg_match_all("/^\s*'([a-z0-9_.*]+)'\s*=>/m", $lang, $found);

        $tanpaLabel = array_values(array_diff(array_keys($kunci), array_unique($found[1])));

        $this->assertSame([], $tanpaLabel, 'Kolom tanpa label Indonesia: '.implode(', ', $tanpaLabel));
    }

    /** Judul tidak membawa jarak sebaris.
     *
     *  Delapan belas judul di panel admin menulis `style="margin:0"`,
     *  `style="margin:20px 0 4px"`, dan seterusnya. `.adm-app h2/h3` sudah
     *  bermargin 0, jadi sebagian besar itu mubazir; sisanya angka lepas
     *  (2/4/8/12/16/20/24 px) yang tidak mengikuti tangga `--adm-space-*`.
     *  Sekarang semua jarak judul datang dari kelas, bukan atribut.
     */
    public function test_judul_tidak_membawa_gaya_sebaris(): void
    {
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());
            $html = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $html);

            if (preg_match_all('/<h[1-6]\b[^>]*\bstyle\s*=/', $html, $found)) {
                $pelanggaran[] = $relative.' → '.count($found[0]).' judul bergaya sebaris';
            }
        }

        $this->assertSame([], $pelanggaran, implode('; ', $pelanggaran));

        $css = File::get(public_path('css/berkah-admin.css'));
        $this->assertStringContainsString('.adm-app h4 {', $css, 'h4 butuh aturan sendiri; tanpa itu ia memakai margin bawaan peramban.');
        $this->assertStringContainsString('.adm-section-title {', $css);
        $this->assertStringContainsString('.adm-subhead {', $css);
    }

    /** Judul halaman, judul bagian, dan tombol memakai huruf kecil kalimat.
     *
     *  Satu halaman menulis "Simpan Data Pribadi", "Edit Alamat Utama",
     *  "Hapus Foto Profil", dan "Nonaktifkan Akun" sementara seluruh aplikasi
     *  memakai kalimat biasa ("Simpan perubahan", "Ganti kata sandi"). Begitu
     *  pula h1 "Selamat Datang", "Buat Akun Baru", dan "Tambah Alamat".
     *  Akronim (PDF, QRIS) dan nama merek tetap boleh berhuruf besar.
     */
    public function test_judul_dan_tombol_memakai_huruf_kecil_kalimat(): void
    {
        $pelanggaran = [];
        $penutup = ['PDF', 'QRIS', 'WhatsApp', 'SIMALUN', 'Berkah', 'Kreatif'];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());

            preg_match_all('/<(h[1-4]|button|summary)\b[^>{}]*>([^<>{}]{4,60})</', $html, $found, PREG_SET_ORDER);

            foreach ($found as $item) {
                $text = trim($item[2]);

                if (str_starts_with($text, '{{') || str_contains($text, '@')) {
                    continue;
                }

                $kata = array_values(array_filter(
                    preg_split('/[\s\/·]+/', $text),
                    fn ($k) => preg_match('/[A-Za-z]/', (string) $k)
                ));

                if (count($kata) < 2) {
                    continue;
                }

                $huruf = array_values(array_filter($kata, fn ($k) => ! in_array($k, $penutup, true) && ! ctype_upper($k) && mb_strlen($k) > 2));

                if (count($huruf) < 2) {
                    continue;
                }

                // Nama merek di awal judul sah ("Berkah Kreatif · …").
                if (in_array($kata[0], $penutup, true)) {
                    continue;
                }

                if (collect($huruf)->every(fn ($k) => ctype_upper(mb_substr($k, 0, 1)))) {
                    $pelanggaran[] = $relative.' → '.$item[1].' "'.$text.'"';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($pelanggaran)), implode(' | ', array_values(array_unique($pelanggaran))));
    }

    /** Halaman akun dan panel admin tidak boleh masuk mesin pencari.
     *
     *  Tata letak katalog dipakai bersama oleh etalase publik dan halaman akun
     *  (keranjang, pesanan, profil). Etalase harus tetap terindeks, halaman akun
     *  tidak — jadi meta `noindex` hanya dirender untuk pengguna yang masuk.
     *  Panel admin memuat data pelanggan, jadi selalu `noindex`.
     */
    public function test_halaman_akun_tidak_diindeks(): void
    {
        $admin = File::get(resource_path('views/layouts/admin.blade.php'));
        $catalog = File::get(resource_path('views/catalog/layout.blade.php'));

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $admin);
        $this->assertStringContainsString('@auth<meta name="robots" content="noindex">@endauth', $catalog);
        $this->assertStringNotContainsString('<meta name="robots" content="noindex">@endif', $catalog);

        foreach (['auth/login.blade.php', 'auth/register.blade.php', 'auth/forgot-password.blade.php', 'layouts/guest.blade.php'] as $relative) {
            $this->assertStringContainsString('noindex', File::get(resource_path('views/'.$relative)), $relative.' harus noindex.');
        }
    }

    /** Pesan validasi tidak boleh menampilkan nama kolom mentah.
     *
     *  Laravel menyisipkan `:attribute` dari `lang/id/validation.php`. Kalau
     *  kolomnya tidak dipetakan, pelanggan membaca "kolom proof wajib diisi"
     *  atau "kolom gender wajib diisi" — nama variabel, bukan bahasa manusia.
     */
    public function test_pesan_validasi_memakai_nama_kolom_indonesia(): void
    {
        $lang = File::get(base_path('lang/id/validation.php'));

        $wajib = [
            'gender' => 'jenis kelamin',
            'avatar' => 'foto profil',
            'proof' => 'bukti pembayaran',
            'greeting' => 'ucapan',
            'design_notes' => 'catatan desain',
            'delivery_address' => 'alamat pengantaran',
            'return_address' => 'alamat pengambilan kembali',
            'latitude' => 'titik peta',
            'longitude' => 'titik peta',
            'voucher_code' => 'kode voucher',
            'price' => 'harga',
            'stock' => 'stok',
            'whatsapp_number' => 'nomor WhatsApp',
        ];

        foreach ($wajib as $kolom => $nama) {
            $this->assertMatchesRegularExpression(
                "/'".preg_quote($kolom, '/')."'\s*=>\s*'".preg_quote($nama, '/')."'/",
                $lang,
                'Kolom "'.$kolom.'" belum punya nama Indonesia di pesan validasi.'
            );
        }

        // Sisa-sisa aplikasi lama (laundry) tidak boleh muncul lagi: pesan
        // seperti "Layanan laundry wajib dipilih" tidak ada hubungannya dengan
        // pemesanan papan bunga.
        $this->assertStringNotContainsStringIgnoringCase('laundry', $lang);
        $this->assertStringNotContainsString("'pickup_date'", $lang);
        $this->assertStringNotContainsString("'weight_estimate'", $lang);
    }

    /** Tidak ada kalimat Inggris yang bocor dari kerangka Laravel.
     *
     *  `ConfirmablePasswordController` memakai `__('auth.password')` dan
     *  `ResetPasswordController` memakai `__($status)` dari broker
     *  (`passwords.token`, `passwords.throttled`, …). Selama `lang/id` belum
     *  punya berkas itu, Laravel jatuh ke `fallback_locale` = en: halaman
     *  konfirmasi kata sandi menampilkan "This password is incorrect." dan
     *  halaman reset bisa menampilkan kunci mentah.
     */
    public function test_pesan_kerangka_laravel_tersedia_dalam_bahasa_indonesia(): void
    {
        foreach (['auth' => ['failed', 'password', 'throttle'], 'passwords' => ['reset', 'sent', 'throttled', 'token', 'user']] as $berkas => $kunci) {
            $path = base_path('lang/id/'.$berkas.'.php');

            $this->assertFileExists($path, 'lang/id/'.$berkas.'.php belum ada; Laravel akan memakai bahasa Inggris.');

            $isi = File::get($path);

            foreach ($kunci as $k) {
                $this->assertMatchesRegularExpression("/'".$k."'\s*=>/", $isi, 'Kunci '.$berkas.'.'.$k.' belum diterjemahkan.');
            }
        }

        // Pesan bawaan login ikut memakai istilah aplikasi ini.
        $login = File::get(app_path('Http/Controllers/Auth/LoginController.php'));
        $this->assertStringContainsString("'Kata sandi wajib diisi'", $login);
        $this->assertStringNotContainsString("'Password wajib diisi'", $login);
    }

    /** Halaman galat memakai bahasa Indonesia dan kalimat biasa.
     *
     *  "Server Error" adalah satu-satunya judul berbahasa Inggris di halaman
     *  galat, sementara judul lain memakai Judul Kapital ("Akses Ditolak")
     *  padahal seluruh aplikasi memakai kalimat biasa. Status 429 juga belum
     *  punya halaman sendiri, sehingga pengguna yang kena batas percobaan
     *  masuk melihat halaman bawaan Laravel yang tanpa merek.
     */
    public function test_halaman_galat_berbahasa_indonesia(): void
    {
        $harapan = [
            '403' => 'Akses ditolak',
            '404' => 'Halaman tidak ditemukan',
            '413' => 'Unggahan terlalu besar',
            '419' => 'Sesi kedaluwarsa',
            '429' => 'Terlalu banyak percobaan',
            '500' => 'Kesalahan server',
            '503' => 'Sedang pemeliharaan',
        ];

        foreach ($harapan as $code => $judul) {
            $path = resource_path('views/errors/'.$code.'.blade.php');

            $this->assertFileExists($path, 'Halaman galat '.$code.' belum ada.');

            $isi = File::get($path);
            $this->assertStringContainsString('$title = \''.$judul.'\'', $isi, 'Judul '.$code.' harus "'.$judul.'".');

            if (preg_match("/\$title = '([^']+)'/", $isi, $m)) {
                $this->assertStringNotContainsStringIgnoringCase('error', $m[1], 'Judul galat tidak boleh berbahasa Inggris.');
            }
        }

        // robots.txt menutup area akun dan panel admin.
        $robots = File::get(public_path('robots.txt'));
        foreach (['/admin', '/dashboard', '/customer', '/keranjang', '/checkout'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path, $robots, 'robots.txt harus menutup '.$path.'.');
        }
        $this->assertStringContainsString('Allow: /', $robots);
    }

    /** Logo dan ikon situs memakai berkas yang sudah diperkecil.
     *
     *  `berkah-kreatif-logo.jpeg` berukuran 3460x1892 dan 564 KB, tetapi tidak
     *  pernah ditampilkan lebih dari 108 px (logo header 44–52 px). Setiap
     *  halaman memuatnya — termasuk dua kali di header pelanggan — sehingga di
     *  jaringan seluler gambar itu menghabiskan sebagian besar muatan awal.
     *  Turunan 104 px (7 KB), 216 px untuk splash login (22 KB), dan ikon
     *  32/180 px dibuat ulang dari berkas yang sama.
     */
    public function test_logo_memakai_berkas_ringkas_dan_ikon_situs_seragam(): void
    {
        $head = File::get(resource_path('views/layouts/component/_theme_head.blade.php'));

        foreach (['icon-32.png', 'icon-180.png'] as $berkas) {
            $path = public_path('images/'.$berkas);

            $this->assertFileExists($path, $berkas.' belum ada.');
            $this->assertLessThan(60 * 1024, filesize($path), $berkas.' terlalu besar untuk sebuah ikon.');
            $this->assertStringContainsString($berkas, $head, $berkas.' belum dirujuk di kepala halaman.');
        }

        $this->assertLessThanOrEqual(32 * 1024, filesize(public_path('images/logo-104.png')),
            'Logo header harus di bawah 32 KB (tampil 44–52 px).');

        $jpeg = public_path('images/berkah-kreatif-logo.jpeg');
        $this->assertSame([], array_values(array_unique($this->viewsMemakaiLogoBesar())),
            'Masih ada view yang memuat JPEG logo 564 KB; pakai images/logo-104.png (atau logo-216.png untuk splash).');
    }

    /** @return array<int, string> */
    private function viewsMemakaiLogoBesar(): array
    {
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (str_contains((string) File::get($file->getPathname()), 'images/berkah-kreatif-logo.jpeg')) {
                $pelanggaran[] = str_replace('\\', '/', $file->getRelativePathname());
            }
        }

        return $pelanggaran;
    }

    /** Sapaan "kamu" dipakai di seluruh aplikasi, termasuk notifikasi.
     *
     *  Dua pesan notifikasi masih menyapa "Anda" setelah halaman lain
     *  diseragamkan, sehingga satu pelanggan bisa membaca dua sapaan berbeda
     *  pada hari yang sama.
     */
    public function test_sapaan_seragam_kamu(): void
    {
        $pelanggaran = [];

        foreach (array_merge(File::allFiles(resource_path('views')), File::allFiles(app_path())) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php') && ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $isi = (string) File::get($file->getPathname());

            if (preg_match('/\bAnda\b/', $isi)) {
                $pelanggaran[] = str_replace('\\', '/', $file->getRelativePathname());
            }
        }

        $this->assertSame([], array_values(array_unique($pelanggaran)), 'Masih memakai sapaan "Anda".');
    }

    /** Kolom teks panjang cukup tinggi sejak awal.
     *
     *  `<textarea>` tanpa `rows` hanya setinggi dua baris. Untuk ucapan,
     *  catatan desain, dan alamat lengkap, pelanggan harus menggulir kotak
     *  kecil sebelum bisa membaca kembali tulisannya sendiri — di ponsel itu
     *  pembatas gulir halaman mudah tertukar dengan gulir di dalam kotak.
     */
    public function test_kolom_teks_panjang_punya_tinggi_awal(): void
    {
        $panjang = ['greeting', 'design_notes', 'full_address', 'delivery_address', 'return_address', 'reason', 'note', 'instructions'];
        $kurang = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());
            $scan = str_replace('->', "\u{2192}", $html);

            preg_match_all('/<textarea\b[^>]*>/i', $scan, $found);

            foreach ($found[0] as $tag) {
                if (! preg_match('/name="([^"]+)"/', $tag, $m)) {
                    continue;
                }

                $base = preg_replace('/^.*[.\[]/', '', rtrim($m[1], ']'));

                if (! in_array($base, $panjang, true)) {
                    continue;
                }

                if (! preg_match('/rows="([3-9]|\d{2,})"/', $tag)) {
                    $kurang[] = $relative.' → '.$base;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($kurang)), 'Kolom teks panjang butuh rows>=3: '.implode(', ', array_unique($kurang)));
    }

    /** Kolom pencarian memakai `type="search"`.
     *
     *  Dengan `type="search"`, papan ketik ponsel menampilkan tombol "Cari"
     *  dan peramban memberi tombol bersihkan; dengan `type` bawaan (teks)
     *  keduanya tidak muncul dan pengguna harus menghapus kata kunci manual.
     */
    public function test_kolom_pencarian_memakai_type_search(): void
    {
        $kurang = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());

            preg_match_all('/<input\b[^>]*>/i', $html, $found);

            foreach ($found[0] as $tag) {
                $pencarian = str_contains($tag, 'data-adm-search') || preg_match('/name="q"/', $tag);

                if ($pencarian && ! str_contains($tag, 'type="search"')) {
                    $kurang[] = $relative.' → '.substr($tag, 0, 70);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($kurang)), 'Kolom pencarian tanpa type=search.');
    }

    /** Tautan bergaya tombol dan teks terjemahan juga kalimat biasa.
     *
     *  `test_judul_dan_tombol_memakai_huruf_kecil_kalimat` hanya memeriksa
     *  `<h1>`–`<h4>`, `<button>`, dan `<summary>`. Tautan bergaya tombol
     *  ("Buat Akun", "Masuk Sekarang") serta string `__()` yang dirender
     *  lewat `{{ }}` lolos dari pemeriksaan itu, padahal terlihat sama
     *  menonjol di mata pelanggan.
     */
    public function test_tautan_tombol_dan_teks_terjemahan_memakai_kalimat_biasa(): void
    {
        $nama = ['PDF', 'QRIS', 'WhatsApp', 'Instagram', 'Facebook', 'Google', 'Maps', 'SIMALUN', 'Berkah', 'Kreatif', 'Jambi', 'Halo', 'Toko', 'Bank', 'BCA', 'BRI', 'BNI', 'Mandiri', 'DANA', 'OVO', 'GoPay', 'QR', 'ID', 'AI', 'CSV'];
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());

            $kandidat = [];

            preg_match_all('/<a\b[^>]*class="[^"]*\bbtn[^"]*"[^>]*>([^<>{}]{4,60})</', $html, $tautan, PREG_SET_ORDER);
            foreach ($tautan as $item) {
                $kandidat[] = [$item[0], $item[1]];
            }

            preg_match_all("/__\(\s*'([^']{4,60})'\s*\)/", $html, $terjemah, PREG_SET_ORDER);
            foreach ($terjemah as $item) {
                $kandidat[] = [$item[0], $item[1]];
            }

            foreach ($kandidat as [$jejak, $text]) {
                $text = trim(preg_replace('/&[a-z]+;/', '', $text));

                $kata = array_values(array_filter(
                    preg_split('/[\s\/·—]+/u', $text),
                    fn ($k) => preg_match('/[A-Za-z]/', (string) $k)
                ));

                $huruf = array_values(array_filter($kata, fn ($k) => ! in_array($k, $nama, true) && ! ctype_upper($k) && mb_strlen($k) > 2));

                if (count($huruf) < 2 || in_array($kata[0] ?? '', $nama, true)) {
                    continue;
                }

                if (collect($huruf)->every(fn ($k) => ctype_upper(mb_substr($k, 0, 1)))) {
                    $pelanggaran[] = $relative.' → "'.$text.'"';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($pelanggaran)), implode(' | ', array_values(array_unique($pelanggaran))));
    }

    /** Semua label isian memakai kalimat biasa, bukan Judul Kapital.
     *
     *  Aturan ini sudah dijaga untuk profil dan konfirmasi sandi, tetapi
     *  formulir alamat, pendaftaran, dan masuk masih memakai "Nama Lengkap",
     *  "Alamat Lengkap", dan "Konfirmasi Kata Sandi" — satu formulir bisa
     *  punya dua gaya label. Nama merek, akronim, dan satuan tetap huruf
     *  besar (Berkah Kreatif, JPG, MB).
     */
    public function test_semua_label_isian_memakai_huruf_kecil_kalimat(): void
    {
        $sah = ['SIMALUN', 'Berkah', 'Kreatif', 'Jambi', 'WhatsApp', 'Instagram', 'Facebook', 'Google', 'Maps', 'QRIS', 'PDF', 'MB', 'KB', 'HP', 'RT', 'RW', 'QR', 'ID', 'JPG', 'JPEG', 'PNG', 'WebP', 'GIF', 'WIB'];
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());

            preg_match_all('/<label\b[^>]*>(.*?)<\/label>/s', $html, $found, PREG_SET_ORDER);

            foreach ($found as $item) {
                $teks = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($item[1], ENT_QUOTES))));

                if ($teks === '' || str_contains($teks, '{{') || str_contains($teks, '@')) {
                    continue;
                }

                $kata = array_values(array_filter(preg_split('/[\s\/·]+/u', $teks), fn ($k) => preg_match('/[A-Za-z]/', (string) $k)));

                if (count($kata) < 2) {
                    continue;
                }

                $biasa = array_values(array_filter($kata, fn ($k) => ! in_array(trim($k, '().,:'), $sah, true) && ! ctype_upper($k) && mb_strlen($k) > 2));

                if (count($biasa) >= 2 && collect($biasa)->every(fn ($k) => ctype_upper(mb_substr($k, 0, 1)))) {
                    $pelanggaran[] = $relative.' → "'.$teks.'"';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($pelanggaran)), implode(' | ', array_values(array_unique($pelanggaran))));
    }

    /** Teks kapital ditulis kalimat biasa; kapital datang dari CSS.
     *
     *  Label kecil di atas judul ("RUANG PELANGGAN", "DATA PRIBADI") ditulis
     *  huruf besar langsung di markup. Pembaca layar kadang mengejanya
     *  huruf per huruf, dan teks itu tidak bisa dicari dengan huruf kecil.
     *  Sekarang markup memakai kalimat biasa dan `text-transform` di CSS yang
     *  membuatnya tampak kapital — tampilan sama, makna lebih benar.
     */
    public function test_teks_kapital_dibuat_oleh_css_bukan_markup(): void
    {
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = (string) File::get($file->getPathname());
            $html = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $html);

            preg_match_all('/>([^<>{}]+)</u', $html, $found);

            foreach ($found[1] as $teks) {
                $teks = trim(preg_replace('/\s+/u', ' ', html_entity_decode($teks, ENT_QUOTES)));

                if ($teks === '' || ! preg_match('/[A-Z]{2,}/', $teks)) {
                    continue;
                }

                $huruf = preg_split('//u', preg_replace('/[^A-Za-z]/', '', $teks), -1, PREG_SPLIT_NO_EMPTY);

                if (count($huruf) < 3 || array_filter($huruf, fn ($c) => ctype_lower($c))) {
                    continue;
                }

                // Merek dan satuan boleh kapital seluruhnya.
                // Merek dan satuan boleh kapital seluruhnya ("SIMALUN", "· WIB").
                if (count($kata) === 1 && in_array(trim($teks, " ·.:•"), ['SIMALUN', 'WIB', 'PDF', 'QRIS', 'ID'], true)) {
                    continue;
                }

                $pelanggaran[] = $relative.' → "'.$teks.'"';
            }
        }

        foreach (['public/css/berkah-experience.css' => ['bk-kicker', 'bk-atelier-label', 'bk-order-card__label'], 'public/css/berkah-auth.css' => ['auth-eyebrow']] as $berkas => $kelas) {
            $css = (string) File::get(base_path($berkas));

            foreach ($kelas as $nama) {
                $this->assertMatchesRegularExpression('/\.'.preg_quote($nama, '/').'\{[^}]*text-transform:\s*uppercase/', $css, $berkas.' tidak mengapitalkan .'.$nama);
            }
        }

        $this->assertSame([], array_values(array_unique($pelanggaran)), implode(' | ', array_values(array_unique($pelanggaran))));
    }
}
