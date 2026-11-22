<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjaga integrasi UI kit + tema gelap.
 *
 * Bug visual paling mahal muncul saat sebuah halaman baru menambahkan
 * permukaan putih tanpa padanan tema gelap, atau saat mikro-interaksi
 * mengubah kontrak form (nama field / nilai / tombol submit). Tes ini
 * memeriksa keduanya secara statis.
 */
class UiKitTest extends TestCase
{
    /** Permukaan putih yang memang harus tetap putih (knop, titik, kilatan). */
    private const SURFACE_ALLOWLIST = [
        '#exit-flash',
        '.dot.on',
        '.switch__thumb',
        '.activity-dot.pending',
        '.history-dot',
        '.step-circle',
    ];

    /** Halaman yang sengaja tidak memuat tema/UI kit (email, PDF, lembar cetak). */
    private const HEADLESS_PAGES = [
        'emails/layout.blade.php',
        'exports/finance-pdf.blade.php',
        'order/receipt-pdf.blade.php',
        'rental/daily-sheet.blade.php',
    ];

    public function test_theme_and_ui_kit_assets_exist(): void
    {
        foreach (['css/berkah-theme.css', 'css/berkah-ui-kit.css', 'js/berkah-ui-kit.js'] as $asset) {
            $this->assertFileExists(public_path($asset), $asset.' belum ada.');
        }

        $head = File::get(resource_path('views/layouts/component/_theme_head.blade.php'));

        $this->assertStringContainsString("'css/berkah-theme.css'", $head);
        $this->assertStringContainsString("'css/berkah-ui-kit.css'", $head);
        $this->assertStringContainsString("setAttribute('data-theme'", $head);
        $this->assertStringContainsString('prefers-color-scheme: dark', $head);
        $this->assertStringContainsString('localStorage', $head);

        $kit = File::get(resource_path('views/layouts/component/_ui_kit.blade.php'));

        $this->assertStringContainsString("'js/berkah-ui-kit.js'", $kit);
        $this->assertStringContainsString('data-bk-theme-toggle', $kit);
        $this->assertStringContainsString('id="bk-theme-switch"', $kit);
        $this->assertStringContainsString('data-bk-theme-set="dark"', $kit);
    }

    public function test_every_screen_loads_theme_head_and_ui_kit(): void
    {
        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Partial (nama berawalan _) bukan halaman yang punya dokumen
            // sendiri, walau komentarnya menyebut tag penutup body.
            if (str_starts_with($file->getFilename(), '_')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $html = File::get($file->getPathname());

            if (in_array($relative, self::HEADLESS_PAGES, true) || str_starts_with($relative, 'emails/') || str_contains($relative, 'pdf')) {
                $this->assertStringNotContainsString('_theme_head', $html, $relative.' (email/PDF) tidak boleh memuat tema.');
                $this->assertStringNotContainsString('_ui_kit', $html, $relative.' (email/PDF) tidak boleh memuat UI kit.');
                continue;
            }

            if (str_contains($html, '</head>') && ! str_contains($html, "_theme_head")) {
                $missing[] = $relative.' → _theme_head';
            }

            if (str_contains($html, '</body>') && ! str_contains($html, '_ui_kit')) {
                $missing[] = $relative.' → _ui_kit';
            }
        }

        $this->assertSame([], $missing, "Halaman belum memuat tema/UI kit:\n".implode("\n", $missing));
    }

    public function test_dark_theme_defines_core_tokens(): void
    {
        $css = File::get(public_path('css/berkah-theme.css'));

        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\]\s*\{/', $css);

        foreach (['--ink:', '--card:', '--line:', '--fc-ink:', '--fc-cream:', '--bk-ink:', '--theme-bg:'] as $token) {
            $this->assertStringContainsString($token, $css, 'Token gelap hilang: '.$token);
        }

        // --surface-raised sengaja TIDAK dipetakan: nilainya juga dipakai
        // sebagai warna teks putih pada tombol brand.
        $this->assertStringNotContainsString('--surface-raised: var(--theme-surface)', $css);
        $this->assertStringContainsString('--surface-raised', File::get(resource_path('views/layouts/component/_tokens.blade.php')));
    }

