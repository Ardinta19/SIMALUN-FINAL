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
        // Judul halaman dan tombol utama boleh Judul Kapital (seperti
        // "Masuk Sekarang" di halaman login), tetapi LABEL ISIAN memakai
        // huruf kecil sesuai kalimat — sama dengan "Kata sandi" di login dan
        // daftar. Halaman konfirmasi sandi dan profil pelanggan sempat memakai
        // "Kata Sandi Saat Ini", sehingga satu formulir punya dua gaya.
        $konfirmasi = File::get(resource_path('views/auth/confirm-password.blade.php'));
        $verifikasi = File::get(resource_path('views/auth/verify-email.blade.php'));
        $profil = File::get(resource_path('views/roles/customer/profile/edit.blade.php'));

        $this->assertStringContainsString("__('Kata sandi')", $konfirmasi);
        $this->assertStringContainsString("__('Verifikasi Email Kamu')", $verifikasi);

        foreach (['Kata sandi saat ini', 'Kata sandi baru', 'Konfirmasi kata sandi baru'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $profil, 'Label "'.$label.'" hilang dari profil pelanggan.');
        }

        $this->assertStringNotContainsString('>Kata Sandi', $profil, 'Label isian tidak boleh Judul Kapital.');
        $this->assertStringContainsString('Ganti Kata Sandi', $profil, 'Judul kartu tetap Judul Kapital.');
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
}
