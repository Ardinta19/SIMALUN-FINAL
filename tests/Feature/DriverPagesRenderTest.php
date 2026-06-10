<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests: every revamped DRIVER page must render (HTTP 200) without
 * Blade/runtime errors, both on an empty database and with an assigned order.
 * Guards the ui-ux-revamp against template regressions before deploy.
 */
class DriverPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): User
    {
        return User::factory()->create(['role' => 'driver']);
    }

    public function test_semua_halaman_driver_render_tanpa_error_pada_db_kosong(): void
    {
        $driver = $this->driver();

        $routes = [
            'driver.dashboard',
            'driver.orders',
            'driver.tracking',
            'driver.notifications',
            'driver.profile',
            'driver.help',
            'driver.report',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($driver)->get(route($name));
            $this->assertSame(200, $response->status(), "Route {$name} mengembalikan {$response->status()}, harusnya 200.");
        }
    }

    public function test_halaman_detail_tugas_driver_render_dengan_order_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = $this->driver();

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
            'customer_name' => 'Detail Render',
            'customer_phone' => '081234567890',
            'service_category_id' => $kategori->id,
            'service_id' => $service->id,
            'weight_estimate' => 3,
            'pickup_time' => 'siang',
        ]);

        // Tugaskan ke driver & set status agar muncul sebagai tugas aktif.
        $order = Order::latest()->firstOrFail();
        $order->update(['driver_id' => $driver->id, 'status' => 'dijemput']);

        // Detail tugas
        $this->assertSame(200, $this->actingAs($driver)->get(route('driver.orders.show', $order))->status());
        // Daftar tugas & tracking dengan data nyata
        $this->assertSame(200, $this->actingAs($driver)->get(route('driver.orders'))->status());
        $this->assertSame(200, $this->actingAs($driver)->get(route('driver.tracking'))->status());
    }
}
