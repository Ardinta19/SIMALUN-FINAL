<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalCustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_previews_catalog_and_orders_remain_private_on_order_page(): void
    {
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $otherOrder = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $other->id, 'confirmation_deadline' => now()->addDay()]);
        $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff']);
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk()->assertSee('Berkah Kreatif')
            ->assertSee('data-catalog-preview', false)->assertSee(route('welcome'))->assertSee(route('rental.orders.index'))
            ->assertDontSee($order->reference)->assertDontSee($otherOrder->reference)->assertDontSee('Azka Laundry');
        $this->get(route('rental.orders.index'))->assertOk()->assertSee($order->reference)
            ->assertDontSee($otherOrder->reference)->assertSee(route('rental.orders.show', $order));
    }
}
