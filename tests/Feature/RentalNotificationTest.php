<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_updates_are_private_and_open_the_owner_order(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff']);
        $this->actingAs($admin)->post(route('admin.rental.confirm', $unit))->assertSessionHasNoErrors();
        $notification = $customer->notifications()->firstOrFail();
        $this->assertSame($order->id, $notification->data['rental_order_id']);
        $this->actingAs($other)->get(route('customer.notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($customer)->get(route('customer.notifications'))->assertOk()->assertSee('Unit dikonfirmasi');
        $this->get(route('customer.notifications.open', $notification->id))->assertRedirect(route('rental.orders.show', $order));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($admin)->get(route('admin.notifications'))->assertOk();
    }
}
