<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use App\Notifications\RentalUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengingat dua arah: bukti bayar yang menganggur, pelunasan H-1, dan respons
 * desain. Semua idempoten per unit+jenis+penerima+tanggal, dan tidak boleh
 * mengubah status apa pun.
 */
class RentalReminderTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:RentalOrderUnit,1:User,2:User} */
    private function context(string $startsOn = '2026-12-10', string $endsOn = '2026-12-11', int $amount = 100, string $delivery = 'pickup', string $return = 'dropoff'): array
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create([
            'rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 5,
        ]);
        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now('Asia/Jakarta')->addDay(),
        ]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan',
            'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => $amount,
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat',
            'delivery_method' => $delivery, 'return_method' => $return,
            'starts_on' => $startsOn, 'event_on' => $startsOn, 'ends_on' => $endsOn,
            'status' => 'confirmed', 'progress' => 'waiting',
        ]);

        return [$unit, $admin, $customer];
    }

    public function test_proof_reminder_waits_six_hours_and_is_idempotent(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin] = $this->context();
            $payment = $unit->payments()->create(['amount' => 100, 'proof_path' => 'bukti.jpg', 'submitted_at' => now()->subHours(5)]);

            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertNotSentTo($admin, RentalUpdate::class);

            $payment->forceFill(['submitted_at' => now()->subHours(7)])->save();
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertSentTo($admin, RentalUpdate::class, fn (RentalUpdate $notification) => str_contains($notification->toArray($admin)['title'], 'Bukti menunggu verifikasi'));
            Notification::assertCount(1);

            // Perintah jalan lagi di hari yang sama tidak menggandakan pesan.
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertCount(1);

            // Hari berikutnya pengingat boleh muncul lagi.
            Carbon::setTestNow(Carbon::parse('2026-12-02 09:00:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertSentToTimes($admin, RentalUpdate::class, 2);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_h1_reminder_skips_covered_bills_and_units_without_a_quote(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-12-04 08:00:00', 'Asia/Jakarta'));
        try {
            // A: belum ada bukti sama sekali -> diingatkan.
            [$due, $admin, $dueCustomer] = $this->context('2026-12-05', '2026-12-06');
            // B: bukti tepat waktu sudah menutup tagihan -> tidak diingatkan.
            [$covered, , $coveredCustomer] = $this->context('2026-12-05', '2026-12-06');
            $covered->payments()->create(['amount' => 100, 'proof_path' => 'bukti.jpg', 'submitted_at' => now()->subHour()]);
            // C: butuh ongkos dan belum ada penawaran -> tagihan belum pasti.
            [$unquoted, , $unquotedCustomer] = $this->context('2026-12-05', '2026-12-06', 100, 'delivery', 'collection');

            $this->artisan('rental:remind')->assertExitCode(0);

            Notification::assertSentTo($dueCustomer, RentalUpdate::class, fn (RentalUpdate $notification) => str_contains($notification->toArray($dueCustomer)['message'], 'sebelum pukul 23.59 WIB'));
            Notification::assertNotSentTo($coveredCustomer, RentalUpdate::class);
            Notification::assertNotSentTo($unquotedCustomer, RentalUpdate::class);
            Notification::assertSentToTimes($dueCustomer, RentalUpdate::class, 1);
            Notification::assertCount(1);
            // Pengingat tidak mengubah status apa pun.
            $this->assertSame('confirmed', $due->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_design_reminder_reaches_customer_then_escalates_to_admin(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin, $customer] = $this->context('2026-12-10', '2026-12-11');
            $preview = $unit->previews()->create(['version' => 1, 'image_path' => 'desain.jpg', 'status' => 'pending', 'created_by' => $admin->id]);

            // Baru diunggah: belum ada pengingat.
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertNotSentTo($customer, RentalUpdate::class);
            Notification::assertNotSentTo($admin, RentalUpdate::class);

            // 30 jam: pelanggan diingatkan; admin belum dieskalasi.
            $preview->forceFill(['created_at' => now()->subHours(30)])->save();
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertSentTo($customer, RentalUpdate::class, fn (RentalUpdate $notification) => $notification->toArray($customer)['title'] === 'Pratinjau desain menunggu');
            Notification::assertNotSentTo($admin, RentalUpdate::class);

            // 50 jam: admin dieskalasi pada hari yang sama (jenis berbeda).
            $preview->forceFill(['created_at' => now()->subHours(50)])->save();
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertSentTo($admin, RentalUpdate::class, fn (RentalUpdate $notification) => $notification->toArray($admin)['title'] === 'Pelanggan belum merespons desain');

            // Sudah direspons: tidak ada pengingat lagi besoknya.
            $preview->forceFill(['status' => 'approved', 'responded_at' => now()])->save();
            Carbon::setTestNow(Carbon::parse('2026-12-02 09:00:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            Notification::assertSentToTimes($customer, RentalUpdate::class, 1);
            Notification::assertSentToTimes($admin, RentalUpdate::class, 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_verification_clears_the_review_hold_only_when_the_bill_is_settled(): void
    {
        [$unit, $admin] = $this->context();
        $unit->forceFill(['payment_review_hold' => true, 'payment_review_resolved' => false])->save();
        $full = $unit->payments()->create(['amount' => 100, 'proof_path' => 'bukti.jpg', 'submitted_at' => now()]);
        $this->actingAs($admin)->post(route('admin.rental.payment.verify', $full))->assertSessionHasNoErrors();
        $settled = $unit->fresh();
        $this->assertFalse($settled->payment_review_hold);
        $this->assertTrue($settled->payment_review_resolved);

        // Pembayaran sebagian tidak boleh melepas peninjauan.
        [$partialUnit, $secondAdmin] = $this->context();
        $partialUnit->forceFill(['payment_review_hold' => true, 'payment_review_resolved' => false])->save();
        $part = $partialUnit->payments()->create(['amount' => 40, 'proof_path' => 'bukti.jpg', 'submitted_at' => now()]);
        $this->actingAs($secondAdmin)->post(route('admin.rental.payment.verify', $part))->assertSessionHasNoErrors();
        $this->assertTrue($partialUnit->fresh()->payment_review_hold);
    }

    public function test_review_scope_ignores_returned_and_cancelled_units(): void
    {
        [$unit] = $this->context();
        $unit->forceFill(['payment_review_hold' => true])->save();
        $this->assertSame(1, RentalOrderUnit::needsPaymentReview()->count());

        $unit->forceFill(['progress' => 'returned'])->save();
        $this->assertSame(0, RentalOrderUnit::needsPaymentReview()->count());

        $unit->forceFill(['progress' => 'waiting', 'status' => 'cancelled'])->save();
        $this->assertSame(0, RentalOrderUnit::needsPaymentReview()->count());
    }
}
