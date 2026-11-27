<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Keranjang dikelompokkan per papan + tanggal, dengan stepper −/+.
 *
 * Sebelumnya setiap unit menjadi satu kartu, jadi lima unit papan yang sama
 * tampil sebagai lima kartu hampir identik dengan tulisan "UNIT 1…5" dan
 * tombol "Hapus" masing-masing. Sekarang satu baris mewakili satu kelompok;
 * tombol − mengurangi satu unit dari kelompok itu dan + menambahnya.
 */
class CartGroupingTest extends TestCase
{
    use RefreshDatabase;

    private function produk(): RentalProduct
    {
        $kategori = RentalCategory::create(['name' => 'Wisuda']);

        return RentalProduct::create([
            'rental_category_id' => $kategori->id,
            'name' => 'Papan Melati',
            'base_price' => 100000,
            'price_unit' => 'day',
            'stock' => 5,
        ]);
    }

    private function entri(int $productId, ?string $mulai = null, ?string $selesai = null): array
    {
        $entry = ['product_id' => $productId];

        if ($mulai && $selesai) {
            $entry += ['starts_on' => $mulai, 'ends_on' => $selesai];
        }

        return $entry;
    }

    public function test_unit_dengan_papan_dan_tanggal_sama_tampil_sebagai_satu_baris(): void
    {
        $product = $this->produk();
        $cart = [
            (string) Str::uuid() => $this->entri($product->id, '2026-12-04', '2026-12-05'),
            (string) Str::uuid() => $this->entri($product->id, '2026-12-04', '2026-12-05'),
            (string) Str::uuid() => $this->entri($product->id, '2026-12-04', '2026-12-05'),
            (string) Str::uuid() => $this->entri($product->id, '2026-12-10', '2026-12-11'),
        ];

        $response = $this->withSession(['rental_cart' => $cart])->get(route('cart.index'))->assertOk();

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'class="card body cart-item"'), 'Tiga unit + satu unit bertanggal lain = dua baris.');
        /* Sejak pembaruan langsung (§9.146) penghitung unit dan subtotal
           membawa kait data-*; angkanya tetap sama. */
        $this->assertStringContainsString('<span class="bk-qty-num" aria-live="polite" data-cart-count>3</span>', $html);
        $this->assertStringContainsString('<span class="bk-qty-num" aria-live="polite" data-cart-count>1</span>', $html);
        $this->assertStringContainsString('Subtotal harga dasar <strong data-cart-subtotal>Rp 300.000</strong>', $html);
        $this->assertStringContainsString('4 unit', $html);
    }

    public function test_tombol_minus_mengurangi_satu_unit_dari_kelompok_yang_sama(): void
    {
        $product = $this->produk();
        $idA = (string) Str::uuid();
        $idB = (string) Str::uuid();
        $idLain = (string) Str::uuid();

        $cart = [
            $idA => $this->entri($product->id, '2026-12-04', '2026-12-05'),
            $idB => $this->entri($product->id, '2026-12-04', '2026-12-05'),
            $idLain => $this->entri($product->id, '2026-12-10', '2026-12-11'),
        ];

        $this->withSession(['rental_cart' => $cart])
            ->post(route('cart.decrement', $idA))
            ->assertRedirect();

        $sisa = session('rental_cart');
        $this->assertCount(2, $sisa);
        $this->assertArrayHasKey($idA, $sisa);
        $this->assertArrayNotHasKey($idB, $sisa, 'Unit terakhir pada kelompok itulah yang dikurangi.');
        $this->assertArrayHasKey($idLain, $sisa, 'Kelompok bertanggal lain tidak boleh tersentuh.');
    }

    public function test_tombol_hapus_menghapus_semua_unit_kelompok_tanpa_menyentuh_kelompok_lain(): void
    {
        $product = $this->produk();
        $idA = (string) Str::uuid();
        $idLain = (string) Str::uuid();

        $cart = [
            $idA => $this->entri($product->id),
            $idLain => $this->entri($product->id, '2026-12-10', '2026-12-11'),
        ];

        $this->withSession(['rental_cart' => $cart])
            ->post(route('cart.clear', $idA))
            ->assertRedirect();

        $sisa = session('rental_cart');
        $this->assertCount(1, $sisa);
        $this->assertArrayHasKey($idLain, $sisa);
    }

    public function test_tombol_plus_menambah_satu_unit_pada_kelompok_yang_sama(): void
    {
        $product = $this->produk();
        $idA = (string) Str::uuid();

        $cart = [$idA => $this->entri($product->id, '2026-12-04', '2026-12-05')];
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->withSession(['rental_cart' => $cart])
            ->post(route('cart.duplicate', $idA))
            ->assertSessionHasNoErrors();

        $this->assertCount(2, session('rental_cart'));
    }
}
