<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the realtime driver-location tracking feature end-to-end:
 * a driver posts GPS coordinates for an active task, and the owning
 * customer can read the latest driver position via the polling endpoint.
 */
class DriverLocationTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAssignedOrder(User $driver): Order
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $kategori = ServiceCategory::create([
            'name' => 'Kiloan',
            'pricing_model' => 'per_kg',
            'is_active' => true,
        ]);

        $service = Service::create([
            'name' => 'Cuci Kiloan',
            'slug' => 'cuci-kiloan-'.uniqid(),
            'pricing_model' => 'per_kg',
            'unit_price' => 8000,
            'unit_type' => 'kg',
            'price_per_kg' => 8000,
            'estimated_hours' => 24,
            'description' => 'Kiloan',
            'service_category_id' => $kategori->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.orders.walk-in.store'), [
            'customer_name' => 'Tracking Customer',
            'customer_phone' => '081234567890',
            'service_category_id' => $kategori->id,
            'service_id' => $service->id,
            'weight_estimate' => 2,
            'pickup_time' => 'pagi',
        ]);

        $order = Order::latest()->firstOrFail();
        $order->update(['driver_id' => $driver->id, 'status' => 'dijemput']);

        return $order->refresh();
    }

    public function test_driver_kirim_lokasi_dan_customer_bisa_membacanya(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $order = $this->makeAssignedOrder($driver);

        // Driver mengirim koordinat GPS untuk tugas aktifnya.
        $this->actingAs($driver)
            ->postJson(route('driver.orders.location', $order), ['lat' => -1.6101, 'lng' => 103.6131])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $order->refresh();
        $this->assertEqualsWithDelta(-1.6101, (float) $order->driver_lat, 0.0001);
        $this->assertEqualsWithDelta(103.6131, (float) $order->driver_lng, 0.0001);
        $this->assertNotNull($order->driver_location_updated_at);

        // Customer pemilik order membaca posisi kurir lewat endpoint polling.
        $this->actingAs($order->customer)
            ->getJson(route('customer.tracking.location'))
            ->assertOk()
            ->assertJson([
                'has_driver' => true,
                'status' => 'dijemput',
            ]);
    }

    public function test_driver_lain_tidak_bisa_kirim_lokasi_order_bukan_miliknya(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $order = $this->makeAssignedOrder($driver);

        $driverLain = User::factory()->create(['role' => 'driver']);

        $this->actingAs($driverLain)
            ->postJson(route('driver.orders.location', $order), ['lat' => 0, 'lng' => 0])
            ->assertForbidden();
    }

    public function test_lokasi_ditolak_saat_order_tidak_dalam_status_aktif(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $order = $this->makeAssignedOrder($driver);
        $order->update(['status' => 'dicuci']); // bukan dijemput/dikirim

        $this->actingAs($driver)
            ->postJson(route('driver.orders.location', $order), ['lat' => -1.61, 'lng' => 103.61])
            ->assertStatus(422);
    }

    public function test_validasi_koordinat_di_luar_rentang_ditolak(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $order = $this->makeAssignedOrder($driver);

        $this->actingAs($driver)
            ->postJson(route('driver.orders.location', $order), ['lat' => 200, 'lng' => 999])
            ->assertStatus(422);
    }
}
