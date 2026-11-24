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
        foreach ([route('welcome'), route('catalog.show', $product), route('cart.index'), route('rental.checkout'), route('rental.orders.index'), route('rental.orders.show', $order), route('customer.dashboard'), route('customer.profile'), route('customer.notifications'), route('customer.help'), route('customer.addresses.index'), route('customer.addresses.create')] as $url) {
            $this->get($url)->assertOk()->assertSee('id="customer-nav"', false)->assertSee('Navigasi pelanggan')->assertSee(route('customer.notifications'))->assertSee(route('customer.profile'));
        }
        $this->get(route('rental.orders.index'))->assertSee('customer-nav__item is-active', false)->assertSee('aria-current="page"', false);
        $this->get(route('rental.checkout'))->assertSee('customer-nav__item is-active', false)->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Keranjang, 1 unit"', false)->assertSee('Bantuan / FAQ')->assertSee('data-customer-radial', false)
            ->assertSee('id="bk-menu-goo"', false)->assertSee('name="terms"', false)
            ->assertSee('css/page-transitions.css', false);
        $this->get(route('welcome'))->assertSee('>Katalog</span>', false)->assertDontSee('Buat Pesanan Baru');
        foreach ([route('rental.orders.index'), route('customer.notifications'), route('customer.dashboard'), route('customer.profile'), route('customer.addresses.create')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('grid-template-columns:repeat(4,minmax(0,1fr))', false)
                // Label bawah memakai token sejak tangga tipe disatukan:
                // ukuran huruf 12 px (--bk-fs-2xs) dengan leading rapat
                // (--bk-lh-tight) — bukan lagi angka 10px/12px yang lepas.
                ->assertSee('font-size:var(--bk-fs-2xs)', false)
                ->assertSee('line-height:var(--bk-lh-tight)', false)
                ->assertSee('height:68px', false)
                ->assertSee('env(safe-area-inset-bottom, 0px)', false);
        }
    }
    public function test_order_filter_retains_safe_inputs_and_identifies_invalid_fields(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $url = route('rental.orders.index');
        $this->actingAs($customer)->from($url)->get(route('rental.orders.index', ['q' => 'Mawar', 'status' => 'invalid']))->assertRedirect($url)->assertSessionHasErrors('status');
        $this->get($url)->assertOk()->assertSee('value="Mawar"', false)->assertSee('aria-describedby="order-status-error"', false)->assertSee('Pencarian belum diterapkan');
        $this->from($url)->get(route('rental.orders.index', ['q' => ['bad']]))->assertSessionHasErrors('q');
        $this->get($url)->assertOk()->assertSee('id="order-search-error"', false);
    }

    public function test_legacy_customer_pages_keep_cart_in_tab_bar_and_menu_above_the_content(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('rental.orders.index'));
        $response->assertOk()->assertSee('id="customer-nav"', false)
            ->assertSee('customer-nav__label">Keranjang', false)
            ->assertSee('href="'.route('cart.index').'" class="customer-nav__item', false)
            // Menu pelanggan sekarang berupa disclosure di kepala halaman
            // (bk-header-radial), bukan pita lama bk-top-menu.
            ->assertSee('bk-header-radial', false)->assertSee('data-customer-radial', false);
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="customer-nav"'), strpos($html, 'data-customer-radial'), 'Menu pelanggan harus berada di atas, bukan menggantikan keranjang di tab bawah.');
        // Hitung elemen <details> saja: skrip menu juga menyebut nama atribut ini.
        $this->assertSame(1, substr_count($html, 'data-customer-radial>'), 'Menu pelanggan hanya boleh dirender sekali agar filter SVG tidak duplikat.');
        $catalog = $this->get(route('welcome'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($catalog, 'data-customer-radial>'));
    }

    public function test_guest_and_customer_have_accessible_catalog_navigation_without_admin_links(): void
    {
        $this->get(route('welcome'))->assertOk()
            ->assertSee('Langsung ke isi')->assertSee('Navigasi utama')->assertSee('Navigasi cepat')
            ->assertSee('aria-label="Navigasi utama"', false)->assertSee(route('login'))->assertDontSee(route('admin.rental.orders'))
            // Tamu memakai busur yang sama; dialog "Jelajahi" sudah dicabut.
            ->assertSee('data-customer-radial', false)->assertSee('bk-header-radial', false)
            ->assertSee(route('register'))->assertDontSee('id="bk-menu"', false);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('welcome'))->assertOk()
            ->assertSee(route('rental.orders.index'))->assertSee(route('customer.help'))
            ->assertSee('id="customer-nav"', false)->assertSee('bk-header-radial', false)
            ->assertSee('href="'.route('cart.index').'" class="customer-nav__item', false)
            ->assertSee('body.bk-customer .bk-desktop-nav', false)->assertDontSee(route('admin.rental.orders'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('welcome'))->assertOk()
            ->assertSee(route('admin.rental.orders'))->assertDontSee('id="bk-command"', false);
    }

    /** Busur di halaman tamu harus patuh aturan tampil yang sama dengan
     *  pelanggan: hanya di ponsel, dan panelnya bertumpu pada tombol.
     *  Sebelumnya aturan itu hanya hidup di partial pelanggan, sehingga tamu
     *  melihat busur mengambang di atas hero (layar lebar) dan menimpa
     *  wordmark (layar sempit). */
    public function test_busur_tamu_memakai_aturan_tampil_yang_sama(): void
    {
        $response = $this->get(route('welcome'));
        $html = $response->assertOk()->getContent();

        $this->assertStringContainsString('.bk-header-radial{display:none;position:relative;flex:0 0 auto}', $html);
        $this->assertStringContainsString('@media(max-width:700px){.bk-header-radial{display:block}}', $html);

        // Tema bawaan (tanpa data-theme) tetap punya titik tumpu panel: aturan
        // tampil tidak boleh bergantung pada atribut tema.
        $this->assertStringNotContainsString('html[data-theme] .bk-header-radial{display:', $html);

        // Tujuan yang ada di busur (Daftar) harus tetap ada di tautan header
        // untuk layar lebar: setup sekali, bukan sekali per lebar layar.
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, route('register')),
            'Tujuan “Daftar” harus muncul di busur dan di navigasi header.'
        );
    }

    public function test_desktop_navigation_keeps_accessible_shortcuts_and_current_state(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('customer.notifications'))->assertOk()
            ->assertSee('aria-label="Pintasan akun dan keranjang"', false)
            ->assertSee('aria-label="Notifikasi"', false)->assertSee('aria-label="Profil"', false)
            ->assertSee('href="'.route('customer.notifications').'" class="bk-desktop-action"', false)->assertSee('aria-current="page"', false)
            ->assertSee(route('customer.help'));
        $this->get(route('welcome'))->assertOk()->assertSee('data-open-dialog="bk-filter"', false)
            ->assertDontSee('data-open-dialog="bk-command"', false);
    }

    public function test_customer_discovery_preserves_filter_and_dashboard_navigation(): void
    {
        $this->get(route('welcome'))->assertOk()->assertSee('id="bk-filter"', false)
            ->assertSee('data-filter-form', false)->assertSee('name="starts_on"', false)->assertSee('name="ends_on"', false);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk()
            ->assertSee('Temukan papan untuk momenmu')->assertSee('data-catalog-preview', false)->assertDontSee('Aktivitas terbaru')->assertSee(route('cart.index'))->assertSee(route('customer.help'))
            ->assertSee('aria-label="Navigasi utama"', false)->assertSee('id="customer-nav"', false);
    }

    public function test_customer_back_links_are_descriptive_and_styled_as_buttons(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        foreach ([route('customer.profile'), route('customer.notifications'), route('customer.help'), route('rental.orders.index'), route('cart.index')] as $url) {
            $this->actingAs($customer)->get($url)->assertOk()->assertSee('class="bk-back"', false)
                ->assertSee('css/page-transitions.css', false)->assertSee('js/page-transitions.js', false);
        }
        $this->get(route('customer.addresses.create'))->assertOk()->assertSee('aria-label="Kembali ke daftar alamat"', false);
    }

    public function test_saved_address_pages_work_without_animation_dependencies_and_preserve_coordinates(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $customer->customerAddresses()->create(['label' => "Rumah 'contoh'", 'recipient_name' => 'Nama', 'full_address' => 'Jalan Mawar nomor 10', 'latitude' => -1.6101, 'longitude' => 103.6131, 'is_primary' => true]);
        $this->actingAs($customer)->get(route('customer.addresses.create'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('Peta bersifat opsional')->assertSee('aria-label="Navigasi utama"', false)->assertSee('type="checkbox" name="is_primary"', false)->assertSee('id="map-lat"', false);
        $this->get(route('customer.addresses.edit', $address))->assertOk()->assertSee('value="-1.6101000"', false)->assertSee('value="103.6131000"', false)->assertSee('Alamat utama saat ini')->assertSee('id="map-lat"', false);
        $this->get(route('customer.addresses.index'))->assertOk()->assertSee('Hapus alamat ini?')->assertSee('Alamat utama')
            ->assertSee(route('customer.addresses.edit', $address))->assertSee(route('customer.addresses.destroy', $address))
            ->assertSee('Ya, hapus alamat')->assertDontSee('akan dihapus permanen');
    }
    public function test_help_page_uses_only_configured_business_contact(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('customer.help'))->assertOk()
            ->assertSee('Berkah Kreatif')->assertDontSee('Azka Laundry')->assertDontSee('wa.me/');
        RentalPaymentSetting::create(['id' => 1, 'whatsapp_number' => '6289876543210']);
        $this->get(route('customer.help'))->assertOk()->assertSee('https://wa.me/6289876543210', false);
    }
}
