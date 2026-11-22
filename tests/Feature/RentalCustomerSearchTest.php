<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalCustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_filter_own_orders_without_exposing_other_customers(): void
    {
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $first = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $second = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $private = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $other->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-01', 'event_on' => '2026-12-01', 'ends_on' => '2026-12-01', 'recipient_name' => 'Penerima unik', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $first->units()->create($base + ['status' => 'pending']);
        $first->units()->create($base + ['status' => 'confirmed']);
        $second->units()->create(array_merge($base, ['recipient_name' => 'Lain', 'status' => 'cancelled']));
        $private->units()->create($base + ['status' => 'pending']);
        $url = route('rental.orders.index');
        $this->actingAs($customer)->get($url.'?q=Penerima+unik&status=confirmed')->assertOk()->assertSee($first->reference)->assertDontSee($second->reference)->assertDontSee($private->reference)->assertSee('Menunggu: 1')->assertSee('Dikonfirmasi: 1')->assertSee('Filter aktif')->assertSee(route('rental.orders.show', $first));
        $this->get($url.'?status=cancelled')->assertOk()->assertSee($second->reference)->assertDontSee($first->reference);
        $this->get($url.'?q=tidak-ada')->assertOk()->assertSee('Tidak ada pesanan yang cocok');
        $this->from($url)->get($url.'?status=invalid')->assertRedirect($url)->assertSessionHasErrors('status');
    }
}
