<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_shows_only_active_products_and_categories(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $hidden = RentalCategory::create(['name' => 'Nonaktif', 'is_active' => false]);
        RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Wisuda', 'base_price' => 150000, 'price_unit' => 'day', 'stock' => 2]);
        RentalProduct::create(['rental_category_id' => $hidden->id, 'name' => 'Papan Rahasia', 'base_price' => 1, 'price_unit' => 'event', 'stock' => 1]);
        $this->get('/')->assertOk()->assertSee('Papan Wisuda')->assertDontSee('Papan Rahasia');
    }

    public function test_admin_can_add_catalog_items_but_customer_cannot(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $data = ['rental_category_id' => $category->id, 'name' => 'Papan Merah', 'base_price' => 100000, 'price_unit' => 'event', 'stock' => 3];
        $this->actingAs($customer)->post(route('admin.catalog.store'), $data);
        $this->assertDatabaseMissing('rental_products', ['name' => 'Papan Merah']);
        $this->actingAs($admin)->post(route('admin.catalog.store'), $data)->assertRedirect();
        $this->assertDatabaseHas('rental_products', ['name' => 'Papan Merah', 'stock' => 3]);
    }

    public function test_inactive_product_is_not_accessible(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1, 'is_active' => false]);
        $this->get(route('catalog.show', $product))->assertNotFound();
    }
    public function test_admin_can_manage_categories_and_inactive_category_hides_products(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        RentalCategory::create(['name' => 'Pernikahan']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Wisuda', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $this->actingAs($customer)->put(route('admin.catalog.categories.update', $category), ['name' => 'Lain'])->assertRedirect(route('customer.dashboard'));
        $this->assertDatabaseHas('rental_categories', ['id' => $category->id, 'name' => 'Wisuda']);
        $this->actingAs($admin)->put(route('admin.catalog.categories.update', $category), ['name' => 'Pernikahan'])->assertSessionHasErrors('name');
        $this->put(route('admin.catalog.categories.update', $category), ['name' => 'Kelulusan'])->assertSessionHasNoErrors();
        $this->patch(route('admin.catalog.categories.toggle', $category))->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Papan Wisuda');
        $this->get(route('catalog.show', $product))->assertNotFound();
        $this->patch(route('admin.catalog.categories.toggle', $category))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Papan Wisuda');
        $this->assertDatabaseHas('rental_categories', ['id' => $category->id, 'name' => 'Kelulusan', 'is_active' => true]);
    }
}
