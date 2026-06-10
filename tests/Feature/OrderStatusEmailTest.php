<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies customer-facing email behaviour & branded layouts:
 * - Order status notifications email ONLY customers who have an email.
 * - Admin/driver get in-app only (no inbox spam).
 * - The branded email views render without errors.
 */
class OrderStatusEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $kategori = ServiceCategory::create([
            'name' => 'Kiloan', 'pricing_model' => 'per_kg', 'is_active' => true,
        ]);
        $service = Service::create([
            'name' => 'Cuci Kiloan', 'slug' => 'cuci-'.uniqid(), 'pricing_model' => 'per_kg',
            'unit_price' => 8000, 'unit_type' => 'kg', 'price_per_kg' => 8000,
            'estimated_hours' => 24, 'description' => 'Kiloan',
            'service_category_id' => $kategori->id, 'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.orders.walk-in.store'), [
            'customer_name' => 'Email Customer',
            'customer_phone' => '081234567890',
            'service_category_id' => $kategori->id,
            'service_id' => $service->id,
            'weight_estimate' => 2,
            'pickup_time' => 'pagi',
        ]);

        return Order::latest()->firstOrFail();
    }

    public function test_customer_ber_email_dapat_channel_mail(): void
    {
        $order = $this->makeOrder();
        $notif = new OrderStatusUpdated($order, 'Pesanan Dijemput', 'Kurir sedang menuju lokasi kamu.');

        $customer = User::factory()->create(['role' => 'customer', 'email' => 'cust@example.com']);
        $this->assertContains('mail', $notif->via($customer));
        $this->assertContains('database', $notif->via($customer));
    }

    public function test_customer_tanpa_email_hanya_in_app(): void
    {
        $order = $this->makeOrder();
        $notif = new OrderStatusUpdated($order, 'Pesanan Dijemput', 'Kurir sedang menuju lokasi kamu.');

        $phoneOnly = User::factory()->create(['role' => 'customer', 'email' => null]);
        $this->assertEquals(['database'], $notif->via($phoneOnly));
    }

    public function test_admin_dan_driver_tidak_dapat_email_order(): void
    {
        $order = $this->makeOrder();
        $notif = new OrderStatusUpdated($order, 'Pesanan Baru', 'Ada pesanan baru.');

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);
        $driver = User::factory()->create(['role' => 'driver', 'email' => 'driver@example.com']);
        $this->assertEquals(['database'], $notif->via($admin));
        $this->assertEquals(['database'], $notif->via($driver));
    }

    public function test_email_status_pesanan_render_tanpa_error(): void
    {
        $order = $this->makeOrder();
        $customer = User::factory()->create(['role' => 'customer', 'email' => 'cust@example.com', 'name' => 'Siti']);

        $html = (new OrderStatusUpdated($order, 'Pesanan Dijemput', 'Kurir sedang menuju lokasi kamu.'))
            ->toMail($customer)
            ->render();

        $this->assertStringContainsString($order->order_code, $html);
        $this->assertStringContainsString('Pesanan Dijemput', $html);
        $this->assertStringContainsString('Azka Laundry', $html);
    }

    public function test_email_reset_password_render_tanpa_error(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'email' => 'cust@example.com', 'name' => 'Budi']);

        $html = (new ResetPasswordNotification('dummy-token'))
            ->toMail($user)
            ->render();

        $this->assertStringContainsString('Atur Ulang Password', $html);
        $this->assertStringContainsString('Azka Laundry', $html);
    }
}
