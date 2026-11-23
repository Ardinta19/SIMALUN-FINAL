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
        $this->assertStringContainsString('aria-label="Keranjang{{', $view);

        // Busur: lima tujuan pada radius 112 px, sudut 180/154/128/102/76°.
        foreach (['--rx:-112px;--ry:0px', '--rx:-101px;--ry:49px', '--rx:-69px;--ry:88px', '--rx:-23px;--ry:110px', '--rx:27px;--ry:109px'] as $offset) {
            $this->assertStringContainsString($offset, $view);
        }
        $this->assertStringContainsString('width:46px;height:46px', $view, 'Sasaran sentuh menu harus >= 44 px.');
        $this->assertStringContainsString('nth-of-type(5)', $view);

        // Tujuan kelima adalah halaman yang belum ada di pita bawah.
        $this->assertStringContainsString("route('customer.addresses.index')", $view);
        $this->assertStringContainsString('aria-label="Alamat pengiriman"', $view);

        // Hubungan tombol–panel harus eksplisit untuk pembaca layar.
        $this->assertStringContainsString('aria-controls="bk-radial-panel"', $view);
        $this->assertStringContainsString('id="bk-radial-panel"', $view);

        // Satu variabel menggerakkan busur; transform tetap milik CSS.
        $this->assertStringContainsString('--p:0', $view);
        $this->assertStringContainsString('translate(calc(var(--rx) * var(--p)), calc(var(--ry) * var(--p)))', $view);
        $this->assertStringContainsString('.customer-radial[open] .customer-radial__links a{--p:1}', $view);

        // Tema gelap: lingkaran, lencana, dan tombol pusat tetap terbedakan.
        $this->assertStringContainsString('html[data-theme="dark"] .bk-header-radial .customer-radial__links a', $view);
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
    }
}
