<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalDateFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_by_confirmed_overlap_and_preserves_dates_to_checkout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $full = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Penuh', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
            $open = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Tersedia', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
            $customer = User::factory()->create(['role' => 'customer']);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $attrs = ['rental_product_id' => $full->id, 'product_name' => 'Papan Penuh', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-03', 'event_on' => '2026-12-03', 'ends_on' => '2026-12-04', 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed'];
            $order->units()->create($attrs);
            $dates = ['starts_on' => '2026-12-04', 'ends_on' => '2026-12-05'];
            $this->get(route('welcome', $dates))->assertOk()->assertSee('Papan Tersedia')->assertDontSee('Papan Penuh')->assertSee('belum dicadangkan')
                ->assertSee(route('welcome', $dates + ['category' => $category->id]))->assertSee('Kategori papan bunga')->assertSee('Hapus filter tanggal');
            $this->get(route('catalog.show', array_merge(['product' => $full->id], $dates)))->assertOk()->assertSee('0 unit')->assertDontSee('Tambah ke keranjang');
            $this->from(route('catalog.show', $full))->post(route('cart.store', $full), $dates + ['quantity' => 1])->assertSessionHasErrors('quantity');
            $this->post(route('cart.store', $open), $dates + ['quantity' => 1])->assertSessionHasNoErrors();
            $this->get(route('cart.index'))->assertOk()->assertSee('Tanggal pilihan')->assertSee('+ Tambah unit')->assertSee('− Hapus unit');
            $this->actingAs($customer)->get(route('rental.checkout'))->assertOk()->assertSee('value="2026-12-04"', false)->assertSee('value="2026-12-05"', false);
            $this->get(route('welcome', ['starts_on' => '2026-12-03']))->assertSessionHasErrors('ends_on');
        } finally { Carbon::setTestNow(); }
    }

    public function test_invalid_catalog_dates_are_preserved_and_attached_to_filter_fields(): void
    {
        $start = now('Asia/Jakarta')->addDays(3)->toDateString();
        $end = now('Asia/Jakarta')->addDays(2)->toDateString();
        $this->from(route('welcome'))->get(route('welcome', ['q' => 'mawar', 'starts_on' => $start, 'ends_on' => $end]))
            ->assertRedirect(route('welcome'))->assertSessionHasErrors('ends_on');
        $this->get(route('welcome'))->assertOk()->assertSee('data-filter-errors="1"', false)
            ->assertSee('value="mawar"', false)->assertSee('value="'.$start.'"', false)->assertSee('value="'.$end.'"', false)
            ->assertSee('aria-describedby="catalog-end-error"', false);
    }

    public function test_invalid_detail_dates_preserve_the_attempted_values(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $start = now('Asia/Jakarta')->addDays(3)->toDateString();
        $end = now('Asia/Jakarta')->addDays(2)->toDateString();
        $url = route('catalog.show', $product);
        $this->from($url)->get(route('catalog.show', ['product' => $product->id, 'starts_on' => $start, 'ends_on' => $end]))
            ->assertRedirect($url)->assertSessionHasErrors('ends_on');
        $this->get($url)->assertOk()->assertSee('value="'.$start.'"', false)->assertSee('value="'.$end.'"', false)
            ->assertSee('aria-describedby="detail-end-error"', false);
    }

    public function test_failed_date_search_does_not_offer_to_add_using_previous_dates(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $previous = now('Asia/Jakarta')->addDays(3)->toDateString();
        $start = now('Asia/Jakarta')->addDays(5)->toDateString();
        $end = now('Asia/Jakarta')->addDays(4)->toDateString();
        $catalog = route('welcome', ['starts_on' => $previous, 'ends_on' => $previous]);
        $this->from($catalog)->get(route('welcome', ['starts_on' => $start, 'ends_on' => $end]))->assertSessionHasErrors('ends_on');
        $this->get($catalog)->assertOk()->assertSee('Pencarian belum diterapkan')->assertSee('Perbaiki tanggal dan cari kembali')->assertDontSee(route('cart.store', $product));
        $detail = route('catalog.show', ['product' => $product->id, 'starts_on' => $previous, 'ends_on' => $previous]);
        $this->from($detail)->get(route('catalog.show', ['product' => $product->id, 'starts_on' => $start, 'ends_on' => $end]))->assertSessionHasErrors('ends_on');
        $this->get($detail)->assertOk()->assertSee('agar tanggal sebelumnya tidak ikut digunakan')->assertDontSee(route('cart.store', $product));
        $this->get($detail)->assertSee(route('cart.store', $product));
    }

    public function test_non_scalar_filter_inputs_do_not_break_the_retry_page(): void
    {
        $this->from(route('welcome'))->get(route('welcome', ['q' => ['bad'], 'starts_on' => ['bad']]))->assertSessionHasErrors(['q', 'starts_on']);
        $this->get(route('welcome'))->assertOk()->assertSee('id="catalog-search-error"', false)->assertSee('id="catalog-start-error"', false);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $url = route('catalog.show', $product);
        $this->from($url)->get(route('catalog.show', ['product' => $product->id, 'starts_on' => ['bad'], 'ends_on' => '2026-12-01']))->assertSessionHasErrors('starts_on');
        $this->get($url)->assertOk()->assertSee('id="detail-start-error"', false);
    }

    public function test_catalog_cards_link_to_details_and_add_one_unit_with_selected_dates(): void
    {
        $category = RentalCategory::create(['name' => 'Katalog']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Melati', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $this->get(route('welcome', ['starts_on' => $date, 'ends_on' => $date]))->assertOk()
            ->assertSee('data-open-dialog="bk-filter"', false)->assertSee('href="#catalog-filter"', false)
            ->assertSee('Lihat detail Papan Melati')->assertSee('Tambah Papan Melati ke keranjang')
            ->assertSee(route('cart.store', $product))->assertSee('name="quantity" value="1"', false)
            ->assertSee('name="starts_on" value="'.$date.'"', false);
        $this->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertRedirect(route('cart.index'));
        $this->assertCount(1, session('rental_cart'));
    }

    public function test_catalog_reports_cart_capacity_or_stock_errors_next_to_quantity(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $url = route('catalog.show', $product);
        $this->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertSessionHasNoErrors();
        $this->from($url)->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertRedirect($url)->assertSessionHasErrors('quantity');
        $this->get($url)->assertOk()->assertSee('id="quantity-error"', false)->assertSee('Jumlah papan tidak tersedia')->assertSee('Tambah ke keranjang');
    }

    public function test_expired_cart_dates_link_to_an_undated_detail_and_can_be_changed_at_checkout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
            $date = '2026-12-02';
            $this->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertSessionHasNoErrors();
            $key = array_key_first(session('rental_cart'));
            Carbon::setTestNow(Carbon::parse('2026-12-03 10:00:00', 'Asia/Jakarta'));
            $this->get(route('cart.index'))->assertOk()->assertSee('Tanggal pilihan sudah lewat')
                ->assertSee('href="'.route('catalog.show', $product).'"', false)->assertDontSee(route('cart.duplicate', $key));
            $this->get(route('catalog.show', $product))->assertOk()->assertSee('Periksa perkiraan ketersediaan');
            $customer = User::factory()->create(['role' => 'customer']);
            $this->actingAs($customer)->get(route('rental.checkout'))->assertOk()->assertSee('value="'.$date.'"', false);
        } finally { Carbon::setTestNow(); }
    }

    public function test_cart_quantity_and_checkout_recheck_overlapping_units(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $customer = User::factory()->create(['role' => 'customer']);
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $next = now('Asia/Jakarta')->addDays(4)->toDateString();
        $this->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertSessionHasNoErrors();
        $first = array_key_first(session('rental_cart'));
        $this->post(route('cart.duplicate', $first))->assertSessionHasNoErrors();
        $this->assertCount(2, session('rental_cart'));
        $this->from(route('cart.index'))->post(route('cart.duplicate', $first))->assertSessionHasErrors('quantity');
        $this->delete(route('cart.destroy', $first))->assertRedirect();
        $this->assertCount(1, session('rental_cart'));
        // Tambahkan unit tanpa tanggal; pelanggan memilih tanggal saat checkout.
        $this->post(route('cart.store', $product), ['quantity' => 2])->assertSessionHasNoErrors();
        $keys = array_keys(session('rental_cart'));
        $base = ['event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $units = array_fill_keys($keys, $base);
        $this->actingAs($customer)->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => $units])->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('rental_orders', 0);
        // Tanggal yang tidak beririsan dapat memakai papan yang sama kembali.
        $units[$keys[2]]['event_on'] = $next;
        $units[$keys[2]]['ends_on'] = $next;
        $this->post(route('rental.checkout.store'), ['terms' => 1, 'units' => $units])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rental_orders', 1);
    }
    public function test_checkout_rechecks_stock_if_reservation_changes_after_adding_to_cart(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1, 'starts_on' => $date, 'ends_on' => $date])->assertSessionHasNoErrors();
        $key = array_key_first(session('rental_cart'));
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $other->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => $date, 'event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed'];
        $order->units()->create($base);
        $unit = ['event_on' => $date, 'ends_on' => $date, 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $this->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$key => $unit]])->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('rental_orders', 1);
        $this->assertCount(1, session('rental_cart'));
    }
}
