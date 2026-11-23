<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use App\Services\RentalAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kendali pelanggan pada unit: menolak ongkos, membatalkan sendiri, memperbaiki
 * data, dan memesan ulang dari unit yang dibatalkan.
 */
class RentalCustomerControlsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:RentalProduct,3:RentalOrderUnit} */
    private function context(string $delivery = 'pickup', int $stock = 2): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => $stock]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => '2026-12-10', 'event_on' => '2026-12-10', 'ends_on' => '2026-12-11',
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => $delivery,
            'delivery_address' => $delivery === 'delivery' ? 'Alamat awal' : null,
            'return_method' => 'dropoff', 'status' => 'confirmed', 'progress' => 'waiting',
        ]);

        return [$admin, $customer, $product, $unit];
    }

    public function test_transport_can_be_rejected_and_revised_until_a_payment_exists(): void
    {
        [$admin, $customer, , $unit] = $this->context('delivery');

        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 50, 'note' => 'Antar pagi'])->assertSessionHasNoErrors();
        $this->assertSame(1, $unit->fresh()->transport_quote_version);

        // Penolakan wajib beralasan dan mengosongkan penawaran.
        $this->actingAs($customer)->post(route('rental.transport.reject', $unit), ['version' => 1, 'note' => ''])->assertSessionHasErrors('note');
        $this->actingAs($customer)->post(route('rental.transport.reject', $unit), ['version' => 1, 'note' => 'Terlalu mahal'])->assertSessionHasNoErrors();
        $this->assertNull($unit->fresh()->transport_quote);
        $this->assertNull($unit->fresh()->payableAmount());

        // Versi lama tidak dapat disetujui setelah ada penawaran baru.
        $this->actingAs($customer)->post(route('rental.transport.approve', $unit), ['version' => 1])->assertSessionHasErrors('transport');
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 75, 'note' => 'Antar pagi dan jemput'])->assertSessionHasNoErrors();
        $this->assertSame(2, $unit->fresh()->transport_quote_version);
        $this->actingAs($customer)->post(route('rental.transport.approve', $unit), ['version' => 1])->assertSessionHasErrors('transport');
        $this->actingAs($customer)->post(route('rental.transport.approve', $unit), ['version' => 2])->assertSessionHasNoErrors();
        $this->assertSame(175, $unit->fresh()->payableAmount());
        $this->assertSame(['quote', 'reject', 'quote', 'approve'], $unit->transportQuoteEvents()->pluck('action')->all());

        // Setelah ada pembayaran, ongkos terkunci.
        $unit->payments()->create(['amount' => 10, 'proof_path' => 'bukti.jpg', 'submitted_at' => now()]);
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 80])->assertSessionHasErrors('transport');
    }

    public function test_customer_can_cancel_own_unit_before_production(): void
    {
        [$admin, $customer, $product, $unit] = $this->context();
        $other = User::factory()->create(['role' => 'customer']);

        // Wajib konfirmasi tertulis dan alasannya.
        $this->actingAs($customer)->post(route('rental.cancel.customer', $unit), ['reason' => 'Salah tanggal'])->assertSessionHasErrors('confirmed');
        // Pelanggan lain tidak boleh membatalkan.
        $this->actingAs($other)->post(route('rental.cancel.customer', $unit), ['reason' => 'Iseng', 'confirmed' => 1])->assertForbidden();

        $this->actingAs($customer)->post(route('rental.cancel.customer', $unit), ['reason' => 'Salah tanggal', 'confirmed' => 1])->assertSessionHasNoErrors();
        $fresh = $unit->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('customer_request', $fresh->cancel_code);
        $this->assertSame('customer', $fresh->cancel_responsibility);
        $this->assertSame(['cancel'], $fresh->cancellationEvents()->pluck('action')->all());
        $this->assertSame($customer->id, $fresh->cancellationEvents()->first()->recorded_by);

        // Tanggal dilepas kembali ke stok.
        $this->assertSame(2, app(RentalAvailability::class)->minimumAvailable($product, '2026-12-10', '2026-12-11'));

        // Unit yang sudah dikerjakan tidak dapat dibatalkan sendiri.
        $fresh->forceFill(['status' => 'confirmed', 'progress' => 'production', 'status_before_cancel' => null, 'cancel_code' => null])->save();
        $this->actingAs($customer)->post(route('rental.cancel.customer', $fresh), ['reason' => 'Menyesal', 'confirmed' => 1])->assertSessionHasErrors('cancel');
    }

    public function test_customer_can_fix_unit_details_until_payment_or_production(): void
    {
        [$admin, $customer, , $unit] = $this->context();
        $payload = ['recipient_name' => 'Ibu Baru', 'greeting' => 'Selamat wisuda', 'design_notes' => 'Warna lembut', 'return_address' => ''];

        $this->actingAs($customer)->post(route('rental.unit.update', $unit), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Ibu Baru', $unit->fresh()->recipient_name);
        $this->assertStringContainsString('nama penerima', $unit->progressEvents()->get()->last()->note);

        // Desain disetujui: nama/ucapan/permintaan desain terkunci, alamat masih boleh.
        $unit->previews()->create(['version' => 1, 'image_path' => 'desain.jpg', 'status' => 'approved', 'created_by' => $admin->id, 'responded_at' => now()]);
        $this->actingAs($customer)->post(route('rental.unit.update', $unit), ['recipient_name' => 'Ibu Lain', 'greeting' => 'Selamat wisuda', 'design_notes' => 'Warna lembut', 'return_address' => ''])->assertSessionHasErrors('unit');
        $this->actingAs($customer)->post(route('rental.unit.update', $unit), ['recipient_name' => 'Ibu Baru', 'greeting' => 'Selamat wisuda', 'design_notes' => 'Warna lembut', 'delivery_address' => 'Alamat baru', 'return_address' => ''])->assertSessionHasNoErrors();

        // Setelah pembayaran terverifikasi, data unit terkunci.
        $unit->payments()->create(['amount' => 100, 'proof_path' => 'bukti.jpg', 'status' => 'verified', 'submitted_at' => now(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        $this->actingAs($customer)->post(route('rental.unit.update', $unit), $payload)->assertSessionHasErrors('unit');
    }

    public function test_cancelled_unit_can_be_reordered_into_the_cart(): void
    {
        [, $customer, , $unit] = $this->context();
        $unit->forceFill(['status' => 'cancelled', 'status_before_cancel' => 'confirmed', 'cancel_code' => 'customer_request', 'cancel_reason' => 'Batal'])->save();

        $this->actingAs($customer)->post(route('rental.unit.reorder', $unit))->assertRedirect(route('rental.checkout'));
        $cart = session('rental_cart');
        $this->assertCount(1, $cart);
        $entry = array_values($cart)[0];
        $this->assertSame($unit->rental_product_id, $entry['product_id']);
        $this->assertSame('Penerima', $entry['recipient_name']);
        $this->assertSame('Selamat', $entry['greeting']);
        $this->assertArrayNotHasKey('starts_on', $entry);

        // Isian unit dipakai sebagai nilai awal checkout.
        $this->get(route('rental.checkout'))->assertOk()->assertSee('Penerima', false);

        // Unit aktif tidak bisa dipesan ulang lewat jalur ini.
        $unit->forceFill(['status' => 'confirmed'])->save();
        $this->actingAs($customer)->post(route('rental.unit.reorder', $unit))->assertSessionHasErrors('reorder');
    }
}
