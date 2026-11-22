<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalDurationPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_price_is_validated_and_snapshotted_on_checkout(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $this->actingAs($admin)->post(route('admin.catalog.packages.store', $product), ['name' => 'Paket dua hari', 'days' => 2, 'price' => 150000])->assertSessionHasNoErrors();
        $package = $product->packages()->firstOrFail();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $key = array_key_first(session('rental_cart'));
        $unit = ['starts_on' => now()->addDays(3)->toDateString(), 'event_on' => now()->addDays(3)->toDateString(), 'ends_on' => now()->addDays(3)->toDateString(), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'package_id' => $package->id];
        $this->post(route('rental.checkout.store'), ['units' => [$key => $unit], 'terms' => '1'])->assertSessionHasErrors('units');
        $this->assertDatabaseCount('rental_orders', 0);
        $unit['ends_on'] = now()->addDays(4)->toDateString();
        $this->post(route('rental.checkout.store'), ['units' => [$key => $unit], 'terms' => '1'])->assertSessionHasNoErrors();
        $ordered = RentalOrder::firstOrFail()->units()->firstOrFail();
        $this->assertSame(150000, (int) $ordered->rental_amount);
        $this->actingAs($admin)->put(route('admin.catalog.packages.update', $package), ['name' => 'Baru', 'days' => 2, 'price' => 200000])->assertSessionHasNoErrors();
        $this->assertSame('Paket dua hari', $ordered->fresh()->package_name);
        $this->assertSame(150000, (int) $ordered->fresh()->rental_amount);
    }
}
