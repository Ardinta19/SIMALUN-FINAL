<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use App\Services\ExpireRentalConfirmations;
use App\Services\RentalAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pesanan yang baru dikirim sudah menahan tanggal: pelanggan lain tidak
 * boleh melihat atau memesan tanggal itu sampai unit dikonfirmasi, dan
 * tanggalnya dilepas lagi kalau tenggat konfirmasi lewat.
 */
class RentalStockHoldTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock = 1): RentalProduct
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);

        return RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => 'Papan Tahan',
            'base_price' => 100000,
            'price_unit' => 'day',
            'stock' => $stock,
        ]);
    }

    private function order(User $customer, string $deadline = '+24 hours'): RentalOrder
    {
        return RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now('Asia/Jakarta')->modify($deadline),
        ]);
    }

    private function unit(RentalProduct $product, string $start, string $end, string $status = 'pending'): array
    {
        return [
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
        ];
    }

    public function test_pending_order_holds_dates_for_other_customers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $product = $this->product(1);
            $first = User::factory()->create(['role' => 'customer']);
            $this->order($first)->units()->create($this->unit($product, '2026-12-05', '2026-12-06'));

            $availability = app(RentalAvailability::class);
            $this->assertSame(['2026-12-05', '2026-12-06'], $availability->fullyBookedDates($product));
            $this->assertSame(0, $availability->minimumAvailable($product, '2026-12-05', '2026-12-06'));

            // Kalender katalog menandai tanggal itu sebagai sudah dibooking.
            $this->get(route('catalog.show', $product))->assertOk()
                ->assertSee('data-fc-disabled=\'["2026-12-05","2026-12-06"]\'', false)
                ->assertSee('sudah dibooking dan tidak bisa dipilih');

            // Pelanggan kedua ditolak saat menaruh unit bertanggal sama.
            $second = User::factory()->create(['role' => 'customer']);
            $this->actingAs($second)->post(route('cart.store', $product), [
                'quantity' => 1, 'starts_on' => '2026-12-05', 'ends_on' => '2026-12-06',
            ])->assertSessionHasErrors('quantity');

            // Tanggal lain tetap bisa dipesan.
            $this->post(route('cart.store', $product), [
                'quantity' => 1, 'starts_on' => '2026-12-08', 'ends_on' => '2026-12-09',
            ])->assertSessionHasNoErrors();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_checkout_of_second_customer_is_rejected_when_pending_order_holds_stock(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $product = $this->product(1);
            $first = User::factory()->create(['role' => 'customer']);
            $this->order($first)->units()->create($this->unit($product, '2026-12-12', '2026-12-13'));

            $second = User::factory()->create(['role' => 'customer']);
            $this->actingAs($second)->post(route('cart.store', $product), [
                'quantity' => 1, 'starts_on' => '2026-12-12', 'ends_on' => '2026-12-13',
            ])->assertSessionHasErrors('quantity');

            $this->assertSame(1, RentalOrderUnit::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_confirming_one_pending_unit_is_not_blocked_by_another_pending_unit(): void
    {
        $product = $this->product(2);
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order($customer);
        $first = $order->units()->create($this->unit($product, now('Asia/Jakarta')->addDays(5)->toDateString(), now('Asia/Jakarta')->addDays(6)->toDateString()));
        $second = $order->units()->create($this->unit($product, now('Asia/Jakarta')->addDays(5)->toDateString(), now('Asia/Jakarta')->addDays(6)->toDateString()));

        $this->actingAs($admin)->post(route('admin.rental.confirm', $first))->assertSessionHasNoErrors();
        $this->post(route('admin.rental.confirm', $second))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $second->fresh()->status);
    }

    public function test_admin_cannot_reduce_stock_below_dates_held_by_pending_orders(): void
    {
        $product = $this->product(2);
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->order($customer)->units()->create($this->unit($product, now('Asia/Jakarta')->addDays(5)->toDateString(), now('Asia/Jakarta')->addDays(6)->toDateString()));

        $payload = [
            'rental_category_id' => $product->rental_category_id,
            'name' => $product->name,
            'base_price' => 100,
            'price_unit' => 'day',
            'stock' => 1,
        ];
        $this->actingAs($admin)->put(route('admin.catalog.update', $product), $payload)
            ->assertSessionHasErrors('stock');
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_expired_pending_units_release_the_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $product = $this->product(1);
            $customer = User::factory()->create(['role' => 'customer']);
            $unit = $this->order($customer, '+24 hours')->units()->create($this->unit($product, '2026-12-10', '2026-12-11'));

            $availability = app(RentalAvailability::class);
            $this->assertSame(['2026-12-10', '2026-12-11'], $availability->fullyBookedDates($product));

            Carbon::setTestNow(Carbon::parse('2026-12-02 11:00:00', 'Asia/Jakarta'));
            $this->assertSame(1, app(ExpireRentalConfirmations::class)->run());

            $this->assertSame('cancelled', $unit->fresh()->status);
            $this->assertSame([], $availability->fullyBookedDates($product));
            $this->assertSame(1, $availability->minimumAvailable($product, '2026-12-10', '2026-12-11'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
