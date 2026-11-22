<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyOrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_order_success_page_is_not_visible_to_unassigned_driver_or_other_customer(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $driver = User::factory()->create(['role' => 'driver']);
        $assigned = User::factory()->create(['role' => 'driver']);
        $admin = User::factory()->create(['role' => 'admin']);
        $service = Service::create(['name' => 'Cuci', 'slug' => 'cuci-test', 'pricing_model' => 'per_kg', 'unit_price' => 100, 'unit_type' => 'kg', 'price_per_kg' => 100, 'estimated_hours' => 24, 'is_active' => true]);
        $order = Order::create(['order_code' => 'ORD-TEST-001', 'customer_id' => $owner->id, 'service_id' => $service->id, 'address' => 'Alamat', 'zone' => 'A', 'pickup_cost' => 0, 'pickup_date' => now()->toDateString(), 'pickup_time' => 'siang', 'weight_estimate' => 1, 'service_cost' => 100, 'discount' => 0, 'total_cost' => 100, 'status' => 'menunggu', 'driver_id' => $assigned->id]);
        $url = route('order.show', $order->order_code);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($driver)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($assigned)->get($url)->assertOk();
        $this->actingAs($admin)->get($url)->assertOk();
    }
}
