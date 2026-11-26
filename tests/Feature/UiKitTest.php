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
        $this->assertMatchesRegularExpression('/html\s*\{\s*color-scheme:\s*light;/', $css);
        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\]\s*\{\s*color-scheme:\s*dark;/', $css);

        foreach (['--ink:', '--card:', '--line:', '--fc-ink:', '--fc-cream:', '--bk-ink:', '--wine:', '--paper:', '--theme-bg:'] as $token) {
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

    public function test_dark_theme_text_stays_readable_on_dark_surfaces(): void
    {
        $css = File::get(public_path('css/berkah-theme.css'));

        // Blok cetak sengaja tetap terang, jadi tidak ikut diuji.
        $printAt = strpos($css, '@media print');
        if ($printAt !== false) {
            $css = substr($css, 0, $printAt);
        }
        $css = preg_replace('!/\*.*?\*/!s', '', $css);

        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);

        $failures = [];

        foreach ($rules as $rule) {
            $selector = trim(preg_replace('/\s+/', ' ', $rule[1]));

            if (! str_contains($selector, 'data-theme="dark"')) {
                continue;
            }

            // Teks nonaktif memang dibuat redup; bukan konten yang harus dibaca.
            if (str_contains($selector, ':disabled')) {
                continue;
            }

            preg_match_all('/(?<![\w-])color\s*:\s*(#[0-9a-fA-F]{3,6})/', $rule[2], $matches);

            foreach ($matches[1] as $hex) {
                $ratio = $this->contrastRatio($hex, '#201820');

                if ($ratio < 4.5) {
                    $failures[] = $selector.' → '.$hex.' ('.round($ratio, 2).':1)';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($failures)), "Teks tema gelap di bawah 4.5:1 terhadap permukaan #201820:\n".implode("\n", $failures));
    }

    public function test_customer_surfaces_and_auth_copy_have_dark_values(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));

        $selectors = [
            '.bk-category-strip a',
            '.bk-applied-filters a',
            '.bk-inbox-item',
            '.bk-order-card',
            '.bk-order-status span',
            '.bk-jump a',
            '.bk-pagination a',
            '.bk-profile-card',
            '.bk-address-card',
            '.bk-help .help-card',
            '.bk-checkout-steps li',
            '.bk-back',
            '.bk-order-row',
            '.auth-card > p',
            '.auth-back',
            '.auth-form input',
            '.field-label',
            '.field-input',
        ];

        foreach ($selectors as $selector) {
            $this->assertStringContainsString($selector, $theme, 'Belum ada nilai gelap untuk: '.$selector);
        }

        // Teks terang di atas permukaan gelap, bukan sebaliknya.
        $this->assertStringContainsString('.auth-card > p, .auth-foot', $theme);
        $this->assertStringContainsString('color: var(--theme-ink-mid);', $theme);
    }

    public function test_status_chips_alerts_and_legacy_pages_have_dark_surfaces(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));

        // Chip pastel + teks yang sudah jadi terang = tidak terbaca; latarnya
        // harus ikut digelapkan.
        $selectors = [
            '.btn-danger',
            '.logout-btn',
            '.alert-error',
            '.rating-status--err',
            '.ok',
            '.alert-success',
            '.rating-status--ok',
            '.driver-btn.wa',
            '.da-btn.wa',
            '.status-badge.dijemput',
            '.status-badge.disetrika',
            '.status-badge.dikirim',
            '.badge-muted',
            '.confirm-btn--cancel',
            '.cancel-btn--secondary',
            '.confirm-icon.type-danger',
            '.confirm-icon.type-warning',
            '.confirm-icon.type-success',
            '.confirm-icon.type-info',
            '.step-circle',
            '.step-chip.active',
            '.customer-radial__links a',
            '.bk-header-radial>summary',
            '.auth-back',
            '.code-pill',
        ];

        foreach ($selectors as $selector) {
            $pattern = '/html\[data-theme="dark"\][^{]*'.preg_quote($selector, '/').'[^{]*\{[^{}]*background(?:-color)?\s*:/';

            $this->assertMatchesRegularExpression($pattern, $theme, 'Permukaan chip/alert belum digelapkan: '.$selector);
        }

        // Bintang rating, kode galat besar, dan <details> bantuan bukan keping
        // berlatar: yang dijaga di sini warna teks/garisnya, bukan latarnya.
        $textOnly = [
            '.rating-existing__star',
            '.rating-stars label',
            '.rating-existing__star--off',
            '.code-big',
            '.help-card details',
        ];

        foreach ($textOnly as $selector) {
            $pattern = '/html\[data-theme="dark"\][^{]*'.preg_quote($selector, '/').'[^{}]*\{[^{}]*(?:color|border-color)\s*:/';

            $this->assertMatchesRegularExpression($pattern, $theme, 'Nilai tema gelap untuk teks/garis belum ada: '.$selector);
        }
    }

    public function test_catalog_secondary_text_has_dark_values(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));

        // Teks sekunder katalog/pelanggan memakai heksa gelap literal; tanpa
        // padanan gelap ia jadi abu tua di atas latar gelap.
        $selectors = [
            '.bk-wordmark span',
            '.bk-empty p',
            '.bk-order-info small',
            '.bk-order-date',
            '.bk-inbox-content time',
            '.bk-inbox-message',
            '.bk-how small',
            '.bk-cart-count',
            '.bk-address-delete p',
            '.bk-muted-mark',
            '.bk-order-filter__hint',
            '.bk-order-date b',
            '.bk-cart-count strong',
            '.bk-inbox-arrow',
            '.bk-how>div>span',
            '.bk-req',
            '.bk-field-error',
            '.bk-address-delete summary',
            '.driver-nav',
            '.bottom-cta',
            '.btn-order:disabled',
            // Palet --blue-* halaman pesanan/alamat/profil/laporan
            '.card-title',
            '.section-title',
            '.sum-total-label',
            '.dr-value',
            '.review__av',
            '.order-card__avatar',
            '.driver-chip__av',
            '.timeline-card__title',
            '.info-card__title',
            '.eta-value',
            '.activity-title',
            '.addr-link',
            '.cat-heading',
            '.service-price',
            '.item-stepper-btn',
            '.item-note-toggle',
            '.ws-btn',
            '.sum-total-value',
            '.oc-value',
            '.order-card__code',
            '.progress-lbl.active',
            '.order-card__price',
            '.order-card__cta',
            '.step-label.active',
            '.ir-value.highlight',
            '.items-mini-price',
            '.avatar-action',
            '.section__link',
            // Palet --brand/--danger/--success/--warning halaman admin & driver
            '.rate__stars',
            '.review__rate',
            '.avatar-file',
            '.btn-add-item',
            '.card-title svg',
            '.card__title svg',
            '.contact-bar__btn--call',
            '.data-row__value--primary',
            '.empty-state__ico',
            '.empty__icon',
            '.faq-q span',
            '.filter-summary__badge',
            '.filter-tab.active',
            '.input-icon',
            '.menu-card__icon',
            '.menu-card__icon--blue',
            '.notif.is-unread .notif__time',
            '.notif__ico',
            '.order-card__badge--proses',
            '.order-card__code',
            '.overview-card__stat-num',
            '.report-card__avatar',
            '.report-card__category',
            '.section-card__icon',
            '.section-link',
            '.section-title svg',
            '.stat-card__icon',
            '.stat-card__icon--blue',
            '.stat-card__num',
            '.stat__num',
            '.stepper__btn',
            '.summary-card__value.balance',
            '.task-card__address svg',
            '.task-card__code',
            '.task-card__weight',
            '.tx-head__title svg',
            '.alert--error',
            '.btn-logout',
            '.error',
            '.form-label__req',
            '.item-remove',
            '.log-diff__before',
            '.order-card__badge--batal',
            '.summary-card__value.expense',
            '.tx-amount.expense',
            '.alert--success',
            '.data-row__value--accent',
            '.empty-card__ico',
            '.log-diff__after',
            '.order-card__badge--selesai',
            '.order-card__total',
            '.stat__num--success',
            '.summary-card__value.income',
            '.tx-amount.income',
            '.active-task__title svg',
            '.form-card__icon',
            '.stat__num--accent',
            '.v-code svg',
            '.order-card__badge--menunggu',
            '.stat-card__num--amber',
            '.fc-layer__title',
            '.fc-cal__month',
            '.fc-opt[aria-selected="true"]',
            '.fc-segmented__btn:hover',
            '.fc-cal__day.is-today',
            '.fc-month.is-today',
            '.fc-opt__check',
            '.fc-trigger__icon',
            '.fc-cal__day.is-selected',
            '.fc-month.is-selected',
            '.status-badge.dibatalkan',
            'footer',
            '.field label',
            '.field__required',
            '.field__error',
            '.field-label .required',
            '.field-error',
            '.field-label .req',
            '.status-label',
            '.at-time.current',
            '.ir-value.orange',
            '.service-card:has(input:checked) .service-name',
            '.subtitle',
            '.oc-label',
            '.dr-label',
            '.manual-note',
            '.err',
            '.sum-discount',
            '.order-card__badge',
            '.pg-item--muted',
            '.pg-item--step',
            '.pg-item--page',
            '.pg-item--current',
            '.pg-dots',
            '.auth-button',
            '.bk-how',
            '.bk-address-delete',
            '.info-row',
            '.order-card__footer',
            '.history-item',
            '.tip',
            '.bk-theme-choice[aria-pressed="true"]',
        ];

        $missing = array_values(array_filter($selectors, fn (string $selector) => ! $this->themeMentions($theme, $selector)));

        $this->assertSame([], $missing, "Selector belum punya nilai tema gelap:\n".implode("\n", $missing));
    }

    public function test_light_theme_secondary_text_meets_contrast(): void
    {
        // Teks sekunder pada tema terang harus tetap >= 4.5:1 terhadap
        // permukaan tempat ia benar-benar duduk.
        $cases = [
            ['berkah-experience.css', '.bk-kicker', '#fffaf6'],
            ['berkah-experience.css', '.bk-order-card__label', '#fffaf6'],
            ['berkah-experience.css', '.bk-how>div>span', '#fbf6ef'],
            ['berkah-experience.css', '.bk-menu-links a span', '#fffaf6'],
            ['berkah-experience.css', '.bk-muted-mark', '#fbf6ef'],
            ['berkah-experience.css', '.bk-order-mark', '#f5e7e2'],
            ['berkah-experience.css', '.bk-order-card__mark', '#f5e7e2'],
            ['berkah-auth.css', '.auth-foot', '#fbf6ef'],
            ['berkah-auth.css', '.auth-eyebrow', '#fffaf6'],
        ];

        foreach ($cases as [$file, $selector, $surface]) {
            $color = $this->declaredColor(File::get(public_path('css/'.$file)), $selector);

            $this->assertNotNull($color, 'Warna teks tidak ditemukan untuk '.$selector.' di '.$file);

            $ratio = $this->contrastRatio($color, $surface);

            $this->assertGreaterThanOrEqual(4.5, $ratio, $selector.' hanya '.round($ratio, 2).':1 pada '.$surface.' ('.$file.').');
        }
    }

    public function test_theme_javascript_keeps_color_scheme_in_sync(): void
    {
        $js = File::get(public_path('js/berkah-ui-kit.js'));

        $this->assertStringContainsString('root.style.colorScheme', $js);
        $this->assertStringContainsString('const setThemeAttribute', $js);

        // Hanya helper yang boleh menyetel data-theme langsung; jalur lain
        // (penyimpanan antar-tab, media query, mode ikut sistem) wajib lewat
        // helper agar color-scheme tidak tertinggal.
        $this->assertSame(1, substr_count($js, "root.setAttribute('data-theme'"));

        // Bilah peramban mobile ikut tema tapi warna merek per halaman tetap
        // dipulihkan saat kembali ke tema terang.
        $this->assertStringContainsString('meta[name="theme-color"]', $js);
        $this->assertStringContainsString("DARK_THEME_COLOR = '#140f12'", $js);
    }

    public function test_themed_pages_never_hardcode_dark_inline_text_colors(): void
    {
        $offenders = [];
        $themed = 0;

        foreach (File::allFiles(resource_path('views')) as $file) {
            $html = File::get($file->getPathname());

            // Halaman bertema: memuat sendiri kepala tema, atau mewarisi
            // layout yang memuatnya.
            $inherits = preg_match('/@extends\(\s*[\'"](?:catalog\.layout|layouts\.guest|errors\.layout)/', $html) === 1;

            if (! str_contains($html, '_theme_head') && ! $inherits) {
                continue;
            }

            $themed++;

            preg_match_all('/style="([^"]*)"/s', $html, $matches);

            foreach ($matches[1] as $css) {
                if (! preg_match('/(?<![\w-])color\s*:\s*(#[0-9a-fA-F]{6})/', $css, $color)) {
                    continue;
                }

                // Gaya inline menang atas aturan tema, jadi tinta gelap akan
                // hilang di mode gelap. Chip yang punya latar sendiri aman.
                if ($this->relativeLuminance($color[1]) >= 0.30) {
                    continue;
                }

                if (preg_match('/background(?:-color)?\s*:\s*#[0-9a-fA-F]{6}/', $css)) {
                    continue;
                }

                $offenders[] = $file->getRelativePathname().' → '.$color[0];
            }
        }

        // Ambang 50 → 25 setelah modul laundry dicabut (±30 view ikut
        // hilang), lalu 25 → 20 setelah lima halaman admin mandiri pindah ke
        // `layouts.admin` (mereka tidak lagi memuat `_theme_head` sendiri).
        // Tujuannya tetap sama: memastikan deteksi halaman bertema tidak
        // diam-diam berhenti menemukan apa pun.
        $this->assertGreaterThan(20, $themed, 'Deteksi halaman bertema gagal.');
        $this->assertSame([], $offenders, 'Gunakan var(--theme-*, cadangan-terang) untuk warna teks inline di halaman bertema.');
    }

    public function test_dark_theme_text_tokens_clear_every_dark_surface(): void
    {
        $css = File::get(public_path('css/berkah-theme.css'));

        $this->assertSame(1, preg_match('/html\[data-theme="dark"\] \{(.*?)\n\}/s', $css, $block), 'Blok token gelap tidak ditemukan.');

        $token = function (string $name) use ($block): string {
            $this->assertSame(1, preg_match('/'.$name.':\s*(#[0-9a-fA-F]{6})/', $block[1], $m), $name.' hilang dari tema gelap.');

            return $m[1];
        };

        $surfaces = [$token('--theme-bg'), $token('--theme-surface'), $token('--theme-surface-2'), $token('--theme-surface-3')];

        foreach (['--theme-ink', '--theme-ink-mid', '--theme-ink-lt', '--theme-accent-text', '--theme-danger-ink', '--theme-success-ink', '--theme-warning-ink'] as $ink) {
            $hex = $token($ink);

            foreach ($surfaces as $surface) {
                $this->assertGreaterThanOrEqual(
                    4.5,
                    $this->contrastRatio($hex, $surface),
                    $ink.' ('.$hex.') tidak terbaca di permukaan '.$surface.'.'
                );
            }
        }
    }

    public function test_auth_pages_keep_text_readable_over_the_gradient(): void
    {
        foreach (['auth/login.blade.php', 'auth/register.blade.php'] as $page) {
            $css = File::get(resource_path('views/'.$page));

            // Kartu kaca harus bertinta gelap: di bagian bawah gradien latar
            // menjadi terang, sehingga teks putih transparan tidak terbaca.
            $this->assertStringContainsString('background:rgba(43,6,20,.55)', $css, $page.' memakai kartu kaca terlalu bening.');

            // Placeholder 45% putih hanya 2,4:1 di atas isian.
            $this->assertStringContainsString('::placeholder { color:rgba(255,255,255,.62)', $css, $page.' punya placeholder yang terlalu pucat.');
            $this->assertStringNotContainsString('color:rgba(255,255,255,.45)', $css, $page.' masih memakai teks putih 45%.');
        }
    }

    public function test_semantic_color_tokens_are_readable_in_light_theme(): void
    {
        $tokens = File::get(resource_path('views/layouts/component/_tokens.blade.php'));

        // Token ini dipakai sebagai warna teks di atas putih sekaligus latar
        // tombol bertulisan putih; keduanya harus >= 4.5:1.
        foreach (['success-500' => '#087f5b', 'warning-500' => '#b45309', 'danger-500' => '#c62f2f', 'accent-500' => '#c2410c'] as $name => $hex) {
            $this->assertStringContainsString('--'.$name.': '.$hex, $tokens, 'Nilai '.$name.' berubah menjadi lebih pucat.');

            $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio($hex, '#ffffff'), $name.' tidak terbaca sebagai teks di atas putih.');
            $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio('#ffffff', $hex), 'Teks putih di atas '.$name.' tidak terbaca.');
        }
    }

    public function test_print_view_stays_light_in_dark_theme(): void
    {
        $css = File::get(public_path('css/berkah-theme.css'));

        $start = strpos($css, '@media print');

        $this->assertNotFalse($start, 'Blok @media print hilang.');

        $print = substr($css, $start);

        // Saat mencetak dari mode gelap, token harus kembali ke nilai terang;
        // kalau tidak, kartu tercetak hitam dengan teks pucat.
        foreach (['--theme-surface:', '--theme-ink:', '--theme-accent-text:', '--bk-glass:', '--fc-blush:'] as $token) {
            $this->assertStringContainsString($token, $print, 'Blok cetak tidak menyetel '.$token);
        }

        $this->assertStringContainsString('color-scheme: light;', $print);

        // Catatan bantuan memakai token, bukan heksa literal, supaya ikut
        // kembali terang saat dicetak dari mode gelap.
        $this->assertMatchesRegularExpression(
            '/html\[data-theme="dark"\] :is\(\.notice, \.note\) \{[^}]*background: var\(--theme-warning-soft\)/s',
            $css
        );
    }

    public function test_secondary_text_and_gradient_buttons_stay_readable(): void
    {
        // --fc-muted dipakai ::placeholder dan tujuh aturan teks sekunder;
        // nilai lamanya (#8a727a) cuma 4,40:1 di atas putih dan 3,68:1 di
        // atas blush, jadi token dikunci sekaligus diuji di tiga latar.
        $controls = File::get(public_path('css/berkah-controls.css'));

        $this->assertStringContainsString('--fc-muted: #7a626a', $controls, 'Token teks sekunder memudar lagi.');

        foreach (['#ffffff', '#fbf6ef', '#f6e7ea'] as $surface) {
            $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio('#7a626a', $surface), 'Placeholder tidak terbaca di atas '.$surface.'.');
        }

        // Label putih di ujung terang gradien kartu auth; #cf6f8b hanya 3,13:1.
        $kit = File::get(public_path('css/berkah-ui-kit.css'));

        $this->assertStringContainsString('--bk-auth-3: #ad4a6b', $kit, 'Ujung terang gradien auth terlalu pucat untuk label putih.');
        $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio('#fff6f2', '#ad4a6b'), 'Label auth tidak terbaca di ujung gradien.');
        $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio('#fff6f2', '#a81f45'), 'Label auth tidak terbaca di ujung gelap gradien.');

        // Tombol bergradien tidak boleh berakhir di biru muda: teks putihnya
        // cuma 2,46:1. Semua tombol kini berhenti di --blue-mid (>= 4,8:1).
        foreach ([
            'roles/customer/addresses/create.blade.php',
            'roles/customer/addresses/edit.blade.php',
            'roles/customer/profile/edit.blade.php',
        ] as $view) {
            $css = File::get(resource_path('views/'.$view));

            $this->assertDoesNotMatchRegularExpression('/linear-gradient\([^;]*var\(--blue-mid\)[^;]*var\(--blue-light\)/s', $css, 'Gradien di '.$view.' berakhir di biru muda yang bikin teks putih tak terbaca.');
            $this->assertMatchesRegularExpression('/linear-gradient\([^;]*var\(--blue-dark\)[^;]*var\(--blue-mid\)/s', $css, 'Gradien di '.$view.' harus berjalan dari --blue-dark ke --blue-mid.');
        }

    }

    public function test_theme_never_uses_important(): void
    {
        $css = preg_replace('!/\*.*?\*/!s', '', File::get(public_path('css/berkah-theme.css')));

        $this->assertStringNotContainsString('!important', $css, 'Aturan tema tidak boleh memakai !important.');
    }

    /**
     * Warna teks pertama yang dideklarasikan oleh aturan dengan selector persis
     * (tanpa :hover/:focus) di dalam satu berkas CSS.
     */
    private function declaredColor(string $css, string $selector): ?string
    {
        $css = preg_replace('!/\*.*?\*/!s', '', $css);

        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);

        foreach ($rules as $rule) {
            $matched = false;

            foreach (explode(',', $rule[1]) as $candidate) {
                if (trim(preg_replace('/\s+/', ' ', $candidate)) === $selector) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                continue;
            }

            if (preg_match('/(?<![\w-])color\s*:\s*(#[0-9a-fA-F]{6})/', $rule[2], $match)) {
                return $match[1];
            }
        }

        return null;
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $light = $this->relativeLuminance($foreground);
        $dark = $this->relativeLuminance($background);
        $hi = max($light, $dark);
        $lo = min($light, $dark);

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $channels = [];

        foreach ([0, 2, 4] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
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
        $this->assertStringContainsString('BOOKING_HORIZON_DAYS - 1', $show);

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

    public function test_theme_button_never_covers_the_footer_on_auth_screens(): void
    {
        // Tombol tema melayang di kanan bawah dan dulu menutupi pil footer
        // halaman masuk/daftar. Di halaman tanpa slot header, tombol harus
        // pindah ke sudut kanan atas — seberang tombol kembali.
        $css = File::get(public_path('css/berkah-ui-kit.css'));

        $this->assertMatchesRegularExpression(
            '/\.auth-page \.bk-theme-btn\.bk-theme-dock\s*\{[^}]*top:[^}]*right:[^}]*bottom: auto/',
            $css,
            'Halaman auth perlu aturan sudut kanan atas.'
        );
        $this->assertStringContainsString('body.guest-body .bk-theme-btn.bk-theme-dock', $css);

        foreach (['auth/login', 'auth/register', 'auth/forgot-password'] as $view) {
            $html = File::get(resource_path('views/'.$view.'.blade.php'));
            $this->assertMatchesRegularExpression('/<body[^>]*class="auth-page"/', $html, $view);
            $this->assertStringContainsString("layouts.component._ui_kit", $html, $view);
        }
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

    public function test_theme_toggle_shows_exactly_one_icon_per_theme(): void
    {
        $kit = File::get(resource_path('views/layouts/component/_ui_kit.blade.php'));
        $css = File::get(public_path('css/berkah-ui-kit.css'));

        // Markup membawa dua ikon; CSS yang memilih satu sesuai tema.
        $this->assertStringContainsString('class="bk-theme-btn__sun"', $kit);
        $this->assertStringContainsString('class="bk-theme-btn__moon"', $kit);

        // `.bk-theme-btn svg { display: block }` (tipe + kelas) mengalahkan
        // selektor satu kelas, jadi ikon bulan di tema terang wajib memakai
        // dua kelas. Tanpa ini tombol menampilkan matahari DAN bulan.
        $this->assertMatchesRegularExpression('/\.bk-theme-btn\s+\.bk-theme-btn__moon\s*\{[^}]*display:\s*none/', $css);
        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\]\s+\.bk-theme-btn\s+\.bk-theme-btn__sun\s*\{[^}]*display:\s*none/', $css);
        $this->assertMatchesRegularExpression('/html\[data-theme="dark"\]\s+\.bk-theme-btn\s+\.bk-theme-btn__moon\s*\{[^}]*display:\s*(block|inline)/', $css);

        // Aturan satu kelas adalah bentuk yang kalah cascade; jangan dikembalikan.
        $this->assertDoesNotMatchRegularExpression('/\n\.bk-theme-btn__moon\s*\{[^}]*display:\s*none/', $css);
        $this->assertDoesNotMatchRegularExpression('/\n\.bk-theme-btn__sun\s*\{[^}]*display:\s*none/', $css);

        // Tombol tetap satu sel grid sehingga tidak ada ikon menumpuk.
        $this->assertMatchesRegularExpression('/\.bk-theme-btn\s*\{[^}]*display:\s*inline-grid/', $css);
    }

    public function test_theme_hand_follows_the_switch_direction(): void
    {
        $kit = File::get(resource_path('views/layouts/component/_ui_kit.blade.php'));
        $js = File::get(public_path('js/berkah-ui-kit.js'));
        $css = File::get(public_path('css/berkah-theme.css'));
        $kitCss = File::get(public_path('css/berkah-ui-kit.css'));

        // Grup tangan bagian dalam dicerminkan agar datang dari sisi kenop.
        $this->assertStringContainsString('id="bk-theme-hand-art"', $kit);
        $this->assertStringContainsString("art.setAttribute('transform', 'scale(-1 1)')", $js);
        $this->assertStringContainsString('HAND_PARK_RIGHT', $js);
        $this->assertStringNotContainsString('HAND_PARK_LEFT', $js);

        // Nilai dibaca ulang setiap restart: arah selalu mengikuti tema baru.
        $this->assertStringContainsString('x: () => handState.from', $js);
        $this->assertStringContainsString('x: () => handState.to', $js);
        $this->assertStringContainsString('primeHand(dark)', $js);

        // Geometri kenop tidak boleh berubah oleh animasi tangan.
        $this->assertStringContainsString('data-knob-light="-152"', $kit);
        $this->assertStringContainsString('data-knob-dark="0"', $kit);

        // Tangan dipotong tepi panggung, tidak melayang di luar kotak SVG.
        $this->assertMatchesRegularExpression('/#bk-theme-switch\s*\{[^}]*overflow:\s*hidden/', $kitCss);

        // Tooltip bawaan peramban tidak menutupi sakelar lagi.
        $this->assertStringNotContainsString('<title>Sakelar tema', $kit);

        // Dialog tema memakai warna teks & garis versi gelap.
        $this->assertStringContainsString('--bk-theme-dialog-ink:', $css);
        $this->assertStringContainsString('--bk-theme-dialog-line:', $css);
    }

    public function test_secondary_actions_use_the_design_system(): void
    {
        // Tautan aksi tidak boleh tampil sebagai tautan bawaan peramban.
        $expectations = [
            'catalog/index.blade.php' => ['class="btn secondary" href="{{ route(\'welcome\') }}">Hapus filter'],
            'catalog/show.blade.php' => ['class="btn secondary" href="{{ route(\'catalog.show\', $product) }}">Hapus tanggal'],
            'rental/admin.blade.php' => ['class="btn secondary"'],
            'rental/schedule.blade.php' => ['class="btn secondary"', 'class="bk-back"'],
            'rental/payment-settings.blade.php' => ['class="bk-back"'],
            'rental/universities.blade.php' => ['class="bk-back"'],
            'rental/admin-detail.blade.php' => ['class="bk-back"', 'class="btn secondary"'],
        ];

        foreach ($expectations as $file => $needles) {
            $blade = File::get(resource_path('views/'.$file));

            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $blade, 'Tautan aksi tanpa gaya sistem di '.$file);
            }
        }

        // Tombol sistem punya keadaan hover dan fokus keyboard yang terlihat.
        $layout = File::get(resource_path('views/catalog/layout.blade.php'));
        $this->assertStringContainsString('.btn:hover', $layout);
        $this->assertStringContainsString('.btn.secondary:hover', $layout);
        $this->assertStringContainsString('button:focus-visible', $layout);
    }

    public function test_date_picker_disables_fully_booked_dates(): void
    {
        $js = File::get(public_path('js/berkah-controls.js'));

        $this->assertStringContainsString('data-fc-disabled', $js);
        $this->assertStringContainsString('parseBlockedDates', $js);
        $this->assertStringContainsString('is-blocked', $js);
        $this->assertStringContainsString('sudah dibooking', $js);

        // Rentang yang melewati hari penuh dan tanggal yang sudah dipakai
        // unit lain di keranjang juga ditolak sebelum formulir dikirim.
        $this->assertStringContainsString('validateRangeConflicts', $js);
        $this->assertStringContainsString('peerBlockedFor', $js);
        $this->assertStringContainsString('Rentang tanggal memuat', $js);

        // Nilai yang sudah terisi tetapi ternyata penuh diberi galat sejak
        // halaman dimuat, dan ketersediaan disegarkan saat kalender dibuka.
        $this->assertStringContainsString('reportDateProblem', $js);
        $this->assertStringContainsString('maybeRefresh', $js);
        $this->assertStringContainsString('applyStock', $js);
        $this->assertStringContainsString('data-fc-refresh-url', $js);

        // Rentang yang melewati tanggal milik unit lain juga menyebut sebabnya.
        $this->assertStringContainsString('kurangi jumlah unit', $js);

        // Tanggal kembali tidak boleh mendahului tanggal acara.
        $this->assertStringContainsString('syncRangeBounds', $js);
        $this->assertStringContainsString('tidak boleh sebelum tanggal acara', $js);

        $checkout = File::get(resource_path('views/rental/checkout.blade.php'));
        $this->assertStringContainsString('data-fc-range="unit-', $checkout);
        $this->assertStringContainsString('data-fc-stock=', $checkout);
        $this->assertStringContainsString('data-fc-unit=', $checkout);
        $this->assertStringContainsString('data-fc-refresh-url="', $checkout);

        $detail = File::get(resource_path('views/catalog/show.blade.php'));
        $this->assertStringContainsString('data-fc-range="catalog-availability"', $detail);
        $this->assertStringContainsString('data-fc-refresh-url="', $detail);

        $css = File::get(public_path('css/berkah-controls.css'));
        $this->assertStringContainsString('.fc-cal__day.is-blocked', $css);
    }

    public function test_admin_dark_theme_links_stay_readable(): void
    {
        // `.adm-app a` (0,1,1) mengalahkan `.adm-user__name` (0,1,0). Sebelum
        // token dipisah, nama pengguna dan "Ubah profil" di bilah sisi gelap
        // tercetak wine #85092d di atas kartu #410418 — kontras ±1,5:1.
        $admin = File::get(public_path('css/berkah-admin.css'));

        $this->assertStringContainsString('--adm-link: #85092d;', $admin);
        $this->assertStringContainsString('--adm-link: #f3a8bb;', $admin);
        $this->assertStringContainsString('.adm-app a { color: var(--adm-link); text-underline-offset: 3px; }', $admin);
        $this->assertStringContainsString(
            '.adm-sidebar :is(.adm-brand, .adm-user__name, .adm-user__edit) { color: #fff; }',
            $admin,
            'Tautan di bilah sisi gelap harus tetap putih.'
        );

        // Wine pekat sebagai warna hover juga hilang di permukaan gelap.
        $this->assertMatchesRegularExpression(
            '/html\[data-theme="dark"\] \.adm-app :is\(\.adm-btn--ghost, \.adm-btn--quiet\):hover \{\s*color: #fff;/',
            $admin
        );
    }

    public function test_fokus_dialog_kembali_ke_logo_bila_pemicu_tersembunyi(): void
    {
        $script = File::get(public_path('js/berkah-experience.js'));

        // Pemicu dialog (mis. tombol filter) bisa disembunyikan setelah filter
        // diterapkan. Memfokuskannya gagal, fokus jatuh ke <body>, jadi tombol
        // logo dipakai sebagai jaring pengaman.
        $this->assertStringContainsString('const back = trigger && (!header.contains(trigger)', $script);
        $this->assertStringContainsString('document.activeElement !== back', $script);
        $this->assertStringContainsString('focusWithoutScroll(expand);', $script);
    }

    public function test_dialog_tema_mengembalikan_fokus_ke_tombol_busur(): void
    {
        $kit = File::get(public_path('js/berkah-ui-kit.js'));

        // Di ponsel tombol tema tinggal di dalam busur, dan busur ditutup saat
        // tombolnya ditekan. Memfokuskan tombol yang kini tersembunyi di
        // <details> tertutup membuat fokus jatuh ke <body>, jadi fokus naik ke
        // <summary> busur.
        $this->assertStringContainsString('hiddenByClosedDetails', $kit);
        $this->assertStringContainsString("node.tagName === 'DETAILS' && !node.open", $kit);
        $this->assertStringContainsString("node.querySelector('summary') !== el", $kit);
        $this->assertStringContainsString("closest('details[data-customer-radial]')", $kit);
        $this->assertStringContainsString('usable(radialSummary) ? radialSummary : toggleButton', $kit);
    }

    public function test_checkboxes_share_one_brand_style(): void
    {
        $kit = File::get(public_path('css/berkah-ui-kit.css'));
        $theme = File::get(public_path('css/berkah-theme.css'));

        // Satu rupa untuk ingat saya, syarat & ketentuan, alamat utama,
        // konfirmasi admin, dan voucher aktif.
        $this->assertStringContainsString('input[type="checkbox"]:not([hidden])', $kit);
        $this->assertStringContainsString('--bk-check-on: #85092d;', $kit);
        $this->assertStringContainsString('input[type="checkbox"]:not([hidden]):checked', $kit);
        $this->assertStringContainsString('input[type="checkbox"]:not([hidden]):focus-visible', $kit);

        // Di tema gelap garis kotak tidak boleh sama dengan permukaan kartu.
        $this->assertStringContainsString('--bk-check-line: #7a5560;', $theme);

        // Lebar kotak ditentukan satu aturan; jangan disisipi inline style.
        foreach (['rental/checkout', 'rental/unit'] as $view) {
            $html = File::get(resource_path('views/'.$view.'.blade.php'));
            $this->assertStringNotContainsString('style="width:auto" type="checkbox"', $html, $view);
        }
    }

    /** Pesan validasi bawaan peramban berbahasa Inggris ("Please fill out this
     *  field."). Seluruh antarmuka berbahasa Indonesia, jadi pesannya diganti
     *  lewat setCustomValidity() — tanpa mengubah required/type/pattern, supaya
     *  form tetap tervalidasi dan tetap terkirim tanpa JS. */
    public function test_pesan_validasi_form_berbahasa_indonesia(): void
    {
        $js = File::get(public_path('js/berkah-ui-kit.js'));

        $this->assertStringContainsString("addEventListener('invalid'", $js);
        $this->assertStringContainsString('setCustomValidity(pesan)', $js);
        $this->assertStringContainsString("valueMissing: 'Bagian ini wajib diisi.'", $js);

        // Sembilan jenis pelanggaran yang bisa dijelaskan tanpa istilah asing.
        foreach ([
            'valueMissing', 'typeMismatch', 'patternMismatch', 'tooShort', 'tooLong',
            'rangeUnderflow', 'rangeOverflow', 'stepMismatch', 'badInput',
        ] as $key) {
            $this->assertStringContainsString($key.':', $js, 'Jenis pelanggaran belum diterjemahkan: '.$key);
        }

        // Pesan tidak boleh terkunci: penanda dilepas saat pengguna mengetik.
        $this->assertStringContainsString("addEventListener('input', (event) => lepas(event.target), true)", $js);
        $this->assertStringContainsString("addEventListener('change', (event) => lepas(event.target), true)", $js);

        // Pesan milik kode lain (mis. aturan bisnis) tidak ditimpa.
        $this->assertStringContainsString('if (validity.customError && !milikKita) return;', $js);

        // Pesan yang tertinggal (autofill/skrip, tanpa peristiwa input) tidak
        // boleh menahan pengiriman: penanda dilepas sebelum validasi ulang.
        $this->assertStringContainsString("addEventListener('submit', (event) => bersihkan(event.target), true)", $js);
        $this->assertStringContainsString("addEventListener('reset', (event) => bersihkan(event.target), true)", $js);

        // Tidak ada teks Inggris yang tersisa di berkas pendamping ini.
        $this->assertStringNotContainsString('Please fill out this field', $js);
        $this->assertStringNotContainsString('Please enter', $js);
    }

    /** Setiap `var(--adm-*)` di CSS admin harus benar-benar terdefinisi.
     *
     *  Salah satu blok baru memakai `var(--adm-line-2)` yang tidak pernah
     *  dideklarasikan: peramban jatuh ke `currentColor`, sehingga garis hover
     *  berubah warna mengikuti teks dan tidak ada yang menyadarinya. Tes ini
     *  menangkap salah tulis token sebelum sampai ke layar. Variabel yang
     *  memang disetel JS (`--adm-i`, `--adm-toast-life`) selalu punya nilai
     *  cadangan sehingga dilewati.
     */
    public function test_token_admin_yang_dipakai_selalu_terdefinisi(): void
    {
        $css = File::get(public_path('css/berkah-admin.css'));

        $defined = [];
        preg_match_all('/(--adm-[a-z0-9-]+)\s*:/', $css, $defs);
        foreach ($defs[1] as $name) {
            $defined[$name] = true;
        }

        $offenders = [];

        preg_match_all('/var\(\s*(--adm-[a-z0-9-]+)\s*([,)])/', $css, $uses, PREG_SET_ORDER);

        foreach ($uses as $use) {
            $name = $use[1];
            $hasFallback = $use[2] === ',';

            if (! isset($defined[$name]) && ! $hasFallback) {
                $offenders[$name] = true;
            }
        }

        $this->assertSame([], array_keys($offenders), 'Token admin tanpa definisi: '.implode(', ', array_keys($offenders)));
    }

    /** Kontras token teks admin: minimal 4,5:1 di semua permukaan.
     *
     *  Audit 2026-11-25 menemukan `--adm-ink-3` (#93787f) hanya 3,6–3,9:1 di
     *  atas permukaan admin, padahal ia dipakai `.adm-help`, `.adm-stat__hint`,
     *  `.adm-timeline__time`, dan placeholder kontrol bersama — semuanya teks
     *  kecil. Tes ini menghitung rasio kontras WCAG dari CSS itu sendiri
     *  (bukan dari nilai yang disalin ke tes) untuk tema terang dan gelap.
     */
    public function test_kontras_teks_admin_minimal_aa(): void
    {
        $css = File::get(public_path('css/berkah-admin.css'));

        $this->assertSame(1, preg_match('/:root\s*\{(.*?)\}/s', $css, $light), ':root admin tidak ditemukan.');
        $this->assertSame(1, preg_match('/html\[data-theme="dark"\]\s*\{(.*?)\}/s', $css, $dark), 'Blok gelap admin tidak ditemukan.');

        $pasangan = [
            ['ink', 'surface'],
            ['ink', 'surface-2'],
            ['ink', 'surface-3'],
            ['ink-2', 'surface'],
            ['ink-2', 'surface-3'],
            ['ink-3', 'surface'],
            ['ink-3', 'surface-2'],
            ['ink-3', 'surface-3'],
            ['link', 'surface'],
            ['warn', 'warn-soft'],
            ['ok', 'ok-soft'],
            ['danger', 'danger-soft'],
            ['info', 'info-soft'],
        ];

        foreach (['terang' => $light[1], 'gelap' => $dark[1]] as $tema => $blok) {
            foreach ($pasangan as [$fg, $bg]) {
                $warnaFg = $this->ambilToken($blok, '--adm-'.$fg);
                $warnaBg = $this->ambilToken($blok, '--adm-'.$bg);

                $this->assertNotNull($warnaFg, 'Token --adm-'.$fg.' hilang pada tema '.$tema.'.');
                $this->assertNotNull($warnaBg, 'Token --adm-'.$bg.' hilang pada tema '.$tema.'.');

                $rasio = $this->rasioKontras($warnaFg, $warnaBg);

                $this->assertGreaterThanOrEqual(
                    4.5,
                    $rasio,
                    'Kontras --adm-'.$fg.' di atas --adm-'.$bg.' ('.$tema.') hanya '.round($rasio, 2).':1.'
                );
            }
        }
    }

    private function ambilToken(string $blok, string $nama): ?string
    {
        return preg_match('/'.preg_quote($nama, '/').'\s*:\s*(#[0-9a-fA-F]{6})/', $blok, $m) === 1 ? $m[1] : null;
    }

    private function rasioKontras(string $a, string $b): float
    {
        $la = $this->luminansi($a);
        $lb = $this->luminansi($b);
        $terang = max($la, $lb);
        $gelap = min($la, $lb);

        return ($terang + 0.05) / ($gelap + 0.05);
    }

    private function luminansi(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $kanal = [];

        foreach ([0, 2, 4] as $offset) {
            $c = hexdec(substr($hex, $offset, 2)) / 255;
            $kanal[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $kanal[0] + 0.7152 * $kanal[1] + 0.0722 * $kanal[2];
    }
}
