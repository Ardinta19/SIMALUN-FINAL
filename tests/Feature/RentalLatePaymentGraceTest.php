<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\RentalUpdate;
use Tests\TestCase;

/**
 * Toleransi pembayaran terlambat: bukti masih diterima dan unit masih dapat
 * dilanjutkan sampai H+1 pukul 12.00 WIB. Sesudah batas itu DP hangus dan
 * kelanjutan ditolak.
 */
class RentalLatePaymentGraceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:\App\Models\RentalOrderUnit,1:User,2:User} */
    private function context(string $startsOn = '2026-12-02'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 5]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => $startsOn, 'event_on' => $startsOn, 'ends_on' => $startsOn,
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'confirmed_at' => now()->subDay(),
        ]);

        return [$unit, $admin, $customer];
    }

    private function cancelByDeadline(string $startsOn): array
    {
        [$unit, $admin, $customer] = $this->context($startsOn);
        Carbon::setTestNow(Carbon::parse($startsOn.' 00:00:00', 'Asia/Jakarta'));
        $this->artisan('rental:expire-payments')->assertExitCode(0);
        $this->assertSame('cancelled', $unit->fresh()->status);
        $this->assertSame('payment_timeout', $unit->fresh()->cancel_code);
        $this->assertNull($unit->fresh()->deposit_forfeited_at);

        return [$unit->fresh(), $admin, $customer];
    }

    public function test_late_proof_and_resumption_are_allowed_until_h1_noon(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-12-01 23:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin, $customer] = $this->cancelByDeadline('2026-12-02');

            // Bukti terlambat pada hari-H masih diterima.
            Carbon::setTestNow(Carbon::parse('2026-12-02 09:00:00', 'Asia/Jakarta'));
            $this->actingAs($customer)->post(route('rental.payment.store', $unit), ['amount' => 100, 'proof' => UploadedFile::fake()->image('late.jpg')])->assertSessionHasNoErrors();
            $proof = $unit->payments()->firstOrFail();
            $this->actingAs($admin)->post(route('admin.rental.payment.verify', $proof))->assertSessionHasNoErrors();
            $this->post(route('admin.rental.resume', $unit), ['reason' => 'Sudah lunas'])->assertSessionHasNoErrors();
            $this->assertSame('confirmed', $unit->fresh()->status);
            $this->assertNotNull($unit->fresh()->payment_late_resumed_at);

            // Unit yang tidak dilanjutkan tetap dalam toleransi sampai H+1 pukul 12.00.
            [$expired, , $expiredCustomer] = $this->cancelByDeadline('2026-12-02');
            Carbon::setTestNow(Carbon::parse('2026-12-03 11:59:00', 'Asia/Jakarta'));
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertNull($expired->fresh()->deposit_forfeited_at);
            $this->actingAs($expiredCustomer)->post(route('rental.payment.store', $expired), ['amount' => 100, 'proof' => UploadedFile::fake()->image('grace.jpg')])->assertSessionHasNoErrors();

            // Lewat pukul 12.00 WIB: bukti ditolak, kelanjutan ditolak, DP hangus.
            $graceProof = $expired->payments()->firstOrFail();
            $this->actingAs($admin)->post(route('admin.rental.payment.verify', $graceProof))->assertSessionHasNoErrors();
            Carbon::setTestNow(Carbon::parse('2026-12-03 12:00:01', 'Asia/Jakarta'));
            $this->actingAs($expiredCustomer)->post(route('rental.payment.store', $expired), ['amount' => 100, 'proof' => UploadedFile::fake()->image('too-late.jpg')])->assertSessionHasErrors('payment');
            // Rute admin: kembalikan identitas admin sebelum memanggilnya, jika tidak
            // middleware role akan menolak tanpa pesan galat validasi.
            $this->actingAs($admin)->post(route('admin.rental.resume', $expired), ['reason' => 'Terlambat'])->assertSessionHasErrors('unit');
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertNotNull($expired->fresh()->deposit_forfeited_at);
            $this->actingAs($admin)->post(route('admin.rental.resume', $expired), ['reason' => 'Terlambat lagi'])->assertSessionHasErrors('unit');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_grace_reminders_reach_customer_and_admin_once_per_day(): void
    {
        Storage::fake('local');
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-12-01 23:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin, $customer] = $this->cancelByDeadline('2026-12-02');
            Carbon::setTestNow(Carbon::parse('2026-12-02 09:00:00', 'Asia/Jakarta'));
            $this->actingAs($customer)->post(route('rental.payment.store', $unit), ['amount' => 100, 'proof' => UploadedFile::fake()->image('late.jpg')])->assertSessionHasNoErrors();

            $titles = fn (User $user) => Notification::sent($user, RentalUpdate::class)->map(fn (RentalUpdate $n) => $n->toArray($user)['title']);

            Carbon::setTestNow(Carbon::parse('2026-12-02 09:05:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(1, $titles($customer)->filter(fn ($t) => $t === 'Batas melanjutkan unit')->count());
            $this->assertSame(1, $titles($admin)->filter(fn ($t) => $t === 'Bukti terlambat menunggu verifikasi')->count());

            // Perintah berulang di hari yang sama tidak menggandakan pesan.
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(1, $titles($customer)->filter(fn ($t) => $t === 'Batas melanjutkan unit')->count());

            // Hari berikutnya pengingat muncul lagi selama masih dalam toleransi.
            Carbon::setTestNow(Carbon::parse('2026-12-03 08:00:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(2, $titles($customer)->filter(fn ($t) => $t === 'Batas melanjutkan unit')->count());
            $this->assertSame(2, $titles($admin)->filter(fn ($t) => $t === 'Bukti terlambat menunggu verifikasi')->count());

            // Setelah H+1 pukul 12.00 tidak ada pengingat toleransi lagi.
            Carbon::setTestNow(Carbon::parse('2026-12-03 12:30:00', 'Asia/Jakarta'));
            $this->artisan('rental:remind')->assertExitCode(0);
            $this->assertSame(2, $titles($customer)->filter(fn ($t) => $t === 'Batas melanjutkan unit')->count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
