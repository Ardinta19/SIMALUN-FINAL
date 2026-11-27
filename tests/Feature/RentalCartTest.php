<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_separate_units_and_cart_survives_login(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 2]);
        $this->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect(route('cart.index'));
        $this->assertCount(2, session('rental_cart'));
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->post(route('login.post'), ['identifier' => $user->email, 'password' => 'password123'])->assertRedirect();
        $this->assertCount(2, session('rental_cart'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Papan');
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 1, 'price_unit' => 'day', 'stock' => 1, 'is_active' => false]);
        $this->post(route('cart.store', $product), ['quantity' => 1])->assertNotFound();
    }
    public function test_guest_can_order_directly_and_continue_checkout_after_login(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 2]);
        $this->post(route('cart.store', $product), ['quantity' => 1])->assertRedirect(route('cart.index'));
        $this->post(route('cart.store', $product), ['quantity' => 1, 'next' => 'checkout'])->assertRedirect(route('rental.checkout'));
        $this->get(route('rental.checkout'))->assertRedirect(route('login'));
        $customer = User::factory()->create(['password' => bcrypt('password123')]);
        $customer->forceFill(['role' => 'customer'])->save();
        $this->post(route('login.post'), ['identifier' => $customer->email, 'password' => 'password123'])->assertRedirect(route('rental.checkout'));
        $this->assertCount(2, session('rental_cart'));
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Detail penyewaan');
    }
    public function test_cart_guides_customers_when_an_item_becomes_unavailable(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 2]);
        $this->post(route('cart.store', $product), ['quantity' => 2]);
        /* Desain keranjang sejak `539a825` merender satu baris per kelompok
           (papan + tanggal), bukan satu baris per unit. Dulu tes ini mencari
           label "UNIT 1"/"UNIT 2" yang sudah tidak ada. Yang tetap harus benar:
           kedua unit terwakili lewat penghitung stepper, dan tombol hapus
           berlaku untuk seluruh kelompok. */
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('2 unit')
            ->assertSee('<h2>Papan</h2>', false)
            ->assertSee('aria-label="Jumlah unit Papan"', false)
            ->assertSee('<span class="bk-qty-num" aria-live="polite">2</span>', false)
            ->assertSee('aria-label="Hapus semua unit Papan dari keranjang"', false)
            ->assertSee('Lanjut ke checkout');
        $category->update(['is_active' => false]);
        $this->get(route('cart.index'))->assertOk()->assertSee('Papan tidak tersedia')->assertSee('Hapus 2 unit yang tidak tersedia')->assertDontSee('Lanjut ke checkout');
        $keys = array_keys(session('rental_cart'));
        $this->delete(route('cart.destroy', $keys[0]))->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertSee('Hapus 1 unit yang tidak tersedia');
        $this->delete(route('cart.destroy', $keys[1]))->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertSee('Keranjangmu masih kosong')->assertSee('Jelajahi katalog');
    }
}
