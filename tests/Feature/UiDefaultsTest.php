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
