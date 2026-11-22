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
}
