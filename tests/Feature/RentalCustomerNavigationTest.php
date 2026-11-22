<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalPaymentSetting;
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
        $this->get(route('rental.checkout'))->assertSee('customer-nav__item is-active', false)->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Keranjang, 1 unit"', false)->assertSee('>Keranjang</span>', false);
        $this->get(route('welcome'))->assertSee('>Katalog</span>', false)->assertDontSee('Buat Pesanan Baru');
        foreach ([route('rental.orders.index'), route('customer.notifications'), route('customer.dashboard'), route('customer.profile'), route('customer.addresses.create'), route('customer.report')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('grid-template-columns:repeat(6,minmax(0,1fr))', false)
                ->assertSee('line-height:12px', false)
                ->assertSee('height:68px', false)
                ->assertSee('env(safe-area-inset-bottom, 0px)', false);
        }
    }
    public function test_guest_and_customer_have_accessible_catalog_navigation_without_admin_links(): void
    {
        $this->get(route('welcome'))->assertOk()
            ->assertSee('Langsung ke isi')->assertSee('Navigasi utama')->assertSee('Navigasi cepat')
            ->assertSee('id="bk-command"', false)->assertSee(route('login'))->assertDontSee(route('admin.rental.orders'));
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('welcome'))->assertOk()
            ->assertSee(route('rental.orders.index'))->assertSee(route('customer.help'))
            ->assertSee('id="customer-nav"', false)->assertDontSee(route('admin.rental.orders'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('welcome'))->assertOk()
            ->assertSee(route('admin.rental.orders'))->assertDontSee('id="bk-command"', false);
    }

    public function test_customer_discovery_preserves_filter_and_dashboard_navigation(): void
    {
        $this->get(route('welcome'))->assertOk()->assertSee('id="bk-filter"', false)
            ->assertSee('data-filter-form', false)->assertSee('name="starts_on"', false)->assertSee('name="ends_on"', false);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk()
            ->assertSee('Aktivitas terbaru')->assertSee(route('cart.index'))->assertSee(route('customer.help'))
            ->assertSee('id="bk-command"', false)->assertSee('id="customer-nav"', false);
    }

    public function test_saved_address_pages_work_without_animation_dependencies_and_preserve_coordinates(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $customer->customerAddresses()->create(['label' => "Rumah 'contoh'", 'recipient_name' => 'Nama', 'full_address' => 'Jalan Mawar nomor 10', 'latitude' => -1.6101, 'longitude' => 103.6131, 'is_primary' => true]);
        $this->actingAs($customer)->get(route('customer.addresses.create'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('Peta bersifat opsional')->assertSee('type="checkbox" name="is_primary"', false)->assertDontSee('gsap.min.js');
        $this->get(route('customer.addresses.edit', $address))->assertOk()->assertSee('value="-1.6101000"', false)->assertSee('value="103.6131000"', false)->assertSee('Alamat utama saat ini')->assertDontSee('gsap.min.js');
        $this->get(route('customer.addresses.index'))->assertOk()->assertSee('Hapus alamat ini?')->assertDontSee('gsap.min.js')->assertDontSee('akan dihapus permanen');
    }
    public function test_report_page_uses_only_configured_business_contact(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('customer.report'))->assertOk()
            ->assertSee('Berkah Kreatif')->assertDontSee('Azka Laundry')->assertDontSee('wa.me/')->assertDontSee('1x24 jam');
        RentalPaymentSetting::create(['id' => 1, 'whatsapp_number' => '6289876543210']);
        $this->get(route('customer.report'))->assertOk()->assertSee('https://wa.me/6289876543210', false);
    }
}
