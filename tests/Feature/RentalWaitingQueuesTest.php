<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Notifications\RentalUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Antrean dua arah: unit yang menunggu pelanggan, unit dalam toleransi
 * pembayaran, dan unit yang belum ditandai kembali.
 */
class RentalWaitingQueuesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:RentalProduct,3:\App\Models\RentalOrderUnit,4:RentalOrder} */
    private function context(?string $reference = null): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 5]);
        $order = RentalOrder::create(['reference' => $reference ?? (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => '2026-12-20', 'event_on' => '2026-12-20', 'ends_on' => '2026-12-21',
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'waiting',
        ]);

        return [$admin, $customer, $product, $unit, $order];
    }

    public function test_admin_dashboard_shows_units_waiting_for_customers(): void
    {
        [$admin, $customer, , $unit] = $this->context();
        $unit->previews()->create(['version' => 1, 'image_path' => 'desain.jpg', 'status' => 'pending', 'created_by' => $admin->id]);
        $unit->forceFill(['transport_quote' => 50, 'transport_quote_version' => 1, 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat'])->save();

        $this->actingAs($admin)->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Menunggu pelanggan')
            ->assertSee('1 unit menunggu persetujuan desain')
            ->assertSee('1 unit menunggu persetujuan ongkos')
            ->assertSee('unit dalam toleransi pembayaran');
    }

    public function test_admin_filters_for_grace_and_overdue_returns(): void
    {
        [$admin, , , $grace] = $this->context('REF-GRACE-001');
        [, , , $manual] = $this->context('REF-MANUAL-002');
        $manual->forceFill(['status' => 'cancelled', 'cancel_code' => 'manual', 'cancel_responsibility' => 'other'])->save();
        $grace->forceFill(['status' => 'cancelled', 'cancel_code' => 'payment_timeout', 'cancel_responsibility' => 'customer', 'deposit_forfeited_at' => null])->save();

        [, , , $returned] = $this->context('REF-LATE-RETURN-003');
        $returned->forceFill(['progress' => 'handed_over', 'ends_on' => now('Asia/Jakarta')->subDays(3)->toDateString()])->save();

        $this->actingAs($admin)->get(route('admin.rental.orders', ['status' => 'grace']))
            ->assertOk()->assertSee('REF-GRACE-001')->assertDontSee('REF-MANUAL-002');
        $this->actingAs($admin)->get(route('admin.rental.orders', ['status' => 'return_overdue']))
            ->assertOk()->assertSee('REF-LATE-RETURN-003')->assertDontSee('REF-GRACE-001');
    }

    public function test_unreturned_unit_reminder_is_sent_once_per_day(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-12-25 09:00:00', 'Asia/Jakarta'));
        try {
            [$admin, , , $unit] = $this->context();
            $unit->forceFill(['progress' => 'handed_over', 'ends_on' => '2026-12-24'])->save();

            $titles = fn (User $user) => Notification::sent($user, RentalUpdate::class)->map(fn (RentalUpdate $n) => $n->toArray($user)['title']);

            // Kurang dari dua hari setelah tanggal kembali: belum diingatkan.
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(0, $titles($admin)->filter(fn ($t) => $t === 'Unit belum ditandai kembali')->count());

            Carbon::setTestNow(Carbon::parse('2026-12-26 09:00:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(1, $titles($admin)->filter(fn ($t) => $t === 'Unit belum ditandai kembali')->count());

            Carbon::setTestNow(Carbon::parse('2026-12-27 09:00:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(2, $titles($admin)->filter(fn ($t) => $t === 'Unit belum ditandai kembali')->count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
