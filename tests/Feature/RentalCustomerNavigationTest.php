<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalCustomerNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_bottom_navigation_is_present_across_rental_pages_and_role_scoped(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $this->get(route('welcome'))->assertOk()->assertDontSee('id="customer-nav"', false);
        $this->actingAs($admin)->get(route('welcome'))->assertOk()->assertDontSee('id="customer-nav"', false);
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1])->assertRedirect();
        foreach ([route('welcome'), route('catalog.show', $product), route('cart.index'), route('rental.checkout'), route('rental.orders.index'), route('rental.orders.show', $order), route('customer.dashboard'), route('customer.profile'), route('customer.notifications'), route('customer.help'), route('customer.addresses.index'), route('customer.addresses.create'), route('customer.report')] as $url) {
            $this->get($url)->assertOk()->assertSee('id="customer-nav"', false)->assertSee('Navigasi pelanggan')->assertSee(route('customer.notifications'))->assertSee(route('customer.profile'));
        }
        $this->get(route('rental.orders.index'))->assertSee('customer-nav__item is-active', false)->assertSee('aria-current="page"', false);
        $this->get(route('rental.checkout'))->assertSee('customer-nav__fab is-active', false)->assertSee('aria-current="page"', false);
    }
    public function test_saved_address_pages_work_without_animation_dependencies_and_preserve_coordinates(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $customer->customerAddresses()->create(['label' => "Rumah 'contoh'", 'recipient_name' => 'Nama', 'full_address' => 'Jalan Mawar nomor 10', 'latitude' => -1.6101, 'longitude' => 103.6131, 'is_primary' => true]);
        $this->actingAs($customer)->get(route('customer.addresses.create'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('Peta bersifat opsional')->assertSee('type="checkbox" name="is_primary"', false)->assertDontSee('gsap.min.js');
        $this->get(route('customer.addresses.edit', $address))->assertOk()->assertSee('value="-1.6101000"', false)->assertSee('value="103.6131000"', false)->assertSee('Alamat utama saat ini')->assertDontSee('gsap.min.js');
        $this->get(route('customer.addresses.index'))->assertOk()->assertSee('Hapus alamat ini?')->assertDontSee('gsap.min.js')->assertDontSee('akan dihapus permanen');
    }
}
