<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests: every revamped ADMIN page must render (HTTP 200) without
 * Blade/runtime errors, both on an empty database and with real data.
 * Guards the ui-ux-revamp against template regressions before deploy.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_semua_halaman_admin_render_tanpa_error_pada_db_kosong(): void
    {
        $admin = $this->admin();

        $routes = [
            'admin.dashboard',
            'admin.orders',
            'admin.walkin.form',
            'admin.finance.index',
            'admin.reports',
            'admin.vouchers.index',
            'admin.vouchers.create',
            'admin.categories.index',
            'admin.audit.index',
            'admin.notifications',
            'admin.profile',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($admin)->get(route($name));
            $this->assertSame(200, $response->status(), "Route {$name} mengembalikan {$response->status()}, harusnya 200.");
        }
    }

    public function test_halaman_struk_admin_render_dengan_order_nyata(): void
    {
        $admin = $this->admin();

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

        // Buat order lewat endpoint walk-in (jalur yang sudah teruji).
        $this->actingAs($admin)->post(route('admin.orders.walk-in.store'), [
            'customer_name' => 'Render Test',
            'customer_phone' => '081234567890',
            'service_category_id' => $kategori->id,
            'service_id' => $service->id,
            'weight_estimate' => 2,
            'pickup_time' => 'pagi',
        ]);

        $order = Order::latest()->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.orders.receipt', $order));
        $this->assertSame(200, $response->status());

        // Halaman daftar pesanan dengan data nyata juga harus render.
        $this->assertSame(200, $this->actingAs($admin)->get(route('admin.orders'))->status());
    }
}
