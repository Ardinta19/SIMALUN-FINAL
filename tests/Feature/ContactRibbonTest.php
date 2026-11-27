<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjaga strip berjalan di beranda katalog: alur pemesanan terbaru plus
 * kanal kontak (Instagram, TikTok, WhatsApp) dalam satu baris yang bergerak
 * ke kanan dan berhenti saat disentuh.
 *
 * Tes ini membaca berkas view/CSS/JS langsung karena pemeriksaan di peramban
 * (gerak, jeda dua detik, salinan yang tak terlihat oleh pembaca layar) tidak
 * bisa dijalankan dari PHP. Perilaku runtime-nya sendiri sudah diuji di jsdom
 * lewat `/tmp/wasm-php/ribbon-jsdom.cjs`; yang dijaga di sini adalah hal-hal
 * yang mudah hilang saat seseorang menyunting markup atau CSS.
 */
class ContactRibbonTest extends TestCase
{
    private function ribbon(): string
    {
        return File::get(resource_path('views/catalog/_ribbon.blade.php'));
    }

    private function script(): string
    {
        return File::get(public_path('js/berkah-ribbon.js'));
    }

    public function test_beranda_katalog_memakai_partial_strip_berjalan(): void
    {
        $index = File::get(resource_path('views/catalog/index.blade.php'));

        $this->assertStringContainsString("@include('catalog._ribbon')", $index);
        $this->assertStringNotContainsString('aria-label="Cara memesan"', $index,
            'Strip lama tiga langkah seharusnya sudah digantikan partial berjalan.');
        $this->assertFileExists(public_path('js/berkah-ribbon.js'));
    }

    public function test_lima_langkah_mengikuti_alur_pemesanan_yang_sebenarnya(): void
    {
        $ribbon = $this->ribbon();

        $langkah = [
            'Pilih papan',
            'Atur tanggal & ucapan',
            'Admin konfirmasi',
            'Bayar & setujui desain',
            'Pantau sampai terpasang',
        ];

        $previous = -1;

        foreach ($langkah as $judul) {
            $posisi = strpos($ribbon, $judul);
            $this->assertNotFalse($posisi, 'Langkah hilang: '.$judul);
            $this->assertGreaterThan($previous, $posisi, 'Urutan langkah terbalik: '.$judul);
            $previous = $posisi;
        }

        /* Alur nyata: keranjang belum menahan stok, tanggal & ucapan diisi saat
           checkout, admin mengonfirmasi ketersediaan/ongkos, pembayaran dan
           pratinjau desain disetujui pelanggan, lalu progres dipantau sampai
           unit terpasang. Kalimat lama "Tunggu konfirmasi admin" pada langkah
           terakhir tidak lagi benar. */
        $this->assertStringContainsString('Jadwal dicek, ongkos disepakati', $ribbon);
        $this->assertStringContainsString('Unggah bukti, cek pratinjau', $ribbon);
        $this->assertStringNotContainsString('Tulis ceritamu', $ribbon);
        $this->assertStringNotContainsString('Pantau pesanan', $ribbon);
    }

    public function test_tiga_kanal_kontak_memakai_logo_dan_nama(): void
    {
        $ribbon = $this->ribbon();

        foreach (['Instagram', 'TikTok', 'WhatsApp'] as $kanal) {
            $this->assertStringContainsString('<strong>'.$kanal.'</strong>', $ribbon);
        }

        // Nomor tampil dalam bentuk yang enak dibaca, bukan angka mentah.
        $this->assertStringContainsString('\\App\\Support\\Laundry::phoneDisplay()', $ribbon);
        $this->assertStringContainsString('\\App\\Support\\WhatsApp::link(', $ribbon);

        // Tiga lambang merek, semuanya SVG sebaris — tanpa berkas gambar baru
        // dan tanpa CDN. Ikon disembunyikan dari pembaca layar karena namanya
        // sudah ada sebagai teks.
        $this->assertSame(3, substr_count($ribbon, '<span class="bk-ribbon__icon" aria-hidden="true"><svg'));
        $this->assertSame(3, substr_count($ribbon, 'viewBox="0 0 24 24"'));

        // Setiap tautan keluar aman dan bisa diklik di seluruh badan pil.
        $this->assertSame(3, substr_count($ribbon, 'target="_blank" rel="noopener noreferrer"'));
    }

