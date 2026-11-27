<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Notifications\RentalUpdate;
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
        $this->actingAs($customer)->get(route('customer.notifications'))->assertOk()->assertSee('Unit dikonfirmasi')
            ->assertSee('Belum dibaca')->assertSee('Tandai semua dibaca')->assertSee(route('customer.notifications.open', $notification->id));
        $this->get(route('customer.notifications.open', $notification->id))->assertRedirect(route('rental.orders.show', $order));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route('customer.notifications'))->assertOk()->assertDontSee('Belum dibaca');
        $this->actingAs($admin)->get(route('admin.notifications'))->assertOk();
    }
    public function test_admin_can_review_notifications_individually_and_open_the_related_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $admin->notify(new RentalUpdate($order->id, 'Bukti baru', 'Periksa bukti pembayaran.'));
        $admin->notify(new RentalUpdate($order->id, 'Pesanan baru', 'Periksa pesanan.'));
        $notification = $admin->notifications()->firstOrFail();
        $this->actingAs($admin)->get(route('admin.notifications'))->assertOk()->assertSee('Tandai semua dibaca')->assertSee(route('admin.notifications.open', $notification->id), false);
        $this->assertSame(2, $admin->unreadNotifications()->count());
        $this->actingAs($otherAdmin)->get(route('admin.notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.notifications.open', $notification->id))->assertRedirect(route('admin.rental.orders', ['q' => $order->reference]));
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->post(route('admin.notifications.read-all'))->assertSessionHasNoErrors();
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_tidak_ada_rute_tandai_dibaca_yang_tidak_dipakai(): void
    {
        $rute = (string) file_get_contents(base_path('routes/web.php'));
        $this->assertStringNotContainsString("/notifications/{id}/read", $rute);
        $this->assertStringNotContainsString("'notifications.read'", $rute);

        $controller = (string) file_get_contents(app_path('Http/Controllers/NotificationController.php'));
        $this->assertStringNotContainsString('function markRead(', $controller);
        // Satu-satunya jalur membuka satu notifikasi tetap ada.
        $this->assertStringContainsString('function markAndRedirect(', $controller);
    }
}
