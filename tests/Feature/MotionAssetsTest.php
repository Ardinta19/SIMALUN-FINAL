<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menjaga lapisan gerak bersama: Lenis (gulir halus), GSAP + ScrollTrigger
 * (kemunculan, parallax, hitungan), dan micro-interaction.
 *
 * Semua pustaka harus disajikan dari public/vendor (tanpa CDN) dan dimuat
 * lewat satu titik: layouts/component/_form_controls.blade.php, sehingga
 * guest, auth, customer, maupun admin mendapat perilaku yang sama.
 */
class MotionAssetsTest extends TestCase
{
    private function source(string $relative): string
    {
        $path = public_path($relative);

        $this->assertFileExists($path, "Aset gerak hilang: public/{$relative}");

        return (string) file_get_contents($path);
    }

    public function test_animation_libraries_are_vendored_locally(): void
    {
        foreach ([
            'vendor/gsap/gsap.min.js',
            'vendor/gsap/ScrollTrigger.min.js',
            'vendor/lenis/lenis.min.js',
            'vendor/lenis/lenis.css',
            'js/berkah-motion.js',
            'css/berkah-motion.css',
        ] as $asset) {
            $this->assertFileExists(public_path($asset), "Aset gerak hilang: public/{$asset}");
        }

        $this->assertGreaterThan(20_000, filesize(public_path('vendor/gsap/ScrollTrigger.min.js')));
        $this->assertGreaterThan(8_000, filesize(public_path('vendor/lenis/lenis.min.js')));
    }

    public function test_motion_partial_loads_every_library_with_cache_busting(): void
    {
        $partial = (string) file_get_contents(
            resource_path('views/layouts/component/_form_controls.blade.php')
        );

        // Urutan penting: GSAP → ScrollTrigger → Lenis → mesin gerak.
        $expected = [
            'vendor/gsap/gsap.min.js',
            'vendor/gsap/ScrollTrigger.min.js',
            'vendor/lenis/lenis.min.js',
            'js/berkah-motion.js',
        ];

        $previous = -1;
        foreach ($expected as $asset) {
            $position = strpos($partial, $asset);
            $this->assertNotFalse($position, "Partial tidak memuat {$asset}.");
            $this->assertGreaterThan($previous, $position, "{$asset} dimuat dalam urutan yang salah.");
            $previous = $position;
        }

        $this->assertStringContainsString("vendor/lenis/lenis.css", $partial);
        $this->assertStringContainsString("css/berkah-motion.css", $partial);
        // Versi aset mengikuti filemtime, bukan nomor manual.
        $this->assertStringContainsString('$fcAsset(', $partial);
    }

    public function test_motion_engine_guards_accessibility_and_failure_modes(): void
    {
        $js = $this->source('js/berkah-motion.js');

        foreach ([
            "prefers-reduced-motion: reduce",
            'matchMedia',
            'saveData',
            "pointer: fine",
            'fc-scroll-lock',
            'MutationObserver',
            'pagehide',
            'window.gsap',
            'window.ScrollTrigger',
            'window.Lenis',
            'data-bk-reveal',
            'data-bk-press',
            'data-bk-tilt',
            'window.BkMotion',
        ] as $needle) {
            $this->assertStringContainsString($needle, $js, "berkah-motion.js kehilangan: {$needle}");
        }
    }

    /** Setiap halaman mendapat animasi masuk yang aman. */
    public function test_masuk_halaman_dibatasi_agar_tidak_merusak_tata_letak(): void
    {
        $js = $this->source('js/berkah-motion.js');

        $this->assertStringContainsString('function initPageEnter()', $js);
        $this->assertStringContainsString('initPageEnter();', $js);

        // Jangan menganimasikan elemen fixed/sticky atau seluruh pohon main.
        $this->assertStringContainsString("style.position !== 'fixed' && style.position !== 'sticky'", $js);
        $this->assertStringContainsString('.slice(0, 4)', $js);

        // Halaman yang sudah punya koreografi sendiri tidak dianimasikan dua kali.
        $this->assertStringContainsString("page.querySelector('[data-bk-reveal], [data-bk-reveal-group]')", $js);

        // Tidak ada sisa transform/containing block setelah animasi selesai.
        $this->assertStringContainsString("clearProps: 'opacity,transform'", $js);

        // Dua jalan keluar: penanda html dan elemen per blok.
        $this->assertStringContainsString('data-bk-no-page-enter', $js);
        $this->assertStringContainsString('data-bk-no-enter', $js);
    }