    public function test_kanal_yang_belum_diatur_tidak_dirender(): void
    {
        $ribbon = $this->ribbon();

        foreach (['Ig', 'Tt', 'Wa'] as $kanal) {
            $this->assertStringContainsString('@if($bkRibbon'.$kanal.')', $ribbon);
        }

        $this->assertStringNotContainsString('href="#"', $ribbon);
    }

    public function test_strip_berhenti_saat_disentuh_dan_lanjut_dua_detik_kemudian(): void
    {
        $js = $this->script();

        $this->assertStringContainsString('RESUME_DELAY = 2000', $js);
        $this->assertStringContainsString("bar.addEventListener('pointerenter'", $js);
        $this->assertStringContainsString("bar.addEventListener('pointerleave'", $js);
        $this->assertStringContainsString("bar.addEventListener('pointerdown'", $js);
        $this->assertStringContainsString("bar.addEventListener('pointerup'", $js);
        $this->assertStringContainsString("bar.addEventListener('focusin'", $js);
        $this->assertStringContainsString("bar.addEventListener('focusout'", $js);
        $this->assertStringContainsString('pointerType', $js);
    }

    public function test_salinan_strip_tidak_terbaca_dua_kali_dan_tidak_menahan_tab(): void
    {
        // Isi digandakan di peramban, bukan di view: pembaca layar tidak boleh
        // mendengar langkah yang sama dua kali, dan Tab tidak boleh berhenti di
        // tautan salinan yang tampak identik.
        $this->assertSame(1, substr_count($this->ribbon(), 'data-bk-ribbon-group'),
            'Penggandaan harus terjadi di JavaScript, bukan di Blade.');

        $js = $this->script();
        $this->assertStringContainsString("copy.setAttribute('aria-hidden', 'true')", $js);
        $this->assertStringContainsString("link.setAttribute('tabindex', '-1')", $js);
    }

    public function test_strip_menghormati_pengaturan_gerak_pengguna(): void
    {
        $js = $this->script();
        $css = File::get(public_path('css/berkah-experience.css'));

        $this->assertStringContainsString("matchMedia('(prefers-reduced-motion: reduce)')", $js);
        $this->assertStringContainsString("getItem('bk-motion') === 'off'", $js,
            'Sakelar darurat ?gerak=mati harus berlaku juga untuk strip ini.');
        $this->assertStringContainsString("bar.classList.add('is-static')", $js);

        $this->assertStringContainsString('@keyframes bk-ribbon-run', $css);
        $this->assertStringContainsString('animation-play-state: paused', $css);
        $this->assertStringContainsString('.bk-ribbon.is-static .bk-ribbon__viewport', $css);
        $this->assertStringContainsString('overflow-x: auto', $css);

        // Tanpa JavaScript isi tetap terbaca: hanya animasinya yang tidak jalan.
        $this->assertStringContainsString('transform: translate3d(var(--bk-ribbon-shift, -50%), 0, 0)', $css);
    }

    public function test_strip_tidak_membawa_gaya_inline_dan_tetap_bertema(): void
    {
        $ribbon = $this->ribbon();

        $this->assertStringNotContainsString('<style', $ribbon);
        $this->assertStringNotContainsString('style="', $ribbon);

        $theme = File::get(public_path('css/berkah-theme.css'));
        $this->assertStringContainsString('html[data-theme="dark"] .bk-ribbon__link', $theme);
        $this->assertStringContainsString('html[data-theme="dark"] .bk-ribbon__icon', $theme);
    }

    public function test_skrip_strip_dimuat_dari_layout_katalog(): void
    {
        $layout = File::get(resource_path('views/catalog/layout.blade.php'));

        $this->assertStringContainsString("js/berkah-ribbon.js", $layout);
        $this->assertSame(1, substr_count($layout, 'berkah-ribbon.js'),
            'Skrip strip seharusnya dimuat sekali saja di layout katalog.');
    }
}
