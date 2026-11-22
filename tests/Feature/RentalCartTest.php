<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_separate_units_and_cart_survives_login(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 2]);
        $this->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect(route('cart.index'));
        $this->assertCount(2, session('rental_cart'));
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->post(route('login.post'), ['identifier' => $user->email, 'password' => 'password123'])->assertRedirect();
        $this->assertCount(2, session('rental_cart'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Papan');
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 1, 'price_unit' => 'day', 'stock' => 1, 'is_active' => false]);
        $this->post(route('cart.store', $product), ['quantity' => 1])->assertNotFound();
    }
}