    /** Gulir sentuh ikut dihaluskan, dengan pengaman yang jelas.
     *
     *  Keputusan §9.140 membalik pengaturan lama (`syncTouch: false`): di
     *  ponsel tidak ada animasi gulir sama sekali sehingga halaman terasa
     *  kaku dan "melompat". Sekarang Lenis memegang sentuh dengan lerp rendah
     *  (0.075) supaya gerak menempel pada jari, sementara opsi `prevent`
     *  mengembalikan sentuhan pada kolom isian dan daerah bergulir sendiri
     *  (sheet, dialog, [data-lenis-prevent]) agar tidak ada yang tertahan.
     *  Pengaman lain tetap: Lenis berhenti saat gulir dikunci dan tidak pernah
     *  dinyalakan bila pengguna meminta gerak dikurangi atau hemat data. */
    public function test_gulir_sentuh_dihaluskan_dengan_pengaman(): void
    {
        $js = $this->source('js/berkah-motion.js');

        $this->assertStringContainsString('syncTouch: true', $js, 'Gulir sentuh harus ikut dihaluskan.');
        $this->assertStringNotContainsString('syncTouch: false', $js);
        $this->assertStringContainsString('syncTouchLerp: 0.075', $js, 'Lerp sentuh harus rendah agar gerak menempel pada jari.');

        // Kolom isian dan daerah bergulir sendiri memakai gulir asli.
        $this->assertStringContainsString('prevent: function (node)', $js);
        $this->assertStringContainsString('[data-lenis-prevent], input, textarea, select', $js);
        $this->assertStringContainsString('.fc-sheet', $js);

        // Gerak tetap dimatikan untuk pengguna yang memintanya.
        $this->assertStringContainsString('saveData', $js);
        $this->assertStringContainsString('prefers-reduced-motion', $js);

        // Satu frame panjang tidak boleh menggeser posisi gulir sekaligus.
        $this->assertStringNotContainsString('lagSmoothing(0)', $js, 'Delta frame harus dibatasi, bukan dibiarkan apa adanya.');
        $this->assertStringContainsString('lagSmoothing(500, 33)', $js);

        // Kunci gulir busur radial memakai penanda yang sama dengan mesin gerak,
        // supaya pengamat kelas tidak menyalakan Lenis di tengah animasi.
        $radial = $this->source('js/berkah-radial.js');
        $this->assertStringContainsString('bk-scroll-frozen', $radial);
        $this->assertStringContainsString('bk-scroll-frozen', $js);

        // Strip mendatar tidak boleh menahan sumbu Y: geser miring harus tetap
        // menggulir halaman.
        $css = $this->source('css/berkah-motion.css');
        $this->assertMatchesRegularExpression(
            '/\.bk-how\b[\s\S]{0,400}overscroll-behavior-y:\s*auto/',
            $css,
            'Strip mendatar harus membiarkan sumbu Y menyambung ke halaman.'
        );
        $this->assertStringContainsString('overscroll-behavior-x: contain', $css);
    }

    /** Gulir roda mengikuti jari, bukan menunggu satu tween selesai.
     *
     *  Dengan `duration: 1.05` setiap putaran roda memulai tween baru yang
     *  panjang, jadi halaman selalu tertinggal dan gulir cepat berulang
     *  terasa seperti menunggu loading. `lerp` mengejar target secara
     *  proporsional: respons pertama langsung terasa, sisa jarak menyusul.
     */
    public function test_gulir_roda_memakai_lerp_bukan_durasi_tetap(): void
    {
        $js = $this->source('js/berkah-motion.js');

        $this->assertStringContainsString('lerp: 0.1', $js);
        $this->assertStringNotContainsString('duration: 1.05', $js, 'Durasi tetap membuat halaman tertinggal dari roda gigi.');
    }

