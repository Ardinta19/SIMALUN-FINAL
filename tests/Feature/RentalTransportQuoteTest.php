<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalTransportQuoteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:User,3:\App\Models\RentalOrderUnit} */
    private function fixture(): array
    {
        Storage::fake('local');
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $owner = User::factory()->create(); $owner->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $owner->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100000, 'price_unit' => 'day', 'rental_amount' => 100000, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat', 'return_method' => 'dropoff', 'status' => 'confirmed']);

        return [$admin, $owner, $other, $unit];
    }

    public function test_transport_quote_requires_owner_approval_and_is_locked_after_payment(): void
    {
        [$admin, $owner, $other, $unit] = $this->fixture();
        $this->actingAs($owner)->post(route('rental.payment.store', $unit), ['amount' => 1000, 'proof' => UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasErrors('payment');
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 20000, 'note' => 'Antar'])->assertSessionHasNoErrors();
        $this->post(route('admin.rental.transport.quote', $unit), ['amount' => 25000, 'note' => 'Antar'])->assertSessionHasNoErrors();
        $this->actingAs($other)->post(route('rental.transport.approve', $unit), ['version' => 2])->assertForbidden();
        $this->actingAs($owner)->post(route('rental.transport.approve', $unit), ['version' => 1])->assertSessionHasErrors('transport');
        $this->post(route('rental.transport.approve', $unit), ['version' => 2])->assertSessionHasNoErrors();
        $this->assertSame(125000, $unit->fresh()->payableAmount());

        // Sejak babak C penawaran masih boleh direvisi selama belum ada pembayaran;
        // persetujuan lama otomatis gugur dan pelanggan menyetujui versi terbaru.
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 20000, 'note' => 'Antar dan jemput'])->assertSessionHasNoErrors();
        $this->assertNull($unit->fresh()->transport_approved_at);
        $this->assertSame(3, $unit->fresh()->transport_quote_version);
        $this->actingAs($owner)->post(route('rental.transport.approve', $unit), ['version' => 3])->assertSessionHasNoErrors();
        $this->assertSame(120000, $unit->fresh()->payableAmount());

        $this->actingAs($owner)->post(route('rental.payment.store', $unit), ['amount' => 120000, 'proof' => UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasNoErrors();
        $payment = $unit->payments()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.rental.payment.verify', $payment))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_payments', ['id' => $payment->id, 'status' => 'verified']);

        // Setelah ada pembayaran, ongkos terkunci.
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 1])->assertSessionHasErrors('transport');
    }

    /**
     * Putaran 35 — ongkos hanya opsi. Penawaran bernilai nol tidak perlu
     * disetujui pelanggan: admin yang menuliskannya sudah menyatakan gratis,
     * dan layar "menunggu persetujuan" untuk biaya nol hanya menahan bayar.
     */
    public function test_zero_quote_is_approved_without_customer_step(): void
    {
        [$admin, $owner, $other, $unit] = $this->fixture();

        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 0, 'note' => 'Gratis, masih dekat kampus'])->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertNotNull($unit->transport_approved_at);
        $this->assertSame(100000, $unit->payableAmount());
        $this->assertSame(1, $unit->transportQuoteEvents()->where('action', 'approve')->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id]);

        // Tidak ada lagi yang bisa disetujui pelanggan untuk versi ini.
        $this->actingAs($owner)->post(route('rental.transport.approve', $unit), ['version' => $unit->transport_quote_version])->assertSessionHasErrors('transport');
        $this->assertSame(100000, $unit->fresh()->payableAmount());
    }
}
