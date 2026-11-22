<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_separate_units_and_confirmation_respects_stock(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 2]);
        $items = session('rental_cart');
        $payload = [];
        foreach ($items as $key => $item) {
            $payload[$key] = ['starts_on' => now()->addDays(3)->toDateString(), 'event_on' => now()->addDays(3)->toDateString(), 'ends_on' => now()->addDays(4)->toDateString(), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        }
        $this->post(route('rental.checkout.store'), ['units' => $payload, 'terms' => '1'])->assertRedirect();
        $order = RentalOrder::with('units')->firstOrFail();
        $this->assertCount(2, $order->units);
        $this->assertSame(200000, (int) $order->units[0]->rental_amount);
        $this->actingAs($admin)->post(route('admin.rental.confirm', $order->units[0]))->assertSessionHasNoErrors();
        $this->post(route('admin.rental.confirm', $order->units[1]))->assertSessionHasErrors('unit');
        $this->assertSame('pending', $order->units[1]->fresh()->status);
    }

    public function test_checkout_shows_separate_units_and_optional_copy_control(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 2]);
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Unit 1')->assertSee('Unit 2')->assertSee('Salin detail dari unit sebelumnya')->assertSee('pilihan paket tidak disalin')->assertSee('data-checkout-submit', false)->assertSee('data-checkout-status', false)->assertSee('Ketentuan penyewaan')->assertSee('Konfirmasi dan stok.')->assertSee('DP dan pelunasan.')->assertSee('Jangan kirim pembayaran nyata.');
    }

    public function test_checkout_displays_current_price_and_returns_unavailable_units_to_cart(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 125000, 'price_unit' => 'day', 'stock' => 1]);
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Unit 1 dari 1')->assertSee('125.000')->assertSee('Periksa perkiraan ketersediaan');
        $category->update(['is_active' => false]);
        $this->get(route('rental.checkout'))->assertRedirect(route('cart.index'))->assertSessionHasErrors('cart');
        $this->get(route('cart.index'))->assertOk()->assertSee('Papan tidak tersedia')->assertSee('Hapus unit');
        $this->assertCount(1, session('rental_cart'));
    }

    public function test_checkout_identifies_the_unit_with_incomplete_delivery_information(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 2]);
        $keys = array_keys(session('rental_cart'));
        $base = ['starts_on' => now()->addDays(3)->toDateString(), 'event_on' => now()->addDays(3)->toDateString(), 'ends_on' => now()->addDays(3)->toDateString(), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $this->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$keys[0] => $base, $keys[1] => array_merge($base, ['delivery_method' => 'delivery'])]])->assertRedirect(route('rental.checkout'))->assertSessionHasErrors(['units' => 'Unit 2: alamat pengantaran wajib diisi.']);
        $this->assertDatabaseCount('rental_orders', 0);
    }

    public function test_checkout_uses_one_date_for_event_and_receipt_and_preserves_old_orders(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $key = array_key_first(session('rental_cart'));
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Tanggal acara dan penerimaan')->assertDontSee('name="units['.$key.'][starts_on]"', false);
        $event = now('Asia/Jakarta')->addDays(3)->toDateString();
        $end = now('Asia/Jakarta')->addDays(4)->toDateString();
        $unit = ['event_on' => $event, 'ends_on' => $end, 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $this->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$key => $unit + ['starts_on' => now('Asia/Jakarta')->addDays(2)->toDateString()]]])->assertSessionHasErrors('units');
        $this->assertDatabaseCount('rental_orders', 0);
        $this->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$key => $unit]])->assertSessionHasNoErrors();
        $order = RentalOrder::with('units')->firstOrFail();
        $saved = $order->units->first();
        $this->assertSame($event, $saved->starts_on->toDateString());
        $this->assertSame($event, $saved->event_on->toDateString());
        $this->assertSame(200, (int) $saved->rental_amount);
        $this->get(route('rental.orders.show', $order))->assertOk()->assertSee('Acara dan penerimaan:');
        $saved->update(['starts_on' => now('Asia/Jakarta')->addDays(2)->toDateString()]);
        $this->get(route('rental.orders.show', $order))->assertOk()->assertSee('Penerimaan:')->assertSee('Acara:');
    }

    public function test_checkout_offers_only_customers_own_saved_addresses_and_still_accepts_manual_address(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $customer->customerAddresses()->create(['label' => 'Alamat sendiri', 'recipient_name' => 'Nama', 'full_address' => 'Jalan Mawar nomor 10', 'is_primary' => true]);
        $deleted = $customer->customerAddresses()->create(['label' => 'Alamat dihapus', 'recipient_name' => 'Nama', 'full_address' => 'Jalan Lama nomor 11']);
        $deleted->delete();
        $other->customerAddresses()->create(['label' => 'Alamat rahasia', 'recipient_name' => 'Lain', 'full_address' => 'Jalan Rahasia nomor 12']);
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Alamat sendiri')->assertSee('Jalan Mawar nomor 10')->assertDontSee('Alamat dihapus')->assertDontSee('Alamat rahasia')->assertDontSee('Jalan Rahasia nomor 12');
        $key = array_key_first(session('rental_cart'));
        $event = now('Asia/Jakarta')->addDays(3)->toDateString();
        $this->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$key => ['event_on' => $event, 'ends_on' => $event, 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat manual berbeda', 'return_method' => 'dropoff']]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_order_units', ['delivery_address' => 'Alamat manual berbeda']);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->forceFill(['role' => 'customer'])->save();
        $other->forceFill(['role' => 'customer'])->save();
        $order = RentalOrder::create(['reference' => (string) \Illuminate\Support\Str::uuid(), 'customer_id' => $owner->id, 'confirmation_deadline' => now()->addDay()]);
        $this->actingAs($other)->get(route('rental.orders.show', $order))->assertForbidden();
    }
}
