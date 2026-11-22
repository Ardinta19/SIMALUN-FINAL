<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_catalog_admin_form_survives_non_scalar_retry(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $this->actingAs($admin)->from(route('admin.catalog.index'))->post(route('admin.catalog.store'), [
            'rental_category_id' => $category->id, 'name' => ['Papan'], 'base_price' => ['100'], 'price_unit' => 'event', 'stock' => '1',
        ])->assertRedirect(route('admin.catalog.index'))->assertSessionHasErrors(['name', 'base_price']);
        $this->get(route('admin.catalog.index'))->assertOk()->assertDontSee('Array')->assertSee('name="name"', false);
    }

    public function test_catalog_photos_fit_without_cropping_and_assets_are_versioned(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $this->get(route('welcome'))->assertOk()
            ->assertSee('object-fit:contain', false)
            ->assertSee('css/berkah-experience.css?v=', false)
            ->assertSee('js/berkah-experience.js?v=', false);
    }

    public function test_catalog_admin_previews_the_current_photo_in_its_frame(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Berfoto', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1, 'image_path' => 'catalog/papan.jpg']);
        RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Kosong', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $this->actingAs($admin)->get(route('admin.catalog.index'))->assertOk()
            ->assertSee('admin-photo', false)->assertSee('storage/catalog/papan.jpg', false)
            ->assertSee('Foto Papan Berfoto')->assertSee('Belum ada foto')
            ->assertSee('object-fit:contain', false);
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
    public function test_catalog_photos_and_product_changes_are_managed_and_audited(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $data = ['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 2];
        $this->actingAs($admin)->post(route('admin.catalog.store'), $data + ['image' => UploadedFile::fake()->image('awal.jpg')])->assertSessionHasNoErrors();
        $product = RentalProduct::firstOrFail();
        $oldPath = $product->image_path;
        Storage::disk('public')->assertExists($oldPath);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rental.catalog.product.create', 'auditable_id' => $product->id, 'actor_id' => $admin->id]);
        $this->put(route('admin.catalog.update', $product), $data + ['image' => UploadedFile::fake()->image('baru.png')])->assertSessionHasNoErrors();
        $newPath = $product->fresh()->image_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rental.catalog.product.update', 'auditable_id' => $product->id]);
        $this->patch(route('admin.catalog.toggle', $product))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_logs', ['action' => 'rental.catalog.product.toggle', 'auditable_id' => $product->id]);
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_replacing_a_shared_photo_does_not_remove_the_other_products_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $data = ['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1];
        Storage::disk('public')->put('catalog/shared.jpg', 'old');
        $product = RentalProduct::create($data + ['image_path' => 'catalog/shared.jpg']);
        RentalProduct::create(array_merge($data, ['name' => 'Lain', 'image_path' => 'catalog/shared.jpg']));
        $this->actingAs($admin)->put(route('admin.catalog.update', $product), $data + ['image' => UploadedFile::fake()->image('baru.png')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists('catalog/shared.jpg');
    }
    public function test_category_and_package_changes_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.catalog.categories.store'), ['name' => 'Wisuda'])->assertSessionHasNoErrors();
        $category = RentalCategory::where('name', 'Wisuda')->firstOrFail();
        $this->put(route('admin.catalog.categories.update', $category), ['name' => 'Kelulusan'])->assertSessionHasNoErrors();
        $this->patch(route('admin.catalog.categories.toggle', $category))->assertSessionHasNoErrors();
        $this->patch(route('admin.catalog.categories.toggle', $category))->assertSessionHasNoErrors();
        foreach (['create', 'update', 'toggle'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'rental.catalog.category.'.$action, 'auditable_id' => $category->id]);
        }
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $this->post(route('admin.catalog.packages.store', $product), ['name' => 'Paket', 'days' => 2, 'price' => 150])->assertSessionHasNoErrors();
        $package = $product->packages()->firstOrFail();
        $this->put(route('admin.catalog.packages.update', $package), ['name' => 'Paket baru', 'days' => 3, 'price' => 200])->assertSessionHasNoErrors();
        $this->patch(route('admin.catalog.packages.toggle', $package))->assertSessionHasNoErrors();
        foreach (['create', 'update', 'toggle'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'rental.catalog.package.'.$action, 'auditable_id' => $package->id]);
        }
    }
}