    public function test_dark_theme_covers_every_light_surface(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));
        $light = '/(?:background(?:-color)?)\s*:\s*(?:#fff\b|#ffffff|white|#fffaf8|#faf[0-9a-f]*|#fbf[0-9a-f]*|#f8f[0-9a-f]*|#f9f[0-9a-f]*)/i';
        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Partial (nama berawalan _) bukan halaman yang punya dokumen
            // sendiri, walau komentarnya menyebut tag penutup body.
            if (str_starts_with($file->getFilename(), '_')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (in_array($relative, self::HEADLESS_PAGES, true) || str_starts_with($relative, 'emails/') || str_contains($relative, 'pdf')) {
                continue;
            }

            $html = File::get($file->getPathname());
            preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $html, $blocks);

            foreach ($blocks[1] as $css) {
                $css = preg_replace('!/\*.*?\*/!s', '', $css);
                preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);

                foreach ($rules as $rule) {
                    if (! preg_match($light, $rule[2])) {
                        continue;
                    }

                    foreach (explode(',', $rule[1]) as $selector) {
                        $selector = trim(preg_replace('/\s+/', ' ', $selector));
                        if ($selector === '' || str_starts_with($selector, '@')) {
                            continue;
                        }
                        if (str_contains($selector, ':hover') || str_contains($selector, ':focus') || str_contains($selector, ':active') || str_contains($selector, '::')) {
                            continue;
                        }
                        if (in_array($selector, self::SURFACE_ALLOWLIST, true)) {
                            continue;
                        }
                        if (! $this->themeMentions($theme, $selector)) {
                            $missing[] = $relative.' → '.$selector;
                        }
                    }
                }
            }
        }

        $missing = array_values(array_unique($missing));

        $this->assertSame([], $missing, "Permukaan terang belum punya aturan tema gelap:\n".implode("\n", $missing));
    }

    public function test_dark_theme_covers_dark_text_colors(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));
        $darkColor = '/color\s*:\s*(#[0-6][0-9a-f]{5}|#[0-9a-f]{3}\b)/i';
        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Partial (nama berawalan _) bukan halaman yang punya dokumen
            // sendiri, walau komentarnya menyebut tag penutup body.
            if (str_starts_with($file->getFilename(), '_')) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (in_array($relative, self::HEADLESS_PAGES, true) || str_starts_with($relative, 'emails/') || str_contains($relative, 'pdf')) {
                continue;
            }

            preg_match_all('/<style[^>]*>(.*?)<\/style>/s', File::get($file->getPathname()), $blocks);

            foreach ($blocks[1] as $css) {
                $css = preg_replace('!/\*.*?\*/!s', '', $css);
                preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);

                foreach ($rules as $rule) {
                    if (! preg_match($darkColor, $rule[2], $match)) {
                        continue;
                    }
                    if (strtolower($match[1]) === '#fff' || strtolower($match[1]) === '#ffffff') {
                        continue;
                    }

                    foreach (explode(',', $rule[1]) as $selector) {
                        $selector = trim(preg_replace('/\s+/', ' ', $selector));
                        if ($selector === '' || str_starts_with($selector, '@') || preg_match('/:(hover|focus|active)|::/', $selector)) {
                            continue;
                        }
                        if (! $this->themeMentions($theme, $selector)) {
                            $missing[] = $relative.' → '.$selector;
                        }
                    }
                }
            }
        }

        $missing = array_values(array_unique($missing));

        $this->assertSame([], $missing, "Warna teks gelap literal belum punya padanan tema gelap:\n".implode("\n", $missing));
    }

    private function themeMentions(string $css, string $selector): bool
    {
        $pattern = '/(?<![\w-])'.preg_quote($selector, '/').'(?![\w-])/';

        return (bool) preg_match($pattern, $css);
    }

    public function test_dark_theme_replaces_light_page_surfaces(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));

        // Panel sambutan dashboard: judulnya ikut warna teks tema gelap, jadi
        // latarnya wajib ikut gelap (dulu putih krem -> teks tak terbaca).
        $this->assertMatchesRegularExpression(
            '/html\[data-theme="dark"\] \.bk-dashboard-welcome \{[^}]*background:/s',
            $theme
        );
        $this->assertMatchesRegularExpression(
            '/html\[data-theme="dark"\] \.bk-dashboard-flower \{[^}]*background:/s',
            $theme
        );

        // Kartu menu "Jelajahi": teksnya sudah terang, latarnya harus ikut gelap.
        $this->assertMatchesRegularExpression(
            '/html\[data-theme="dark"\] :is\(\.bk-menu-links a, \.bk-command-links a\) \{[^}]*background:/s',
            $theme
        );

        // Tombol suka, bintang rating, dan kotak catatan bantuan.
        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\] :is\(\.bk-like\) \{[^}]*background:/s', $theme);
        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\] :is\(\.bk-star-rating__options label\) \{[^}]*background:/s', $theme);
        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\] :is\(\.help-notice\) \{[^}]*background:/s', $theme);
    }

    public function test_components_are_wired_without_changing_form_contracts(): void
    {
        $catalog = File::get(resource_path('views/catalog/index.blade.php'));
        $this->assertStringContainsString('class="bk-atc"', $catalog);
        $this->assertStringContainsString('name="quantity"', $catalog);
        $this->assertStringContainsString('class="bk-qty" data-bk-qty', $catalog);
        $this->assertStringContainsString('data-bk-qty-step="-1"', $catalog);
        $this->assertStringContainsString('data-bk-qty-step="1"', $catalog);
        $this->assertStringContainsString('class="bk-qty__input"', $catalog);
        $this->assertStringContainsString('name="starts_on"', $catalog);
        $this->assertStringContainsString('name="ends_on"', $catalog);
        $this->assertStringContainsString("route('cart.store', \$product)", $catalog);

        $show = File::get(resource_path('views/catalog/show.blade.php'));
        $this->assertStringContainsString('bk-atc', $show);
        $this->assertStringContainsString('name="next" value="cart"', $show);
        $this->assertStringContainsString('name="next" value="checkout"', $show);
        $this->assertStringContainsString('name="quantity"', $show);
        $this->assertStringContainsString('class="bk-qty" data-bk-qty', $show);
        $this->assertStringContainsString("data-fc-disabled='@json(\$bookedDates)'", $show);
        $this->assertStringContainsString('id="detail-booked-hint"', $show);
        $this->assertStringContainsString("addDays(119)", $show);

        $cart = File::get(resource_path('views/catalog/cart.blade.php'));
        $this->assertStringContainsString('bk-del', $cart);
        $this->assertStringContainsString('class="bk-unit-count"', $cart);
        $this->assertStringContainsString('+ Tambah unit', $cart);
        $this->assertStringContainsString("route('cart.destroy'", $cart);
        $this->assertStringContainsString("@method('DELETE')", $cart);
        $this->assertStringContainsString('aria-label="Hapus unit', $cart);

        $addresses = File::get(resource_path('views/roles/customer/addresses/index.blade.php'));
        $this->assertStringContainsString('bk-del', $addresses);
        $this->assertStringContainsString("route('customer.addresses.destroy'", $addresses);
        $this->assertStringContainsString('aria-label="Hapus alamat', $addresses);

        $feedback = File::get(resource_path('views/catalog/product-feedback.blade.php'));
        $this->assertStringContainsString('bk-heart', $feedback);
        $this->assertStringContainsString('data-bk-feedback', $feedback);
        $this->assertStringContainsString('data-bk-like-total', $feedback);
        $this->assertStringContainsString('data-bk-like-status', $feedback);
        $this->assertStringContainsString('name="liked"', $feedback);
        $this->assertStringContainsString('aria-pressed', $feedback);
        $this->assertStringContainsString("route('rental.product.like'", $feedback);

        $register = File::get(resource_path('views/auth/register.blade.php'));
        $this->assertStringContainsString('class="btn-submit bk-auth"', $register);
        $this->assertStringContainsString('id="btn-submit"', $register);
        $this->assertStringContainsString('class="btn-text bk-sr-only"', $register);
        $this->assertStringContainsString('type="submit"', $register);

        $nav = File::get(resource_path('views/catalog/customer-navigation.blade.php'));
        $this->assertStringContainsString('data-bk-theme-host', $nav);
    }

    public function test_ui_kit_javascript_respects_reduced_motion_and_submit_contracts(): void
    {
        $js = File::get(public_path('js/berkah-ui-kit.js'));

        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringContainsString("localStorage.removeItem(STORE_KEY)", $js);
        $this->assertStringContainsString('aria-busy', $js);
        $this->assertStringContainsString("label.textContent = 'Menambahkan…'", $js);

        // Tombol submit tidak boleh dinonaktifkan saat submit: tombol bisa
        // membawa name/value (mis. name="next" value="cart").
        $this->assertStringNotContainsString('button.disabled = true', $js);
        $this->assertStringNotContainsString('button.disabled = !0', $js);

        // Heart tidak boleh mengubah nilai input `liked`.
        $this->assertStringNotContainsString('name="liked"', $js);
        $this->assertStringNotContainsString("querySelector('[name=\"liked\"]')", $js);

        // Suka dikirim lewat fetch: halaman tidak dimuat ulang.
        $this->assertStringContainsString("closest('.bk-heart')", $js);
        $this->assertStringContainsString("headers: { Accept: 'application/json'", $js);
        $this->assertStringContainsString('event.preventDefault()', $js);
        $this->assertStringContainsString("[data-bk-like-total]", $js);
        $this->assertStringContainsString('form.submit()', $js);

        // Stepper jumlah unit: menambah/mengurangi tanpa melewati stok.
        $this->assertStringContainsString('[data-bk-qty]', $js);
        $this->assertStringContainsString('[data-bk-qty-step]', $js);
        $this->assertStringContainsString('aria-disabled', $js);
    }

    public function test_theme_switch_points_light_left_and_dark_right(): void
    {
        $kit = File::get(resource_path('views/layouts/component/_ui_kit.blade.php'));
        $js = File::get(public_path('js/berkah-ui-kit.js'));

        // Kenop beristirahat di kanan (gelap) dan bergeser 152px ke kiri untuk terang.
        $this->assertStringContainsString('data-knob-light="-152"', $kit);
        $this->assertStringContainsString('data-knob-dark="0"', $kit);

        // Matahari di kiri, bulan sabit di kanan.
        $sun = strpos($kit, 'cx="176" cy="124" r="12"');
        $moon = strpos($kit, 'cx="328" cy="124" r="17"');
        $this->assertNotFalse($sun);
        $this->assertNotFalse($moon);
        $this->assertLessThan($moon, $sun, 'Matahari harus berada di kiri bulan.');

        // Ikon aktif lebih kecil dari kenop (r=38) supaya tertutup penuh.
        $this->assertStringContainsString('class="bk-theme-svg__knob" cx="328" cy="124" r="38"', $kit);
        $this->assertStringContainsString('M176 106V101', $kit);

        // Arah dibaca dari markup, bukan angka tetap di skrip.
        $this->assertStringContainsString('dataset.knobLight', $js);
        $this->assertStringContainsString('dataset.knobDark', $js);
        $this->assertStringNotContainsString('BkKnobOffset', $js);
        $this->assertStringContainsString('handTarget', $js);

        // Cincin luar ikut bergerak bersama kenop (dulu tertinggal di sisi lain).
        $this->assertStringContainsString('class="bk-theme-svg__knob-ring" cx="328" cy="124" r="38" fill="none"', $kit);
        $this->assertStringContainsString('class="bk-theme-svg__cut"', $kit);
        $this->assertStringContainsString('knobNodes', $js);
        $this->assertStringContainsString("window.gsap.to(node", $js);
    }

    public function test_date_picker_disables_fully_booked_dates(): void
    {
        $js = File::get(public_path('js/berkah-controls.js'));

        $this->assertStringContainsString('data-fc-disabled', $js);
        $this->assertStringContainsString('parseBlockedDates', $js);
        $this->assertStringContainsString('is-blocked', $js);
        $this->assertStringContainsString('sudah dibooking', $js);

        $css = File::get(public_path('css/berkah-controls.css'));
        $this->assertStringContainsString('.fc-cal__day.is-blocked', $css);
    }
}
