<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_rental_work_and_links_without_laundry_dispatch(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff']);
        $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk()->assertSee('Berkah Kreatif')->assertSee($order->reference)->assertSee(route('admin.rental.orders'))->assertSee(route('admin.rental.cashbook.index'))->assertDontSee('Tugaskan Kurir')->assertDontSee('Azka Laundry');
        $customer->delete();
        $this->get(route('dashboard.admin'))->assertOk()->assertSee('Pelanggan tidak tersedia');
        $this->get(route('admin.rental.orders'))->assertOk()->assertSee('Pelanggan tidak tersedia');
    }

    public function test_admin_navigation_reaches_the_help_guide(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $helpUrl = route('admin.help');

        $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk()
            ->assertSee('href="'.$helpUrl.'"', false);

        $this->get(route('admin.profile'))->assertOk()
            ->assertSee('href="'.$helpUrl.'"', false);

        $this->get($helpUrl)->assertOk()->assertSee('Panduan admin');
    }
}