    public function test_motion_styles_never_hide_content_without_javascript(): void
    {
        $css = $this->source('css/berkah-motion.css');

        // Keadaan tersembunyi hanya boleh bergantung pada kelas yang dipasang
        // skrip; tanpa JS tidak ada satu pun aturan opacity: 0 yang berlaku.
        $this->assertStringNotContainsString('opacity: 0;', $css);
        $this->assertStringContainsString('touch-action: manipulation', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('var(--bk-touch)', $css);
    }

    public function test_design_scales_are_shared_tokens(): void
    {
        $kit = $this->source('css/berkah-ui-kit.css');
        foreach (['--bk-fs-2xs: .75rem', '--bk-space-1: 4px', '--bk-space-10: 64px', '--bk-lh-body', '--bk-touch: 44px'] as $token) {
            $this->assertStringContainsString($token, $kit, "Token skala hilang: {$token}");
        }

        $admin = $this->source('css/berkah-admin.css');
        foreach (['--adm-fs-2xs: .75rem', '--adm-space-9: 48px', '--adm-touch: 44px', '--adm-safe-b'] as $token) {
            $this->assertStringContainsString($token, $admin, "Token skala admin hilang: {$token}");
        }

        // Batas bawah keterbacaan. Tangga token harus benar-benar dipakai —
        // dulu pemeriksaan ini hanya mencari literal `font-size: .NNNrem`,
        // sehingga lolos begitu semua ukuran pindah ke var(--adm-fs-*).
        $this->assertGreaterThan(40, substr_count($admin, 'var(--adm-fs-'));

        $undersized = [];
        preg_match_all('/font-size\s*:\s*([^;}]+)/', $admin, $declarations);
        foreach ($declarations[1] as $value) {
            preg_match_all('/(\d*\.?\d+)(px|rem|em)/', $value, $units, PREG_SET_ORDER);
            foreach ($units as $unit) {
                $px = in_array($unit[2], ['rem', 'em'], true) ? (float) $unit[1] * 16 : (float) $unit[1];
                if ($px < 12) {
                    $undersized[] = trim($value);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($undersized)), 'Ada teks di bawah 12px pada berkah-admin.css.');
    }

    public function test_shared_motion_respects_reduced_motion_and_avoids_blanket_transitions(): void
    {
        // Halaman yang membawa CSS sendiri (auth, alamat, profil) tidak menulis
        // blok reduced-motion; `berkah-motion.css` — dimuat di semua halaman
        // lewat `_form_controls` — menyediakan jaring pengamannya. Jaring itu
        // sengaja tinggal di sini, bukan di berkah-theme.css: berkas pemetaan
        // warna dijaga bebas `!important` oleh UiKitTest, sedangkan pengaman
        // gerak memang butuh `!important` untuk mengalahkan aturan komponen.
        $motion = $this->source('css/berkah-motion.css');
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $motion);
        $this->assertStringContainsString('transition-duration: .01ms !important', $motion);
        $this->assertStringContainsString('animation-iteration-count: 1 !important', $motion);
        $this->assertMatchesRegularExpression('/\*,\s*\*::before,\s*\*::after\s*\{[^}]*transition-duration/', $motion);

        // `transition: all` ikut menganimasikan properti tata letak; setiap
        // transisi wajib menyebut properti yang berubah.
        $offenders = [];
        $files = array_merge(File::allFiles(public_path('css')), File::allFiles(resource_path('views')));

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['css', 'php'], true)) {
                continue;
            }

            if (preg_match('/transition:\s*all\b/', File::get($file->getPathname()), $match)) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).' → '.$match[0];
            }
        }

        $this->assertSame([], $offenders, 'Transisi menyapu semua properti.');
    }

    /** Skrip tidak boleh menunggu elemen yang tidak pernah dirender.
     *
     *  `berkah-ui-kit.js` masih mencari `#bk-theme-hand-1` dan
     *  `#bk-theme-hand-2` — dua tangan dari desain sakelar tema yang lama.
     *  SVG sekarang hanya punya satu tangan, jadi kait itu selalu `null`:
     *  tidak error, tetapi tween-nya mati dan pembaca kode mengira animasi
     *  dua tangan masih ada. Tes ini memastikan setiap `querySelector('#id')`
     *  di `public/js` benar-benar ada di view (id yang dibuat saat runtime
     *  didaftarkan di `$dibuatSaatJalan`).
     */
    public function test_skrip_tidak_mencari_elemen_yang_tidak_ada(): void
    {
        $dibuatSaatJalan = ['bk-theme-hand-1', 'bk-theme-hand-2'];
        $corpus = '';

        foreach (File::allFiles(resource_path('views')) as $file) {
            $corpus .= (string) File::get($file->getPathname());
        }

        $yatim = [];

        foreach (File::allFiles(public_path('js')) as $file) {
            $js = (string) File::get($file->getPathname());

            preg_match_all('/querySelector(?:All)?\(\s*[\'"`]#([A-Za-z0-9_-]+)[\'"`]/', $js, $found);

            foreach (array_unique($found[1]) as $id) {
                if (in_array($id, $dibuatSaatJalan, true)) {
                    continue;
                }

                if (! str_contains($corpus, 'id="'.$id.'"')) {
                    $yatim[] = $file->getFilename().' → #'.$id;
                }
            }
        }

        $this->assertSame([], $yatim, implode(' | ', $yatim));
    }
}
