<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menu radial pelanggan (gaya Chrome): busur tujuan, gerak GSAP/Lenis, dan
 * jaring pengaman tanpa JavaScript / reduced-motion.
 */
class RadialMenuTest extends TestCase
{
    public function test_menu_radial_menyebar_dalam_busur_dengan_sasaran_46px(): void
    {
        $view = File::get(resource_path('views/layouts/component/customer/_radial_customer.blade.php'));

        // Struktur yang dipakai berkas lain/test lain tidak boleh hilang.
        $this->assertStringContainsString('data-customer-radial', $view);
        $this->assertStringContainsString('<details class="customer-radial', $view);
        $this->assertStringContainsString('id="bk-menu-goo"', $view);
        $this->assertStringContainsString('Bantuan / FAQ', $view);
        // Label tiap tujuan dirender dari data, termasuk jumlah unit.
        $this->assertStringContainsString("'Keranjang'.(\$cartCount ? ', '.\$cartCount.' unit' : '')", $view);
        $this->assertStringContainsString("aria-label=\"{{ \$item['label'] }}\"", $view);

        // Geometri dihitung: sudut dibagi rata 180° → 79°, radius dipilih agar
        // jarak antarpusat 50 px. Jumlah tujuan boleh berubah tanpa mengubah CSS.
        $this->assertStringContainsString('$span = 101;', $view);
        $this->assertStringContainsString('round(25 / sin(deg2rad($step / 2)))', $view);
        $this->assertStringContainsString('style="--rx:{{ $offsets[$slot][0] }}px;--ry:{{ $offsets[$slot][1] }}px"', $view);
        $this->assertStringNotContainsString('nth-of-type(', $view, 'Posisi busur tidak boleh ditulis tangan per slot.');
        $this->assertStringContainsString('width:46px;height:46px', $view, 'Sasaran sentuh menu harus >= 44 px.');

        // Tujuan pelanggan, termasuk halaman yang belum ada di pita bawah.
        foreach (["route('cart.index')", "route('rental.orders.index')", "route('customer.notifications')", "route('customer.addresses.index')", "route('customer.help')"] as $route) {
            $this->assertStringContainsString($route, $view);
        }
        $this->assertStringContainsString("'label' => 'Alamat pengiriman'", $view);

        // Tamu memakai busur yang sama, bukan dialog "Jelajahi".
        $this->assertStringContainsString("route('register')", $view);

        // Busur tamu hanya tampil di ponsel, jadi tujuan "Daftar" harus tetap
        // ada di tautan header untuk layar lebar — kalau tidak, satu tujuan
        // hilang hanya karena layarnya lebar.
        $nav = File::get(resource_path('views/catalog/customer-navigation.blade.php'));
        $this->assertStringContainsString("<a href=\"{{ route('register') }}\"", $nav);
        $this->assertStringContainsString("<a href=\"{{ route('login') }}\"", $nav);
        $this->assertStringContainsString("route('login')", $view);
        $this->assertStringContainsString("Menu: katalog, keranjang, daftar, dan masuk", $view);

        // Slot tema: satu tombol, dipindahkan ke dalam busur di ponsel.
        $this->assertStringContainsString('data-bk-theme-host="radial"', $view);
        $this->assertStringContainsString('.customer-radial__item', $view);

        // Hubungan tombol–panel harus eksplisit untuk pembaca layar, dan id-nya
        // tidak boleh dipakai dua busur di halaman yang sama (id ganda membuat
        // aria-controls menunjuk panel yang salah).
        $this->assertStringContainsString('aria-controls="{{ $panelId }}"', $view);
        $this->assertStringContainsString('id="{{ $panelId }}"', $view);
        $this->assertStringContainsString("\$panelId = \$isCustomer ? 'bk-radial-panel' : 'bk-radial-panel-tamu';", $view);
        $this->assertStringNotContainsString('id="bk-radial-panel"', $view, 'Id panel tidak boleh dipaku: dua busur akan bertabrakan.');

        // Busur boleh dipakai tanpa panel yang sama-sama ber-id: skrip memilih
        // panel lewat kelas, bukan lewat id.
        $this->assertStringContainsString(".querySelector('.customer-radial__panel')", File::get(public_path('js/berkah-radial.js')));

        // Satu variabel menggerakkan busur; transform tetap milik CSS.
        $this->assertStringContainsString('--p:0', $view);
        $this->assertStringContainsString('translate(calc(var(--rx,0px) * var(--p)), calc(var(--ry,0px) * var(--p)))', $view);
        $this->assertStringContainsString('.customer-radial[open] .customer-radial__item{--p:1}', $view);

        // Tema gelap: lingkaran, lencana, dan tombol pusat tetap terbedakan.
        $this->assertStringContainsString('html[data-theme="dark"] .bk-header-radial .customer-radial__item{background:', $view);
        $this->assertStringContainsString('html[data-theme="dark"] .customer-radial__badge', $view);
    }

