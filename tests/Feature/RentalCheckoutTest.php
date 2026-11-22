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
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Unit 1')->assertSee('Unit 2')->assertSee('Salin detail dari unit sebelumnya')->assertSee('pilihan paket tidak disalin');
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
