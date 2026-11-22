<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalDateFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_by_confirmed_overlap_and_preserves_dates_to_checkout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $full = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Penuh', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
            $open = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Tersedia', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
            $customer = User::factory()->create(['role' => 'customer']);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $attrs = ['rental_product_id' => $full->id, 'product_name' => 'Papan Penuh', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-03', 'event_on' => '2026-12-03', 'ends_on' => '2026-12-04', 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed'];
            $order->units()->create($attrs);
            $dates = ['starts_on' => '2026-12-04', 'ends_on' => '2026-12-05'];
            $this->get(route('welcome', $dates))->assertOk()->assertSee('Papan Tersedia')->assertDontSee('Papan Penuh')->assertSee('belum dicadangkan')
                ->assertSee(route('welcome', $dates + ['category' => $category->id]))->assertSee('Kategori papan bunga')->assertSee('Hapus filter tanggal');
            $this->get(route('catalog.show', array_merge(['product' => $full->id], $dates)))->assertOk()->assertSee('0 unit')->assertDontSee('Tambah ke keranjang');
            $this->from(route('catalog.show', $full))->post(route('cart.store', $full), $dates + ['quantity' => 1])->assertSessionHasErrors('quantity');
            $this->post(route('cart.store', $open), $dates + ['quantity' => 1])->assertSessionHasNoErrors();
            $this->get(route('cart.index'))->assertOk()->assertSee('Tanggal pilihan')->assertSee('+ Tambah unit')->assertSee('− Hapus unit');
            $this->actingAs($customer)->get(route('rental.checkout'))->assertOk()->assertSee('value="2026-12-04"', false)->assertSee('value="2026-12-05"', false);
            $this->get(route('welcome', ['starts_on' => '2026-12-03']))->assertSessionHasErrors('ends_on');
        } finally { Carbon::setTestNow(); }
    }

    public function test_cart_quantity_and_checkout_recheck_overlapping_units(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $customer = User::factory()->create(['role' => 'customer']);
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $next = now('Asia/Jakarta')->addDays(4)->toDateString();
        $this->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertSessionHasNoErrors();
        $first = array_key_first(session('rental_cart'));
        $this->post(route('cart.duplicate', $first))->assertSessionHasNoErrors();
        $this->assertCount(2, session('rental_cart'));
        $this->from(route('cart.index'))->post(route('cart.duplicate', $first))->assertSessionHasErrors('quantity');
        $this->delete(route('cart.destroy', $first))->assertRedirect();
        $this->assertCount(1, session('rental_cart'));
        // Tambahkan unit tanpa tanggal; pelanggan memilih tanggal saat checkout.
        $this->post(route('cart.store', $product), ['quantity' => 2])->assertSessionHasNoErrors();
        $keys = array_keys(session('rental_cart'));
        $base = ['event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $units = array_fill_keys($keys, $base);
        $this->actingAs($customer)->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => $units])->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('rental_orders', 0);
        // Tanggal yang tidak beririsan dapat memakai papan yang sama kembali.
        $units[$keys[2]]['event_on'] = $next;
        $units[$keys[2]]['ends_on'] = $next;
        $this->post(route('rental.checkout.store'), ['terms' => 1, 'units' => $units])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rental_orders', 1);
    }
    public function test_checkout_rechecks_stock_if_reservation_changes_after_adding_to_cart(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertSessionHasNoErrors();
        $key = array_key_first(session('rental_cart'));
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $other->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => $date, 'event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed'];
        $order->units()->create($base);
        $unit = ['event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $this->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$key => $unit]])->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('rental_orders', 1);
        $this->assertCount(1, session('rental_cart'));
    }
}
