<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Notifications\RentalUpdate;
use App\Services\RentalAutoClose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Unit yang telanjur tidak ditandai kembali ditutup otomatis setelah masa
 * tenggang, sehingga antrean aktif tidak menggantung dan rating pesanan
 * pelanggan terbuka kembali.
 */
class RentalAutoCloseTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:RentalOrder,3:\App\Models\RentalOrderUnit} */
    private function context(array $overrides = []): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(array_merge([
            'rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => '2026-12-10', 'event_on' => '2026-12-10', 'ends_on' => '2026-12-11',
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'handed_over',
        ], $overrides));

        return [$admin, $customer, $order, $unit];
    }

    public function test_unit_is_closed_after_grace_and_customer_can_rate_the_order(): void
    {
        Notification::fake();
        [$admin, $customer, $order, $unit] = $this->context();
        $unit->forceFill([
            'ends_on' => now('Asia/Jakarta')->subDays(RentalAutoClose::GRACE_DAYS + 1)->toDateString(),
            'event_on' => now('Asia/Jakarta')->subDays(RentalAutoClose::GRACE_DAYS + 2)->toDateString(),
            'starts_on' => now('Asia/Jakarta')->subDays(RentalAutoClose::GRACE_DAYS + 2)->toDateString(),
        ])->save();

        $this->artisan('rental:close-events')->assertExitCode(0);
        $fresh = $unit->fresh();
        $this->assertSame('returned', $fresh->progress);
        $event = $fresh->progressEvents()->latest('id')->first();
        // Riwayat wajib punya pencatat; penutupan sistem memakai admin aktif
        // pertama dan catatannya menyebut tindakan otomatis.
        $this->assertSame($admin->id, $event->recorded_by);
        $this->assertStringContainsString('Ditutup otomatis', $event->note);
        $this->assertStringContainsString('sistem', $event->note);

        $titles = Notification::sent($customer, RentalUpdate::class)->map(fn (RentalUpdate $n) => $n->toArray($customer)['title']);
        $this->assertTrue($titles->contains('Unit ditutup otomatis'));

        // Dijalankan ulang tidak menggandakan penutupan maupun riwayat.
        $this->artisan('rental:close-events')->assertExitCode(0);
        $this->assertSame(1, $fresh->progressEvents()->count());

        // Rating pesanan kini terbuka karena seluruh unit sudah selesai.
        $this->actingAs($customer)->post(route('rental.order.rating', $order), ['rating' => 5])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_order_reviews', ['rental_order_id' => $order->id, 'rating' => 5]);
    }

    public function test_stuck_unit_no_longer_blocks_order_rating_after_grace(): void
    {
        [, $customer, $order, $returned] = $this->context();
        $returned->forceFill(['progress' => 'returned'])->save();
        $stuck = $order->units()->create([
            'rental_product_id' => $returned->rental_product_id, 'product_name' => 'Melati', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => now('Asia/Jakarta')->subDays(10)->toDateString(), 'event_on' => now('Asia/Jakarta')->subDays(10)->toDateString(), 'ends_on' => now('Asia/Jakarta')->subDays(9)->toDateString(),
            'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'handed_over',
        ]);

        // Sebelum dijalankan penutupan otomatis, acara yang sudah lewat jauh
        // tidak lagi memblokir rating pesanan.
        $this->actingAs($customer)->post(route('rental.order.rating', $order), ['rating' => 4])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_order_reviews', ['rental_order_id' => $order->id, 'rating' => 4]);
    }

    public function test_unit_inside_grace_or_not_handed_over_is_left_alone(): void
    {
        Notification::fake();
        [, , , $recent] = $this->context(['ends_on' => now('Asia/Jakarta')->subDays(RentalAutoClose::GRACE_DAYS - 1)->toDateString()]);
        [, , , $ready] = $this->context(['progress' => 'ready', 'ends_on' => now('Asia/Jakarta')->subDays(30)->toDateString()]);

        $this->artisan('rental:close-events')->assertExitCode(0);
        $this->assertSame('handed_over', $recent->fresh()->progress);
        $this->assertSame('ready', $ready->fresh()->progress);
    }
}
