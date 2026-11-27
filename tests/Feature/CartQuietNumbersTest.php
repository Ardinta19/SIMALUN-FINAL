<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Putaran 39 — angka uang di keranjang tenang.
 *
 * Sebelumnya total keranjang "melompat" 0,45 detik setiap kali −/+ ditekan
 * (kelas `is-updated` + keyframes `bk-total-pop`). Pemilik usaha meminta harga
 * tidak berubah dengan animasi, jadi efek itu dicabut. Umpan balik untuk
 * pembaca layar tidak ikut hilang: angkanya kini `aria-live="polite"`.
 *
 * Tes ini membaca berkas, bukan merender halaman, karena yang dijaga adalah
 * absennya animasi di CSS/JS dan hadirnya penanda aksesibilitas di markup.
 */
class CartQuietNumbersTest extends TestCase
{
    public function test_skrip_keranjang_tidak_lagi_menjalankan_animasi_angka(): void
    {
        $script = (string) file_get_contents(public_path('js/berkah-cart-live.js'));

        $this->assertStringNotContainsString('is-updated', $script);
        $this->assertStringNotContainsString('pulse', $script);
        $this->assertStringNotContainsString('bk-total-pop', $script);

        // Angka tetap diperbarui dari data server, tanpa efek.
        $this->assertStringContainsString("document.querySelector('[data-cart-total]')", $script);
        $this->assertStringContainsString('total.textContent = payload.total_label', $script);
        $this->assertStringContainsString("counter.textContent = payload.count + ' unit'", $script);
    }

    public function test_css_tidak_memuat_keyframes_angka_keranjang(): void
    {
        $css = (string) file_get_contents(public_path('css/berkah-experience.css'));

        $this->assertStringNotContainsString('bk-total-pop', $css);
        $this->assertStringNotContainsString('[data-cart-total].is-updated', $css);
    }

    public function test_angka_keranjang_diumumkan_ke_pembaca_layar(): void
    {
        $cart = (string) file_get_contents(resource_path('views/catalog/cart.blade.php'));

        $this->assertStringContainsString('data-cart-total aria-live="polite" aria-atomic="true"', $cart);
        $this->assertStringContainsString('data-cart-total-count aria-live="polite"', $cart);
    }
}
