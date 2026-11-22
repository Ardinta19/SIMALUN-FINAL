<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalAdminSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_search_and_unit_filters_narrow_orders(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $first = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $second = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-01', 'event_on' => '2026-12-01', 'ends_on' => '2026-12-01', 'recipient_name' => 'Penerima unik', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $first->units()->create($base + ['status' => 'pending']);
        $second->units()->create(array_merge($base, ['recipient_name' => 'Penerima lain', 'event_on' => '2026-12-05', 'ends_on' => '2026-12-05', 'status' => 'confirmed']));
        $this->actingAs($admin)->get(route('admin.rental.orders', ['q' => 'Penerima unik']))->assertOk()->assertSee($first->reference)->assertDontSee($second->reference);
        $this->get(route('admin.rental.orders', ['status' => 'confirmed', 'from' => '2026-12-01', 'to' => '2026-12-03']))->assertOk()->assertDontSee($first->reference)->assertDontSee($second->reference);
        $this->get(route('admin.rental.orders', ['status' => 'confirmed', 'to' => '2026-12-06']))->assertOk()->assertSee($second->reference)->assertDontSee($first->reference);
    }
    public function test_task_filters_distinguish_quote_approval_and_latest_design_revision(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
        $orders = collect(range(1, 3))->map(fn () => RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]));
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-01', 'event_on' => '2026-12-01', 'ends_on' => '2026-12-01', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat', 'return_method' => 'dropoff', 'status' => 'confirmed'];
        $orders[0]->units()->create($base);
        $second = $orders[1]->units()->create($base + ['transport_quote' => 0]);
        $third = $orders[2]->units()->create($base + ['transport_quote' => 10, 'transport_approved_at' => now()]);
        $second->previews()->create(['version' => 1, 'image_path' => 'test.png', 'created_by' => $admin->id, 'status' => 'revision']);
        $third->previews()->create(['version' => 1, 'image_path' => 'test.png', 'created_by' => $admin->id, 'status' => 'revision']);
        $third->previews()->create(['version' => 2, 'image_path' => 'test2.png', 'created_by' => $admin->id, 'status' => 'pending']);
        $this->actingAs($admin)->get(route('admin.rental.orders', ['status' => 'transport_quote']))->assertOk()->assertSee($orders[0]->reference)->assertDontSee($orders[1]->reference)->assertDontSee($orders[2]->reference);
        $this->get(route('admin.rental.orders', ['status' => 'transport_approval']))->assertOk()->assertSee($orders[1]->reference)->assertDontSee($orders[0]->reference)->assertDontSee($orders[2]->reference);
        $this->get(route('admin.rental.orders', ['status' => 'design_initial']))->assertOk()->assertSee($orders[0]->reference)->assertDontSee($orders[1]->reference)->assertSee(route('admin.rental.orders.show', $orders[0]));
        $this->get(route('admin.rental.orders.show', $orders[0]))->assertOk()->assertSee('Preview desain · 0 versi')->assertSee('Ongkos dan tagihan')->assertSee('Riwayat dan perkembangan');
        $this->get(route('admin.rental.orders', ['status' => 'design_revision']))->assertOk()->assertSee($orders[1]->reference)->assertDontSee($orders[2]->reference)->assertDontSee($orders[0]->reference);
    }
    public function test_pending_orders_are_prioritised_by_confirmation_deadline_and_seller_cancellations_are_filterable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
        $later = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addHours(20)]);
        $urgent = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addHours(2)]);
        $cancelled = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addHours(2)]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $later->units()->create($base); $urgent->units()->create($base);
        $cancelled->units()->create($base + ['status' => 'cancelled', 'cancel_responsibility' => 'seller', 'cancel_reason' => 'Penjual']);
        $this->actingAs($admin)->get(route('admin.rental.orders', ['status' => 'pending']))->assertOk()->assertSeeInOrder([$urgent->reference, $later->reference])->assertDontSee($cancelled->reference);
        $this->get(route('admin.rental.orders', ['status' => 'seller_cancelled']))->assertOk()->assertSee($cancelled->reference)->assertDontSee($urgent->reference);
        $this->get(route('dashboard.admin'))->assertOk()->assertSee('Perlu perhatian')->assertSee('2 unit menunggu konfirmasi')->assertSee('1 unit dibatalkan karena penjual')->assertSee('termasuk yang sudah ditindaklanjuti');
    }
}