    /** Aturan "kapan tombol busur tampil" hanya boleh hidup di partial busur.
     *  Dulu salinannya tinggal di partial pelanggan, dan halaman tamu — yang
     *  tidak memuat partial itu — menampilkan busur di semua lebar. */
    public function test_aturan_tampil_busur_hanya_ada_di_partial_busur(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if ($file->getFilename() === '_radial_customer.blade.php') continue;
            if (str_contains(File::get($file), '.bk-header-radial{display:')) {
                $offenders[] = $file->getFilename();
            }
        }

        $this->assertSame([], $offenders, 'Aturan tampil busur disalin ke: '.implode(', ', $offenders));

        // Partial pembungkus yang dulu menyalinnya sudah dihapus.
        $this->assertFileDoesNotExist(resource_path('views/layouts/component/customer/_radial_header.blade.php'));
    }

    public function test_gerak_radial_memakai_gsap_dan_lenis_lokal_tanpa_cdn(): void
    {
        $js = File::get(public_path('js/berkah-radial.js'));
        $view = File::get(resource_path('views/layouts/component/customer/_radial_customer.blade.php'));

        $this->assertStringContainsString("js/berkah-radial.js", $view);
        $this->assertStringContainsString('defer', $view);

        // GSAP: satu timeline, stagger, dan lencana memantul.
        $this->assertStringContainsString('gsap.timeline', $js);
        $this->assertStringContainsString("'--p': 1", $js);
        $this->assertStringContainsString('stagger: 0.028', $js);
        $this->assertStringContainsString("ease: 'expo.out'", $js);
        $this->assertStringContainsString("ease: 'back.out(2.4)'", $js);

        // Lenis: gulir di belakang menu dikunci selama menu terbuka.
        $this->assertStringContainsString('window.BkMotion', $js);
        $this->assertStringContainsString('lenis.stop', $js);
        $this->assertStringContainsString('lenis.start', $js);

        // A11y & jaring pengaman.
        $this->assertStringContainsString("setAttribute('aria-expanded'", $js);
        $this->assertStringContainsString("'Escape'", $js);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $js);
        $this->assertStringContainsString('window.bkRadial', $js);

        // Fokus keyboard tidak boleh jatuh ke <body> saat menu ditutup.
        $this->assertStringContainsString('restoreFocus', $js);
        $this->assertStringContainsString("summary.focus({ preventScroll: true })", $js);

        // Slot tema ikut menyebar, dan menekannya menutup busur lebih dulu.
        $this->assertStringContainsString("'.customer-radial__item'", $js);
        $this->assertStringContainsString('.customer-radial__item--theme', $js);

        // Tanpa CDN, dan berkas lama tetap menghormati pengendali baru.
        $this->assertStringNotContainsString('http://', $js);
        $this->assertStringNotContainsString('https://', $js);
        $this->assertStringContainsString('window.bkRadial.close()', File::get(public_path('js/berkah-experience.js')));
    }

    public function test_navigasi_bawah_tidak_ikut_berubah_saat_busur_dipindah(): void
    {
        $nav = File::get(resource_path('views/layouts/component/customer/_navbar_customer.blade.php'));

        // Gaya pita bawah tetap di tempatnya; rupa busur pindah ke partial radial.
        $this->assertStringContainsString('grid-template-columns:repeat(4,minmax(0,1fr))', $nav);
        $this->assertStringContainsString('height:68px', $nav);
        $this->assertStringContainsString('body.bk-customer .bk-desktop-nav', $nav);
        $this->assertStringNotContainsString('.customer-radial__links a:nth-of-type', $nav, 'Busur hanya boleh punya satu sumber gaya.');

        // Kapan tombol busur tampil bukan urusan partial pelanggan lagi:
        // halaman tamu tidak pernah memuat partial ini, dan dulu itulah sebabnya
        // busur tamu tampil di segala lebar dengan panel yang tidak bertumpu
        // pada tombolnya (gambar 102 & 103).
        $this->assertStringNotContainsString('.bk-header-radial{display:none', $nav);
        $radial = File::get(resource_path('views/layouts/component/customer/_radial_customer.blade.php'));
        $this->assertStringContainsString('.bk-header-radial{display:none;position:relative;flex:0 0 auto}', $radial);
        $this->assertStringContainsString('@media(max-width:700px){.bk-header-radial{display:block}}', $radial);

        // Menu harus tetap terjangkau setelah header menjadi ringkas.
        $this->assertStringNotContainsString('.bk-header.is-compact .bk-header-radial{display:none', $nav);
        $this->assertStringContainsString('.bk-header.is-compact .bk-header-radial>summary{width:44px;height:44px}', $nav);

        // Pil ringkas tidak boleh melebar saat busur terbuka: titik tumpunya bergeser.
        $script = File::get(public_path('js/berkah-experience.js'));
        $this->assertStringContainsString("radialMenu && radialMenu.open && header.classList.contains('is-compact')", $script);

        // Tombol tema pindah ke busur di ponsel; header menyisakan slotnya
        // untuk layar lebar (dan admin, yang tidak punya busur).
        $kit = File::get(public_path('js/berkah-ui-kit.js'));
        $this->assertStringContainsString("'[data-bk-theme-host=\"radial\"]'", $kit);
        $this->assertStringContainsString("window.matchMedia('(max-width: 700px)').matches", $kit);
    }

    public function test_busur_menyesuaikan_posisi_tombol(): void
    {
        $view = File::get(resource_path('views/layouts/component/customer/_radial_customer.blade.php'));
        $js = File::get(public_path('js/berkah-radial.js'));

        // Tidak ada bidang atau cincin di belakang busur: dulu ada cincin
        // orbit 244 px dan gumpalan goo di belakang tujuan, dan keduanya
        // terbaca sebagai “latar” menu. Elemen goo tetap ada (id dipakai uji)
        // tetapi tidak digambar lagi.
        $this->assertStringContainsString('.bk-header-radial .customer-radial__goo{display:none}', $view);
        $this->assertStringNotContainsString('customer-radial__links::before{content', $view);
        $this->assertStringContainsString('id="bk-menu-goo"', $view);
        $this->assertStringNotContainsString('--bk-orbit', $view);

        // Tombol di tengah (pil ringkas) memakai busur U; tombol di ujung
        // kanan kembali ke busur kiri-bawah dari server.
        $this->assertStringContainsString('is-arc-u', $js);
        $this->assertStringContainsString('layoutArc', $js);
        $this->assertStringContainsString('const ARC_GAP = 50', $js);
        $this->assertStringContainsString("setProperty('--rx'", $js);
        $this->assertStringContainsString("addEventListener('orientationchange'", $js);
        $this->assertStringContainsString('prefers-reduced-motion', File::get(public_path('js/berkah-radial.js')), 'Gerak harus tetap bisa dikurangi.');

        // Nilai awal dari server disimpan, bukan ditimpa langsung.
        $this->assertStringContainsString("getPropertyValue('--rx')", $js);

        // Satu halaman bisa memuat dua busur (header dan menu atas): semuanya
        // diinisialisasi, dan membuka satu menutup yang lain.
        $this->assertStringContainsString('RADIAL_SELECTOR', $js);
        $this->assertStringContainsString('window.bkRadials', $js);
        $this->assertStringContainsString('other.element.open', $js);

        // Slot tema yang kosong tidak boleh menyisakan lubang di busur.
        $this->assertStringContainsString('const filledItems = () =>', $js);
        $this->assertStringContainsString('childElementCount > 0', $js);

        // Lencana memakai --bp, bukan `transform`: GSAP menulis transform
        // inline permanen, dan itu mengunci skala lencana setelah busur
        // pertama dibuka.
        $this->assertStringContainsString('opacity:calc(var(--p) * var(--bp,1))', $view);
        $this->assertStringContainsString("badge.style.setProperty('--bp', '0')", $js);
        $this->assertStringContainsString("'--bp': 1", $js);
        $this->assertStringNotContainsString('scale:', $js);

        // Ditutup dari luar skrip ini (berkah-experience.js): tween dihentikan,
        // tujuan kembali ke pusat, dan aria-expanded ikut turun.
        $this->assertStringContainsString('Ditutup dari luar skrip ini', $js);

        // Penanda fokus untuk tombol pusat (halaman pelanggan tidak punya
        // aturan global `summary:focus-visible` seperti tata letak katalog),
        // plus kontras lebih tinggi.
        $this->assertStringContainsString('.bk-header-radial>summary:focus-visible{outline:3px solid #bc5b75', $view);
        $this->assertStringContainsString('html[data-theme="dark"] .bk-header-radial>summary:focus-visible{outline-color:#f3a8bb}', $view);
        $this->assertStringContainsString('@media (prefers-contrast: more){', $view);

        // Tombol di kiri layar: busur dicerminkan ke kanan, bukan dibiarkan
        // menyapu ke kiri dan keluar layar.
        $this->assertStringContainsString('const layoutSide = ', $js);
        $this->assertStringContainsString("'is-arc-opposite'", $js);
        $this->assertStringContainsString('roomRight > roomLeft + 24', $js);

        // Kembali lewat Back (bfcache): busur tertutup dan gulir dilepas,
        // kalau tidak halaman pulih dengan busur terbuka dan tak bisa digulir.
        $this->assertStringContainsString("addEventListener('pageshow'", $js);
        $this->assertStringContainsString('event.persisted', $js);

        // Navigasi panah di dalam busur (Tab tetap bekerja).
        $this->assertStringContainsString("'ArrowRight'", $js);
        $this->assertStringContainsString("event.key === 'Home'", $js);
        $this->assertStringContainsString("event.key === 'End'", $js);
        $this->assertStringContainsString('event.preventDefault();', $js);

        // Busur selalu berakhir utuh: jeda antar-tujuan dirapatkan (55 ms
        // membuat dua tujuan terakhir masih di tengah tombol saat disapu
        // mata), dan ada penyelesaian paksa setelah tween masuk.
        $this->assertStringContainsString('stagger: 0.028', $js);
        $this->assertStringContainsString('duration: 0.42', $js);
        $this->assertStringContainsString('onComplete: settle', $js);
        $this->assertStringContainsString('const settle = ', $js);

        // Klik kedua saat busur menyusut tidak lagi ditelan: penutupan
        // dibatalkan dan seluruh tujuan kembali penuh.
        $this->assertStringContainsString('if (closing) {', $js);
        $this->assertStringContainsString("from: 'end'", $js);
    }
}
