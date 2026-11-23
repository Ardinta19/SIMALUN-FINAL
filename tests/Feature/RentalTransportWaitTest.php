<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Notifications\RentalUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Antrean antar/jemput dan acara yang lewat: dua titik tunggu yang tidak
 * memiliki tenggat, kini diberi pengingat harian ke pihak yang seharusnya
 * bergerak.
 */
class RentalTransportWaitTest extends TestCase
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
            'starts_on' => '2026-12-20', 'event_on' => '2026-12-20', 'ends_on' => '2026-12-21',
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'waiting',
        ], $overrides));

        return [$admin, $customer, $order, $unit];
    }

    private function titles(User $user): \Illuminate\Support\Collection
    {
        return Notification::sent($user, RentalUpdate::class)->map(fn (RentalUpdate $n) => $n->toArray($user)['title']);
    }

    public function test_admin_is_reminded_daily_when_transport_is_never_quoted(): void
    {
        Notification::fake();
        [$admin, , , $unit] = $this->context(['delivery_method' => 'delivery', 'delivery_address' => 'Alamat']);
        $unit->forceFill(['created_at' => now()->subHours(25)])->save();

        $this->artisan('rental:remind')->assertExitCode(0);
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($admin)->filter(fn ($t) => $t === 'Ongkos belum ditawarkan')->count());

        // Setelah penawaran dikirim, admin tidak lagi diingatkan soal itu.
        $unit->forceFill(['transport_quote' => 50000, 'transport_quote_version' => 1])->save();
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($admin)->filter(fn ($t) => $t === 'Ongkos belum ditawarkan')->count());
    }

    public function test_customer_is_reminded_daily_when_quote_is_not_answered(): void
    {
        Notification::fake();
        [, $customer, , $unit] = $this->context(['delivery_method' => 'delivery', 'delivery_address' => 'Alamat']);
        $unit->forceFill(['transport_quote' => 50000, 'transport_quote_version' => 1])->save();
        $quote = $unit->transportQuoteEvents()->create(['action' => 'quote', 'version' => 1, 'amount' => 50000, 'recorded_by' => null]);
        $quote->forceFill(['created_at' => now()->subHours(25)])->save();

        $this->artisan('rental:remind')->assertExitCode(0);
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($customer)->filter(fn ($t) => $t === 'Ongkos menunggu persetujuan')->count());

        // Penawaran baru (kurang dari 24 jam) belum diingatkan lagi.
        $unit->transportQuoteEvents()->create(['action' => 'quote', 'version' => 2, 'amount' => 60000, 'recorded_by' => null]);
        $unit->forceFill(['transport_quote' => 60000, 'transport_quote_version' => 2])->save();
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($customer)->filter(fn ($t) => $t === 'Ongkos menunggu persetujuan')->count());

        // Setelah disetujui, tidak ada lagi pengingat ongkos.
        $unit->forceFill(['transport_approved_at' => now()])->save();
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($customer)->filter(fn ($t) => $t === 'Ongkos menunggu persetujuan')->count());
    }

    public function test_admin_is_reminded_when_event_passed_without_finished_progress(): void
    {
        Notification::fake();
        [$admin, , , $unit] = $this->context(['event_on' => now('Asia/Jakarta')->subDays(2)->toDateString(), 'starts_on' => now('Asia/Jakarta')->subDays(2)->toDateString(), 'ends_on' => now('Asia/Jakarta')->subDays(1)->toDateString()]);

        $this->artisan('rental:remind')->assertExitCode(0);
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($admin)->filter(fn ($t) => $t === 'Tanggal acara sudah lewat')->count());

        // Unit yang sudah diserahkan tidak termasuk antrean ini.
        $unit->forceFill(['progress' => 'handed_over'])->save();
        $this->artisan('rental:remind')->assertExitCode(0);
        $this->assertSame(1, $this->titles($admin)->filter(fn ($t) => $t === 'Tanggal acara sudah lewat')->count());
    }
}
