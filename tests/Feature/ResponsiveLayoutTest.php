<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Mencegah bug visual berulang:
 *  1. Setiap ukuran viewport `dvh` wajib punya fallback `vh` (WebView lama
 *     mengabaikan dvh sehingga dialog tumbuh melebihi layar dan terpotong).
 *  2. Dialog/lembar filter harus punya batas tinggi + padding safe area.
 *  3. Header ringkas tidak boleh memakai flag permanen yang membuat logo
 *     menghilang setelah diketuk lalu halaman digulir.
 */
class ResponsiveLayoutTest extends TestCase
{
    /** @return array<string, string> nama sumber => isi CSS */
    private function cssSources(): array
    {
        $sources = [];

        foreach (File::files(public_path('css')) as $file) {
            if (str_ends_with($file->getFilename(), '.css')) {
                $sources['public/css/'.$file->getFilename()] = File::get($file->getPathname());
            }
        }

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $text = File::get($file->getPathname());
            if (! str_contains($text, '<style')) {
                continue;
            }
            preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $text, $matches);
            foreach ($matches[1] as $index => $css) {
                $sources[str_replace('\\', '/', $file->getRelativePathname()).'#style'.$index] = $css;
            }
        }

        return $sources;
    }

    public function test_every_dvh_declaration_has_a_vh_fallback(): void
    {
        $problems = [];

        foreach ($this->cssSources() as $name => $css) {
            $css = preg_replace('!/\*.*?\*/!s', '', $css);
            preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $blocks, PREG_SET_ORDER);

            foreach ($blocks as $block) {
                $body = $block[2];
                if (! str_contains($body, 'dvh')) {
                    continue;
                }

                preg_match_all('/([a-z-]+)\s*:\s*([^;{}]+)/i', $body, $declarations, PREG_SET_ORDER);
                $seen = [];

                foreach ($declarations as $declaration) {
                    $property = strtolower(trim($declaration[1]));
                    $value = trim($declaration[2]);

                    if (str_contains($value, 'dvh')) {
                        $hasFallback = false;
                        foreach ($seen as $previous) {
                            if ($previous['property'] === $property && str_contains($previous['value'], 'vh')) {
                                $hasFallback = true;
                                break;
                            }
                        }
                        if (! $hasFallback) {
                            $problems[] = $name.' → '.$property.': '.$value;
                        }
                    }

                    $seen[] = ['property' => $property, 'value' => $value];
                }
            }
        }

        $this->assertSame([], $problems, "Deklarasi dvh tanpa fallback vh:\n".implode("\n", $problems));
    }

    public function test_filter_dialog_stays_inside_the_viewport_on_every_screen(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertMatchesRegularExpression(
            '/\.bk-dialog\s*\{[^}]*max-height:\s*calc\(100vh - [^)]*\)[^}]*max-height:\s*calc\(100dvh/',
            $css,
            'Dialog harus dibatasi dengan fallback vh lalu dvh.'
        );
        $this->assertStringContainsString('padding-bottom: calc(clamp(16px, 4.2vw, 26px) + env(safe-area-inset-bottom', $css);
        $this->assertStringContainsString('overscroll-behavior: contain', $css);
        $this->assertMatchesRegularExpression('/@media \(max-width: 700px\)\s*\{\s*\.bk-dialog\s*\{[^}]*width:\s*calc\(100% - 16px\)/s', $css);
        $this->assertStringContainsString('[data-filter-form] > p:last-of-type', $css);
        $this->assertStringContainsString('position: sticky', $css);
    }

    public function test_compact_header_cannot_disappear_after_becoming_pinned(): void
    {
        $script = File::get(public_path('js/berkah-experience.js'));
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertStringContainsString('is-pinned', $script);
        $this->assertStringContainsString('is-pinned', $css);
        $this->assertStringContainsString('.bk-header.is-pinned', $css);
        $this->assertStringNotContainsString('holdOpen', $script, 'Flag holdOpen permanen membuat logo menghilang setelah digulir.');

        // Header ringkas tetap bisa muncul lagi setelah menu ditutup.
        $this->assertStringContainsString('radialMenu.addEventListener(\'toggle\'', $script);
        $this->assertStringContainsString("header.classList.add('is-compact'", $script);
        $this->assertStringContainsString("header.classList.remove('is-compact', 'is-pinned')", $script);
    }

    /** Perpindahan navbar penuh ⇄ pil ringkas dianimasikan, bukan melompat.
     *
     *  Kelas `is-compact` tetap dipasang seperti biasa (dijaga tes lain);
     *  animasinya membungkus perubahan itu: ukuran kotak header dianimasikan
     *  per frame, isi lebar memudar, dan logo pil tumbuh dari tengah. */
    public function test_header_morphs_between_full_navbar_and_compact_pill(): void
    {
        $script = File::get(public_path('js/berkah-experience.js'));
        $motion = File::get(public_path('css/berkah-motion.css'));
        $css = File::get(public_path('css/berkah-experience.css'));

        // Pembungkus morph ada, dan kelas tetap dipasang lewat jalur lama.
        $this->assertStringContainsString('morphHeader', $script);
        $this->assertStringContainsString("header.classList.add('is-compact'", $script);
        $this->assertStringContainsString("header.classList.remove('is-compact', 'is-pinned')", $script);
        $this->assertStringContainsString('getBoundingClientRect', $script);
        $this->assertStringContainsString("clearProps: 'width,height,transform'", $script);

        // FLIP juga menutup selisih POSISI absolut ⇄ fixed. Tanpa itu header
        // melompat 70 px saat berganti mode tepat di tepi atas layar.
        $this->assertStringContainsString('xPercent: -50', $script);
        $this->assertStringContainsString('shiftY', $script);

        // Morph beruntun saat gulir cepat dipasang langsung, bukan ditumpuk.
        $this->assertStringContainsString('morphStamp', $script);
        $this->assertMatchesRegularExpression(
            '/stampNow - morphStamp < 320[\s\S]{0,500}mutate\(\);/',
            $script,
            'Morph kedua dalam 320 ms harus dipasang langsung agar header tidak tertinggal.'
        );

        // Tanpa GSAP / saat gerak dimatikan, kelas dipasang langsung.
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $script);
        $this->assertMatchesRegularExpression('/motionAllowed[\s\S]{0,200}mutate\(\);\s*return;/', $script);

        // Transisi `width` dilepas: dengan tween per frame, transisi CSS akan
        // membuat header tertinggal satu langkah di belakang posisinya.
        $this->assertStringNotContainsString('transition:width .3s ease,top .3s ease', $css);
        $this->assertStringContainsString('transition:top .3s ease', $css);

        // Isi menyusul: padding/sudut membulat ikut menyusut, isi lebar memudar,
        // logo pil tumbuh. Semuanya bisa dimatikan lewat prefers-reduced-motion.
        $this->assertStringContainsString('.bk-header--morphing .bk-header-inner', $motion);
        $this->assertStringContainsString('bk-header-pill-in', $motion);
        $this->assertStringContainsString('bk-header-nav-in', $motion);
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)[\s\S]{0,400}bk-header--morphing/',
            $motion,
            'Morph header harus berhenti saat gerak diminimalkan.'
        );
    }

    /** Tidak boleh ada zona mati antara navbar penuh dan pil ringkas.
     *
     *  Ambang lama (180 px) jauh di bawah tepi bawah navbar penuh (±86 px),
     *  jadi selama ±94 px gulir tidak ada header sama sekali — persis keluhan
     *  "navbar tidak terlihat ketika gulir berhenti di dekat atas". Ambangnya
     *  sekarang diukur dari kotak header sendiri, dengan histeresis 8 px
     *  supaya pil tidak berkedip tepat di ambang.
     */
    public function test_header_never_disappears_between_full_navbar_and_pill(): void
    {
        $script = File::get(public_path('js/berkah-experience.js'));

        $this->assertStringNotContainsString('window.scrollY > 180', $script, 'Ambang tetap 180 px menyisakan celah tanpa header.');
        $this->assertStringContainsString('headerExitPoint', $script);

        // Pil muncul tepat saat tepi bawah navbar penuh lewat dari atas layar…
        $this->assertMatchesRegularExpression('/compactAt[^\n]*headerExitPoint\(\) - 2/', $script);
        // …dan baru pergi lagi 8 px lebih ke atas, jadi tidak berkedip.
        $this->assertMatchesRegularExpression('/expandAt[^\n]*headerExitPoint\(\) - 10/', $script);

        // Titik keluar hanya diukur saat header masih penuh: setelah ringkas
        // header `fixed` dan tidak lagi mencerminkan posisinya di dokumen.
        $this->assertMatchesRegularExpression(
            "/is-compact'\) \|\| header\.classList\.contains\('is-pinned'\)/",
            $script
        );
        $this->assertStringContainsString("header.classList.contains('bk-header--morphing')", $script);

        // Satu sinkronisasi per frame, bukan satu per event scroll.
        $this->assertStringContainsString('queueHeaderSync', $script);
        $this->assertStringContainsString('requestAnimationFrame', $script);
    }

    /** Ikon garis-3 duduk di tengah tombolnya.
     *
     *  Dulu kotak ikon adalah grid kecil di dalam <summary>; pada sebagian
     *  peramban isinya terdorong ke tepi kanan lingkaran sehingga tiga garis
     *  tampak tidak simetris. Sekarang kotak tombol itu sendiri (empat sisi,
     *  ukuran otomatis) yang memusatkan ikon, dan ikon digambar pada 24 px —
     *  kelipatan bulat viewBox 24 — supaya garis 2 px jatuh di piksel bulat.
     */
    public function test_radial_menu_icon_sits_in_the_middle_of_its_button(): void
    {
        $radial = File::get(resource_path('views/layouts/component/customer/_radial_customer.blade.php'));
        $nav = File::get(resource_path('views/layouts/component/customer/_navbar_customer.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.bk-header-radial \.customer-radial__trigger\{position:absolute;top:0;right:0;bottom:0;left:0;[^}]*display:flex;[^}]*justify-content:center/',
            $radial,
            'Kotak tombol harus menjadi kotak ikon (empat sisi + flex tengah).'
        );
        $this->assertMatchesRegularExpression(
            '/\.bk-header-radial \.customer-radial__face\{position:absolute;top:0;right:0;bottom:0;left:0;[^}]*justify-content:center/',
            $radial,
            'Kedua wajah ikon (garis-3 dan silang) harus memusatkan isinya.'
        );
        $this->assertStringContainsString(
            '.bk-header-radial>summary svg{display:block;flex:none;margin:0;width:24px;height:24px}',
            $radial,
            'Ikon tombol harus 24 px agar goresan 2 px tidak jatuh di piksel pecahan.'
        );

        // Ukuran lama yang menimpa kotak tombol dihapus: 44/100% membuat ikon
        // melewati padding <summary> dan bergeser ke kanan.
        $this->assertStringNotContainsString('customer-radial__trigger{width:44px;height:44px}', $nav);
        $this->assertStringNotContainsString('customer-radial__trigger{width:100%;height:100%', $radial);
    }

    /** Halaman pelanggan tidak boleh bisa digeser mendatar.
     *
     *  Dekorasi yang lebih lebar dari kolomnya membuat peramban menampilkan
     *  scrollbar mendatar; seluruh halaman ikut bisa tergeser sehingga pil
     *  header tampak tidak di tengah. `overflow-x: clip` memotongnya tanpa
     *  menjadikan <body> kotak gulir (beda dengan `hidden`), jadi sticky dan
     *  gulir halus tetap bekerja.
     */
    public function test_customer_pages_cannot_scroll_sideways(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertMatchesRegularExpression(
            '/body\.bk-experience\s*\{\s*overflow-x:\s*clip/',
            $css,
            'Luapan mendatar halaman pelanggan harus dipotong, bukan dibiarkan membuat scrollbar.'
        );
    }

    /** Tidak ada satu pun elemen yang boleh melebarkan dokumen.
     *
     *  Laporan lapangan 2026-11-25: halaman `sewa/{id}/unit/{id}` pada 437–440 px
     *  masih bisa digeser ke samping walau `body` sudah `overflow-x: clip`.
     *  Penyebabnya dua lapis: `overflow` milik <body> diwariskan ke viewport
     *  (jadi <body> berhenti menjadi kotak pemotong), dan anak grid/flex
     *  memakai `min-width: auto` = min-content sehingga deretan pil
     *  "Lihat bagian" mendorong kolomnya melewati layar.
     */
    public function test_elemen_lebar_tidak_bisa_mendorong_halaman_ke_samping(): void
    {
        $css = preg_replace('/\s+/', ' ', File::get(public_path('css/berkah-experience.css')));

        $this->assertStringContainsString(
            'html.bk-enhanced:has(> body.bk-experience) { overflow-x: clip; }',
            $css,
            'Aturan clip harus dipasang juga di <html>; clip pada <body> saja diwariskan ke viewport.'
        );
        $this->assertStringContainsString(
            '.bk-experience :is(.wrap, .items, .grid, .stack) > * { min-width: 0; }',
            $css,
            'Anak grid/flex wajib boleh menyusut (min-width: 0) agar min-content tidak melebarkan halaman.'
        );
        $this->assertStringContainsString('.bk-experience .bk-jump > div { flex: 1 1 auto; min-width: 0;', $css,
            'Deretan pil "Lihat bagian" harus menjadi scroller yang menyusut, bukan mendorong induknya.');
        $this->assertStringContainsString('.bk-experience .wrap { max-width: 100%; overflow-x: clip; }', $css,
            'Isi halaman dipotong di tepi .wrap supaya satu baris panjang tidak melebarkan dokumen.');
        $this->assertStringContainsString('.bk-experience .bk-unit-jump { max-width: 100%; }', $css,
            'Baris pil versi halaman unit harus dibatasi lebar induknya.');
    }

    /** Nomor halaman tidak boleh terpotong di layar 320 px.
     *
     *  Dua sistem paginasi dulu hidup berdampingan: `.bk-pagination`
     *  (pelanggan, `width:max-content` tanpa `flex-wrap`) dan tampilan bawaan
     *  Laravel (kelas Tailwind, tanpa CSS). Sekarang satu: `.bk-page-nav*`
     *  dengan `flex-wrap: wrap` dan butir 44 px, dipakai semua peran lewat
     *  `Paginator::defaultView`.
     */
    public function test_navigasi_halaman_turun_baris_di_layar_sempit(): void
    {
        $css = preg_replace('/\s+/', ' ', File::get(public_path('css/berkah-ui-kit.css')));
        $alias = File::get(resource_path('views/vendor/pagination/berkah.blade.php'));
        $experience = File::get(public_path('css/berkah-experience.css'));

        $this->assertMatchesRegularExpression('/\.bk-page-nav \{[^}]*flex-wrap: wrap;/', $css);
        $this->assertStringNotContainsString('width: max-content', $css, 'Paginasi tidak boleh selebar isinya.');
        $this->assertStringContainsString("@include('vendor.pagination.token-simple')", $alias, 'Alih paginasi pelanggan harus memakai tampilan yang sama.');
        $this->assertStringNotContainsString('.bk-pagination', $experience, 'Aturan paginasi lama harus dicabut.');

        // Dulu ada tiga sistem paginasi. Dua berkas lama kini hanya alias, dan
        // tidak satu pun boleh membawa <style> sendiri.
        foreach (['berkah.blade.php', 'customer-simple.blade.php'] as $alias) {
            $isi = File::get(resource_path('views/vendor/pagination/'.$alias));
            $markup = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $isi);

            $this->assertStringContainsString("@include('vendor.pagination.token-simple')", $isi, $alias.' harus memakai tampilan bersama.');
            $this->assertStringNotContainsString('<style', $markup, $alias.' tidak boleh membawa gaya sendiri.');
        }
    }

    public function test_kolom_formulir_admin_sejajar_dari_label(): void
    {
        $css = preg_replace('/\s+/', ' ', File::get(public_path('css/berkah-admin.css')));

        $this->assertMatchesRegularExpression(
            '/\.adm-field-grid \{[^}]*align-items: start;/',
            $css,
            'Dengan `align-items: end` kolom yang punya teks bantuan naik dan labelnya tidak sejajar.'
        );
    }

    /** Satu ritme tipografi untuk judul halaman, paragraf, dan kartu.
     *
     *  Sebelumnya jarak di dalam kartu mengikuti margin bawaan peramban (`p`
     *  1em atas-bawah), sehingga paragraf pertama menambah ±16 px di bawah
     *  judul dan paragraf terakhir menambah ±16 px di atas padding kartu.
     *  Ukuran `h1` halaman pun berbeda-beda: 32 px bawaan di halaman unit,
     *  52 px di dasbor. Sekarang judul halaman memakai satu skala (dengan
     *  specificity nol lewat `:where()` supaya hero/dasbor tetap menang), dan
     *  paragraf kartu memakai satu jarak.
     */
    public function test_ritme_tipografi_kartu_sama_di_semua_halaman(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertStringContainsString('.bk-experience :where(.wrap > h1)', $css);
        $this->assertMatchesRegularExpression(
            '/:is\(\.card, \.unit-section, \.bk-row-card, \.notice, \.bk-profile-card\) p\s*\{[^}]*margin: 0 0 var\(--bk-space-3\)[^}]*line-height: 1\.65/',
            $css,
            'Paragraf di dalam kartu harus memakai satu jarak (12 px antarparagraf, 1.65 baris).'
        );
        $this->assertStringContainsString('overflow-wrap: break-word', $css, 'Kata panjang tidak boleh melebarkan kartu.');

        // Kartu yang mengatur jarak lewat `gap` flex tidak boleh ikut ditimpa.
        $this->assertStringNotContainsString(':is(.card, .unit-section, .bk-row-card, .notice, .bk-order-card', $css);
    }

    public function test_customer_bottom_navigation_does_not_cover_content(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\)\s*\{\s*body\.bk-customer\s*\{\s*padding-bottom:\s*calc\(84px \+ env\(safe-area-inset-bottom/',
            $css,
            'Navigasi bawah pelanggan (68 px + safe area) butuh padding body.'
        );
    }

    public function test_catalog_header_respects_the_notch_and_keeps_the_theme_slot(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));
        $kit = File::get(public_path('css/berkah-ui-kit.css'));
        $nav = File::get(resource_path('views/catalog/customer-navigation.blade.php'));

        // Header melayang di atas hero: jarak atas dan ruang cadangannya harus
        // ikut safe-area, kalau tidak header masuk ke bawah status bar/poni.
        $this->assertStringContainsString('top:calc(env(safe-area-inset-top, 0px) + 16px)', $css);
        $this->assertStringContainsString('height:calc(env(safe-area-inset-top, 0px) + 96px)', $css);
        $this->assertStringContainsString('top:calc(env(safe-area-inset-top, 0px) + 12px)', $css);
        // Lompat ke #koleksi tidak boleh mendarat di bawah pil header.
        $this->assertStringContainsString('scroll-margin-top:calc(env(safe-area-inset-top, 0px) + 92px)', $css);

        // Di ponsel slot tema tetap berada di header. Dulu seluruh aksi desktop
        // disembunyikan sehingga tombol tema jatuh ke dok mengambang dan
        // menutupi kartu produk di bawahnya.
        $this->assertStringContainsString('.bk-desktop-actions{display:flex;align-items:center;gap:3px}', $css);
        $this->assertMatchesRegularExpression(
            '/\.bk-desktop-actions > a \{\s*display: none;/',
            $css,
            'Tautan akun di header ponsel harus disembunyikan; slot tema tetap tampil.'
        );
        $this->assertStringContainsString('.bk-header.is-compact .bk-desktop-actions{display:flex}', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/@media\s*\(max-width:\s*700px\)\s*\{\s*\.bk-desktop-actions\s*\{\s*display:\s*none/',
            $css,
            'Wadah aksi tidak boleh disembunyikan seluruhnya di ponsel; slot tema ada di dalamnya.'
        );
        $this->assertMatchesRegularExpression(
            '/\.bk-theme-host \{\s*display: flex;/',
            $kit,
            'Slot tema harus tampil di header ponsel.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/@media \(max-width: 700px\) \{\s*\.bk-theme-host \{\s*display: none/',
            $kit,
            'Slot tema tidak boleh disembunyikan lagi di ponsel.'
        );
        $this->assertStringContainsString('data-bk-theme-host', $nav);
    }

    public function test_mobile_catalog_rhythm_uses_scroll_snap_and_44px_targets(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));
        $kit = File::get(public_path('css/berkah-ui-kit.css'));
        $motion = File::get(public_path('js/berkah-motion.js'));

        // Strip tiga langkah bergulir mendatar: snap + tepi kanan memudar
        // sebagai isyarat bahwa masih ada langkah berikutnya.
        $this->assertStringContainsString('scroll-snap-type:x proximity', $css);
        $this->assertStringContainsString('mask-image:linear-gradient(90deg,#000 calc(100% - 26px),transparent)', $css);

        // Sasaran sentuh minimum 44 px di chip kategori dan stepper jumlah.
        $this->assertMatchesRegularExpression(
            '/\.bk-category-strip a \{\s*min-height: 44px;/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\) \{\s*\.bk-qty__btn \{\s*width: 44px;\s*height: 44px;/',
            $kit
        );

        // Lenis tidak boleh menahan gulir mendatar pada dua wadah itu.
        $this->assertStringContainsString("'.bk-how', '.bk-category-strip'", $motion);
    }

    /** Header lama yang dipakai admin saat meninjau katalog publik masih
     *  hidup di layout katalog. Tautannya dulu teks tanpa tinggi, jadi di
     *  ponsel sasarannya di bawah 44 px — sekarang setinggi sasaran sentuh. */
    public function test_header_katalog_untuk_admin_punya_tautan_setinggi_sasaran_sentuh(): void
    {
        $layout = File::get(resource_path('views/catalog/layout.blade.php'));

        $this->assertStringContainsString(
            '.nav a{color:white;font-size:var(--bk-fs-sm);font-weight:700;text-decoration:none;min-height:44px;display:inline-flex;align-items:center}',
            $layout
        );
        // Di bawah 650 px tautan tetap membungkus selebar header.
        $this->assertStringContainsString('.top .nav{width:100%;margin-left:0;gap:6px 16px}', $layout);
    }

    /** Catatan admin adalah spanduk di atas chipbar: jaraknya satu skala
     *  dengan blok di bawahnya, lebarnya selebar blok itu, dan aksen kirinya
     *  tidak menonjol keluar lengkung kartu. */
    public function test_admin_note_shares_the_rhythm_of_the_blocks_below_it(): void
    {
        $css = File::get(public_path('css/berkah-admin.css'));

        // Jarak antar-kelompok 16 px, sama dengan margin bawah chipbar.
        $this->assertStringContainsString('padding: 12px 14px 12px 16px; margin: 0 0 16px;', $css);
        $this->assertStringContainsString('.adm-chipbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0 0 16px; }', $css);

        // Aksen kiri = lapisan di dalam kartu, dipotong radius kartu.
        $this->assertStringContainsString('.adm-note::before {', $css);
        $this->assertStringContainsString('overflow: hidden; overflow-wrap: anywhere;', $css);

        // Catatan bukan paragraf bacaan: tidak ikut batas 68ch/78ch.
        $this->assertStringNotContainsString('.adm-app :is(.adm-page-sub, .adm-help, .adm-note, .adm-card__body)', $css);
        $this->assertStringContainsString('.adm-app .adm-note { max-width: none; }', $css);
    }

    public function test_catalog_filter_uses_a_single_clear_title(): void
    {
        $catalog = File::get(resource_path('views/catalog/index.blade.php'));

        $this->assertStringContainsString('id="bk-filter-title">Cari papan<', $catalog);
        $this->assertStringContainsString('<label for="catalog-search">Kata kunci</label>', $catalog);
        $this->assertStringNotContainsString('<label for="catalog-search">Cari papan</label>', $catalog);
    }

    /** Target sentuh halaman admin minimal 44 px di layar sentuh.
     *
     *  `.adm-btn` sudah 44 px, tetapi varian kecil (38 px), tombol kuiet
     *  (40 px), chip tautan filter (40 px), dan ikon bulat topbar (40 px)
     *  belum. Di bawah 700 px / perangkat berujung sentuh semuanya dinaikkan;
     *  desktop tidak berubah.
     */
    public function test_target_sentuh_admin_minimal_44_px(): void
    {
        $css = File::get(public_path('css/berkah-admin.css'));

        $this->assertStringContainsString('@media (pointer: coarse), (max-width: 700px) {', $css);

        foreach (['.adm-btn--sm', '.adm-btn--quiet', '.adm-chip--link', '.adm-nav__link', '.adm-tab'] as $selector) {
            $this->assertMatchesRegularExpression(
                '/@media \(pointer: coarse\), \(max-width: 700px\) \{[^}]*'.preg_quote($selector, '/').'/s',
                $css,
                $selector.' harus ikut naik ke 44 px di layar sentuh.'
            );
        }

        $this->assertStringContainsString("    .adm-btn--sm,\n    .adm-btn--quiet,\n    .adm-chip--link,\n    .adm-nav__link,\n    .adm-tab {\n        min-height: 44px;\n    }", $css);
        $this->assertStringContainsString("    .adm-iconbtn {\n        width: 44px;\n        height: 44px;\n    }", $css);
    }

    /** Halaman galat harus utuh di lebar 320 px.
     *
     *  Merek di bawah kartu ditulis huruf besar dengan `letter-spacing: 2.4px`
     *  tanpa `flex-wrap`. Di 320 px ruang kartu hanya ~236 px, jadi baris itu
     *  mendorong keluar. Sekarang ia boleh membungkus dan jarak hurufnya
     *  dirapatkan di bawah 360 px.
     */
    public function test_halaman_galat_utuh_di_320_px(): void
    {
        $layout = File::get(resource_path('views/errors/layout.blade.php'));

        $this->assertStringContainsString('flex-wrap:wrap', $layout);
        $this->assertMatchesRegularExpression('/@media\(max-width:360px\)\{.*?\.brand\{[^}]*letter-spacing:1\.6px/s', $layout);
        // Kartu tidak boleh mengunci lebar tetap: `width:100%` + `max-width`.
        $this->assertStringContainsString('main{width:100%;max-width:440px;', $layout);
        $this->assertStringContainsString('overflow-wrap:anywhere', $layout);
    }

    /** Tombol ikon header katalog minimal 44 px.
     *
     *  `.bk-desktop-action` dulu 42×42, dan di 701–880 px menyusut lagi ke
     *  38×42 — di tablet berujung sentuh tombol keranjang/notifikasi jadi
     *  sasaran yang mudah meleset. Sekarang 44×44 di semua rentang; nav
     *  desktop ikut 44 px agar sederajat.
     */
    public function test_tombol_ikon_header_katalog_minimal_44_px(): void
    {
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertStringContainsString('.bk-desktop-action{position:relative;display:grid;place-items:center;width:44px;height:44px;', $css);
        $this->assertStringContainsString('.bk-desktop-nav a{display:inline-flex;align-items:center;min-height:44px;', $css);
        $this->assertStringNotContainsString('width:38px;height:42px', $css);
        $this->assertStringNotContainsString('min-height:42px;padding:6px 8px', $css);
    }

    /** Kontrol form bersama mengikuti palet admin, bukan palet katalog.
     *
     *  `_form_controls` mengganti setiap <select>/date/month dengan
     *  `.fc-trigger`. Sebelumnya trigger itu tetap 48 px, radius 14 px,
     *  latar putih, dan bayangan katalog — berdiri di samping label
     *  `.adm-field` 12 px dan input admin 44 px, sehingga satu baris
     *  formulir tampak meminjam dua sistem. Popover/sheet/kalender juga
     *  masih memakai `--fc-cream: #fffaf8` dan `--fc-muted: #7a626a`.
     */
    public function test_kontrol_form_bersama_mengikuti_palet_admin(): void
    {
        $css = File::get(public_path('css/berkah-admin.css'));

        $this->assertStringContainsString('--fc-cream: var(--adm-surface);', $css);
        $this->assertStringContainsString('--fc-line: var(--adm-line);', $css);
        $this->assertStringContainsString('--fc-ink: var(--adm-ink);', $css);
        $this->assertStringContainsString('--fc-radius: 12px;', $css);

        $this->assertStringContainsString('.adm-app .fc-trigger {', $css);
        $this->assertMatchesRegularExpression('/\.adm-app \.fc-trigger \{[^}]*min-height: 44px;/s', $css);
        $this->assertMatchesRegularExpression('/\.adm-app \.fc-trigger \{[^}]*border-radius: 12px;/s', $css);
        // Warna katalog tidak boleh tertinggal di aturan trigger admin.
        preg_match('/\.adm-app \.fc-trigger \{([^}]*)\}/s', $css, $rule);
        $this->assertNotEmpty($rule, 'Aturan trigger admin tidak ditemukan.');
        foreach (['#fff', '#fffaf8', '#7a626a', '#eadbd6'] as $katalog)
            $this->assertStringNotContainsString($katalog, $rule[1], 'Trigger admin masih memakai warna katalog: '.$katalog);
    }

    /** Isian lebar di grid formulir tidak boleh memaksa kolom implisit.
     *
     *  `.adm-field-grid` memakai `auto-fit minmax(min(100%,170px),1fr)`: di
     *  320 px hanya satu kolom. Lima isian pencarian ditulis
     *  `style="grid-column: span 2"` sehingga grid membuat kolom implisit
     *  kedua selebar isinya — halaman melebar, kolom terpotong. Sekarang
     *  semuanya memakai `.adm-field--wide` yang jatuh ke `1 / -1` di ≤700 px.
     */
    public function test_isian_lebar_tidak_memaksa_kolom_implisit(): void
    {
        $css = File::get(public_path('css/berkah-admin.css'));

        $this->assertStringContainsString('.adm-field--wide {', $css);
        $this->assertMatchesRegularExpression('/@media \(max-width: 700px\) \{\s*\.adm-field--wide \{ grid-column: 1 \/ -1; \}/s', $css);

        $pelanggaran = [];

        foreach (['rental/admin.blade.php', 'rental/universities.blade.php', 'rental/addons.blade.php'] as $relative) {
            $html = File::get(resource_path('views/'.$relative));

            if (str_contains($html, 'grid-column: span')) {
                $pelanggaran[] = $relative.' masih memakai grid-column: span';
            }

            $this->assertStringContainsString('adm-field--wide', $html, $relative.' belum memakai kelas isian lebar.');
        }

        $this->assertSame([], $pelanggaran, implode('; ', $pelanggaran));
    }

    /** Paginasi bersama: 44 px dan tanpa token yang tidak ada.
     *
     *  Setiap butir dulu memakai `style="padding:8px 12px"` (tinggi ~32 px)
     *  dengan `--brand-*`/`--surface-*` yang hanya didefinisikan di halaman
     *  mandiri. Di panel admin token itu tidak ada: tautan halaman kehilangan
     *  latar dan warna, dan sasarannya terlalu kecil. Sekarang seluruh gaya
     *  datang dari `.bk-page-nav*`.
     */
    public function test_paginasi_bersama_punya_sasaran_sentuh_44_px(): void
    {
        $view = File::get(resource_path('views/vendor/pagination/token-simple.blade.php'));
        $css = File::get(public_path('css/berkah-ui-kit.css'));
        $admin = File::get(public_path('css/berkah-admin.css'));

        // Komentar Blade berisi contoh gaya lama; yang diperiksa markupnya.
        $markup = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $view);
        $this->assertStringNotContainsString('style="', $markup, 'Paginasi tidak boleh memakai gaya sebaris.');
        $this->assertStringContainsString('class="bk-page-nav"', $view);
        $this->assertStringContainsString('aria-current="page"', $view);
        $this->assertStringContainsString('rel="prev"', $view);
        $this->assertStringContainsString('rel="next"', $view);
        $this->assertStringContainsString('@foreach ($elements ?? [] as $element)', $view,
            'Tampilan yang sama dipakai `Paginator::defaultSimpleView`; tanpa penjaga, `simplePaginate()` gagal karena tidak punya $elements.');

        $this->assertStringContainsString('.bk-page-nav__link,', $css);
        $this->assertMatchesRegularExpression('/\.bk-page-nav__link,\s*\n\.bk-page-nav__current,\s*\n\.bk-page-nav__gap \{[^}]*min-height: var\(--bk-touch, 44px\);/s', $css);
        $this->assertStringContainsString('.adm-app .bk-page-nav__current {', $admin);
        $this->assertStringNotContainsString('var(--surface-', $view);
        $this->assertStringNotContainsString('var(--brand-', $view);
    }

    /** `->links()` tanpa argumen tetap memakai paginasi bertema.
     *
     *  Laravel memakai tampilan bawaan (kelas Tailwind) kalau provider tidak
     *  menyetel default. Karena Tailwind tidak dibangun di proyek ini, empat
     *  halaman (`catalog/index`, `catalog/admin`, `rental/admin`,
     *  `rental/cashbook`) menampilkan nomor halaman sebagai teks polos.
     */
    public function test_paginasi_bawaan_memakai_tampilan_bertema(): void
    {
        $provider = File::get(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString("Paginator::defaultView('vendor.pagination.token-simple')", $provider);
        $this->assertStringContainsString("Paginator::defaultSimpleView('vendor.pagination.token-simple')", $provider);
        $this->assertFileExists(resource_path('views/vendor/pagination/token-simple.blade.php'));

        // Tidak boleh ada halaman yang menunjuk tampilan paginasi yang berbeda
        // rupa (dua sistem paginasi = dua tinggi sasaran sentuh).
        foreach (['catalog/index.blade.php', 'catalog/admin.blade.php', 'rental/admin.blade.php', 'rental/cashbook.blade.php'] as $relative) {
            $html = File::get(resource_path('views/'.$relative));
            $this->assertStringContainsString('->links()', $html, $relative.' tidak lagi memakai paginasi bawaan.');
        }
    }
}
