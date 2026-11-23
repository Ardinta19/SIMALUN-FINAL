<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use App\Services\RentalAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Jendela pemesanan (hari ini sampai 366 hari ke depan) harus sama di
 * kalender, batas input tanggal, dan validasi server. Kalender filter
 * katalog hanya memblokir tanggal yang penuh untuk semua papan.
 */
class RentalBookingWindowTest extends TestCase
{
    use RefreshDatabase;

    private function category(): RentalCategory
    {
        return RentalCategory::create(['name' => 'Wisuda']);
    }

    private function product(RentalCategory $category, string $name = 'Papan', int $stock = 1): RentalProduct
    {
        return RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => $name,
            'base_price' => 100,
            'price_unit' => 'day',
            'stock' => $stock,
        ]);
    }

    private function booking(RentalProduct $product, string $status, string $start = '2026-12-05', string $end = '2026-12-06'): RentalOrderUnit
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now('Asia/Jakarta')->addDay(),
        ]);

        return $order->units()->create([
            'rental_product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100,
            'price_unit' => 'day',
            'rental_amount' => 100,
            'recipient_name' => 'Penerima',
            'greeting' => 'Selamat wisuda',
            'delivery_method' => 'pickup',
            'return_method' => 'dropoff',
            'starts_on' => $start,
            'event_on' => $start,
            'ends_on' => $end,
            'status' => $status,
        ]);
    }

    public function test_window_is_one_year_and_cart_rejects_dates_outside_it(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $window = RentalAvailability::bookingWindow();
            $this->assertSame(366, RentalAvailability::BOOKING_HORIZON_DAYS);
            $this->assertSame('2026-12-01', $window['from']->toDateString());
            $this->assertSame('2027-12-01', $window['to']->toDateString());

            $product = $this->product($this->category(), 'Papan', 2);
            $customer = User::factory()->create(['role' => 'customer']);

            $this->actingAs($customer)->post(route('cart.store', $product), [
                'quantity' => 1, 'starts_on' => '2027-12-02', 'ends_on' => '2027-12-03',
            ])->assertSessionHasErrors('starts_on');
            $this->assertStringContainsString('paling jauh 366 hari', session('errors')->first('starts_on'));

            $this->post(route('cart.store', $product), [
                'quantity' => 1, 'starts_on' => '2026-11-30', 'ends_on' => '2026-12-01',
            ])->assertSessionHasErrors('starts_on');

            $this->post(route('cart.store', $product), [
                'quantity' => 1, 'starts_on' => '2027-12-01', 'ends_on' => '2027-12-01',
            ])->assertSessionHasNoErrors();

            $this->get(route('welcome', ['starts_on' => '2027-12-02', 'ends_on' => '2027-12-03']))
                ->assertSessionHasErrors('starts_on');
            $this->get(route('catalog.show', ['product' => $product->id, 'starts_on' => '2027-12-02', 'ends_on' => '2027-12-03']))
                ->assertSessionHasErrors('starts_on');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_checkout_rejects_unit_dates_outside_the_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $product = $this->product($this->category(), 'Papan', 2);
            $customer = User::factory()->create(['role' => 'customer']);
            $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
            $key = array_key_first(session('rental_cart'));

            $unit = [
                'starts_on' => '2028-01-05', 'event_on' => '2028-01-05', 'ends_on' => '2028-01-06',
                'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            ];
            $this->from(route('rental.checkout'))->post(route('rental.checkout.store'), ['terms' => 1, 'units' => [$key => $unit]])
                ->assertSessionHasErrors('units');
            $this->assertStringContainsString('paling jauh 366 hari', session('errors')->first('units'));
            $this->assertDatabaseCount('rental_orders', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_index_calendar_blocks_a_date_only_when_every_product_is_full(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $category = $this->category();
            $first = $this->product($category, 'Papan Satu', 1);
            $second = $this->product($category, 'Papan Dua', 1);
            $this->booking($first, 'confirmed');

            // Papan Dua masih kosong, jadi tanggal itu masih bisa dicari.
            $this->get(route('welcome'))->assertOk()
                ->assertSee('max="2027-12-01"', false)
                ->assertSee("data-fc-disabled='[]'", false)
                ->assertDontSee('sudah penuh untuk semua papan');

            // Pesanan pending pun menahan tanggal: sekarang semua papan penuh.
            $this->booking($second, 'pending');

            $this->get(route('welcome'))->assertOk()
                ->assertSee("data-fc-disabled='[\"2026-12-05\",\"2026-12-06\"]'", false)
                ->assertSee('2 tanggal')
                ->assertSee('sudah penuh untuk semua papan');

            // Pencarian pada rentang yang penuh menjelaskan keadaannya.
            $this->get(route('welcome', ['starts_on' => '2026-12-05', 'ends_on' => '2026-12-06']))->assertOk()
                ->assertSee('Tidak ada papan yang tersedia pada')
                ->assertSee('Hapus filter tanggal');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_index_calendar_follows_stock_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $product = $this->product($this->category(), 'Papan', 1);
            $this->booking($product, 'cancelled');
            $this->get(route('welcome'))->assertOk()->assertSee("data-fc-disabled='[]'", false);

            $this->booking($product, 'confirmed');
            $this->get(route('welcome'))->assertOk()->assertSee("data-fc-disabled='[\"2026-12-05\",\"2026-12-06\"]'", false);

            // Unit yang dibatalkan melepas tanggalnya lagi.
            RentalOrderUnit::where('status', 'confirmed')->update(['status' => 'cancelled']);
            $this->get(route('welcome'))->assertOk()->assertSee("data-fc-disabled='[]'", false);
        } finally {
            Carbon::setTestNow();
        }
    }
}
