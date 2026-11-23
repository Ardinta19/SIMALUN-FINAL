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
        'rental.help-layout',
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

    public function test_admin_mobile_sheet_has_a_controller(): void
    {
        // Markup bilah admin menyembunyikan tujuan di luar empat menu harian
        // di balik tombol "Lainnya". Tanpa pengendali di berkah-admin.js,
        // tombol itu mati dan separuh menu tak bisa dijangkau di ponsel —
        // persis cacat yang pernah lolos karena markup-nya "terlihat benar".
        $nav = File::get(resource_path('views/layouts/component/admin/_navbar_admin.blade.php'));
        $js = File::get(public_path('js/berkah-admin.js'));

        $this->assertStringContainsString('data-admin-more', $nav);
        $this->assertStringContainsString('id="admin-sheet"', $nav);
        $this->assertStringContainsString('data-admin-sheet-close', $nav);

        $this->assertStringContainsString("getElementById('admin-sheet')", $js);
        $this->assertStringContainsString("'[data-admin-more]'", $js);
        $this->assertStringContainsString("'[data-admin-sheet-close]'", $js);
        $this->assertStringContainsString("setAttribute('aria-expanded'", $js);
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
}
