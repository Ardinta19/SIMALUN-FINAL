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
        $this->assertStringContainsString("route('login')", $view);
        $this->assertStringContainsString("Menu: katalog, keranjang, daftar, dan masuk", $view);

        // Slot tema: satu tombol, dipindahkan ke dalam busur di ponsel.
        $this->assertStringContainsString('data-bk-theme-host="radial"', $view);
        $this->assertStringContainsString('.customer-radial__item', $view);

        // Hubungan tombol–panel harus eksplisit untuk pembaca layar.
        $this->assertStringContainsString('aria-controls="bk-radial-panel"', $view);
        $this->assertStringContainsString('id="bk-radial-panel"', $view);

        // Satu variabel menggerakkan busur; transform tetap milik CSS.
        $this->assertStringContainsString('--p:0', $view);
        $this->assertStringContainsString('translate(calc(var(--rx,0px) * var(--p)), calc(var(--ry,0px) * var(--p)))', $view);
        $this->assertStringContainsString('.customer-radial[open] .customer-radial__item{--p:1}', $view);

        // Tema gelap: lingkaran, lencana, dan tombol pusat tetap terbedakan.
        $this->assertStringContainsString('html[data-theme="dark"] .bk-header-radial .customer-radial__item{background:', $view);
        $this->assertStringContainsString('html[data-theme="dark"] .customer-radial__badge', $view);
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
        $this->assertStringContainsString('stagger: 0.055', $js);
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

        // Cincin orbit dan gumpalan tidak lagi berukuran tetap: ukurannya
        // mengikuti busur yang sedang dipakai, supaya bentuk U tidak terlihat
        // sempit di dalam cincin 244 px.
        $this->assertStringContainsString('width:var(--bk-orbit,244px)', $view);
        $this->assertStringContainsString('calc(var(--bk-orbit,238px) * .98)', $view);
        $this->assertStringNotContainsString('width:238px;height:238px', $view);

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
    }
}
