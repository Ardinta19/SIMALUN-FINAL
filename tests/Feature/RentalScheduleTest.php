<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_shows_booked_days_and_pending_without_cancelled_units(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan Mawar', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-11-30', 'event_on' => '2026-12-01', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $order->units()->create($base + ['status' => 'confirmed']);
        $order->units()->create(array_merge($base, ['product_name' => 'Papan Dibatalkan', 'status' => 'cancelled']));
        $url = route('admin.rental.schedule', ['month' => '2026-12']);
        $this->actingAs($customer)->get($url)->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Papan Mawar')->assertSee('Dikonfirmasi · stok dicadangkan')->assertSee('Pengembalian hari ini')->assertDontSee('Papan Dibatalkan');
    }
}
