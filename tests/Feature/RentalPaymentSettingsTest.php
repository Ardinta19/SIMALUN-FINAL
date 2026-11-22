<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_payment_instructions_visible_to_owner_with_wib_deadline(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-02', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $url = route('admin.rental.payment-settings.update');
        $this->actingAs($customer)->put($url, ['instructions' => 'Jangan tersimpan'])->assertRedirect(route('customer.dashboard'));
        $this->assertDatabaseCount('rental_payment_settings', 0);
        $this->actingAs($admin)->put($url, ['instructions' => 'Transfer ke rekening usaha'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_payment_settings', ['id' => 1, 'instructions' => 'Transfer ke rekening usaha']);
        $this->assertSame('2026-12-01 23:59:59', $unit->plannedPaymentDeadline()->format('Y-m-d H:i:s'));
        $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee('Transfer ke rekening usaha')->assertSee('01/12/2026 23:59');
    }
}
