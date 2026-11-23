<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Filter katalog tidak hilang saat kembali dari detail, dan isian checkout
 * dipulihkan setelah pelanggan sempat kembali ke keranjang.
 */
class RentalCheckoutDraftTest extends TestCase
{
    use RefreshDatabase;

    private function product(): RentalProduct
    {
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);

        return RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Bunga', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
    }

    public function test_catalog_detail_returns_to_the_filtered_catalog(): void
    {
        $product = $this->product();

        $this->get(route('catalog.show', ['product' => $product->id, 'q' => 'papan', 'category' => $product->rental_category_id]))
            ->assertOk()
            ->assertSee(route('welcome', ['q' => 'papan', 'category' => $product->rental_category_id]));

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertDontSee('q=papan');
    }

    public function test_checkout_draft_survives_a_trip_back_to_the_cart(): void
    {
        $product = $this->product();
        $customer = User::factory()->create(['role' => 'customer']);
        $key = (string) Str::uuid();
        $cart = [$key => ['product_id' => $product->id]];
        $date = now('Asia/Jakarta')->addDays(5)->toDateString();

        // Kirim tanpa menyetujui ketentuan: validasi gagal, isian disimpan.
        $this->actingAs($customer)->withSession(['rental_cart' => $cart])->post(route('rental.checkout.store'), [
            'units' => [$key => ['event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Ibu Sari', 'greeting' => 'Selamat wisuda', 'delivery_method' => 'pickup', 'return_method' => 'dropoff']],
        ])->assertSessionHasErrors('terms');

        // Buka keranjang lalu kembali ke checkout: isian yang sama muncul lagi.
        $this->get(route('cart.index'))->assertOk();
        $this->get(route('rental.checkout'))
            ->assertOk()
            ->assertSee('Ibu Sari', false)
            ->assertSee('Selamat wisuda', false)
            ->assertSee('value="'.$date.'"', false);
    }

    public function test_successful_checkout_clears_cart_and_draft(): void
    {
        $product = $this->product();
        $customer = User::factory()->create(['role' => 'customer']);
        $key = (string) Str::uuid();
        $cart = [$key => ['product_id' => $product->id]];
        $date = now('Asia/Jakarta')->addDays(5)->toDateString();

        $this->actingAs($customer)->withSession(['rental_cart' => $cart])->post(route('rental.checkout.store'), [
            'terms' => 1,
            'units' => [$key => ['event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Ibu Sari', 'greeting' => 'Selamat wisuda', 'delivery_method' => 'pickup', 'return_method' => 'dropoff']],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertNull(session('rental_cart'));
        $this->assertNull(session('rental_checkout_draft'));
    }
}
