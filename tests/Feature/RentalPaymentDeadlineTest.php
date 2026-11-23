<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalPaymentDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timely_proof_holds_cancellation_and_late_resumption_checks_payment_and_stock(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-12-01 23:59:59', 'Asia/Jakarta'));
        try {
            $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
            $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-02', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed', 'confirmed_at' => now()->subHour()];
            $held = $order->units()->create($attrs); $late = $order->units()->create($attrs); $expired = $order->units()->create($attrs);
            $this->actingAs($customer)->post(route('rental.payment.store', $held), ['amount' => 100, 'proof' => UploadedFile::fake()->image('proof.jpg')])->assertSessionHasNoErrors();
            $proof = $held->payments()->firstOrFail();
            Carbon::setTestNow(Carbon::parse('2026-12-02 00:00:00', 'Asia/Jakarta'));
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertSame('confirmed', $held->fresh()->status);
            $this->assertSame('cancelled', $late->fresh()->status);
            $this->assertSame('payment_timeout', $late->fresh()->cancel_code);
            $this->actingAs($admin)->post(route('admin.rental.payment.verify', $proof))->assertSessionHasNoErrors();
            $this->actingAs($customer)->post(route('rental.payment.store', $late), ['amount' => 100, 'proof' => UploadedFile::fake()->image('late.jpg')])->assertSessionHasNoErrors();
            $lateProof = $late->payments()->firstOrFail();
            $this->actingAs($admin)->post(route('admin.rental.resume', $late), ['reason' => 'Lanjut'])->assertSessionHasErrors('unit');
            $this->post(route('admin.rental.payment.verify', $lateProof))->assertSessionHasNoErrors();
            $this->post(route('admin.rental.resume', $late), ['reason' => 'Lanjut'])->assertSessionHasNoErrors();
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertSame('confirmed', $late->fresh()->status);
            // Toleransi verifikasi masih berlaku sampai H+1 pukul 12.00 WIB.
            Carbon::setTestNow(Carbon::parse('2026-12-03 11:59:00', 'Asia/Jakarta'));
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertNull($expired->fresh()->deposit_forfeited_at);
            Carbon::setTestNow(Carbon::parse('2026-12-03 12:00:01', 'Asia/Jakarta'));
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertNotNull($expired->fresh()->deposit_forfeited_at);
            $this->post(route('admin.rental.resume', $expired), ['reason' => 'Terlambat'])->assertSessionHasErrors('unit');
        } finally { Carbon::setTestNow(); }
    }

    public function test_rejected_timely_settlement_is_held_for_manual_correction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 23:59:00', 'Asia/Jakarta'));
        try {
            $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
            $customer = User::factory()->create();
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-02', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed', 'confirmed_at' => now()->subHour()]);
            $proof = $unit->payments()->create(['amount' => 100, 'proof_path' => 'proof.jpg', 'submitted_at' => now()]);
            Carbon::setTestNow(Carbon::parse('2026-12-02 00:01:00', 'Asia/Jakarta'));
            $this->actingAs($admin)->post(route('admin.rental.payment.reject', $proof), ['reason' => 'Bukti kurang jelas'])->assertSessionHasNoErrors();
            $this->assertTrue($unit->fresh()->payment_review_hold);
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertSame('confirmed', $unit->fresh()->status);
            $this->post(route('admin.rental.payment.resolve-review', $unit), ['confirmed' => 1])->assertSessionHasNoErrors();
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertSame('cancelled', $unit->fresh()->status);
        } finally { Carbon::setTestNow(); }
    }
    public function test_scheduler_notifies_manual_review_and_end_of_continuation_only_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-02 00:01:00', 'Asia/Jakarta'));
        try {
            $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
            $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-02', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed'];
            $hold = $order->units()->create(array_merge($base, ['delivery_method' => 'delivery', 'delivery_address' => 'Alamat']));
            $late = $order->units()->create($base);
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertTrue($hold->fresh()->payment_review_hold);
            $this->assertSame(1, $admin->notifications()->get()->filter(fn ($n) => $n->data['title'] === 'Peninjauan pembayaran diperlukan')->count());
            $this->assertSame('cancelled', $late->fresh()->status);
            Carbon::setTestNow(Carbon::parse('2026-12-03 12:00:01', 'Asia/Jakarta'));
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->artisan('rental:expire-payments')->assertExitCode(0);
            $this->assertSame(1, $customer->notifications()->get()->filter(fn ($n) => $n->data['title'] === 'Masa kelanjutan berakhir')->count());
        } finally { Carbon::setTestNow(); }
    }
}
