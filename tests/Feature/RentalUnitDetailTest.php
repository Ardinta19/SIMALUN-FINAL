<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalUnitDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_unit_delivery_design_and_payment_status(): void
    {
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100000, 'price_unit' => 'day', 'rental_amount' => 100000, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'design_notes' => 'Bunga merah', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat penerimaan', 'return_method' => 'collection', 'return_address' => 'Alamat pengembalian', 'transport_quote' => 0, 'transport_approved_at' => now(), 'status' => 'confirmed']);
        $unit->payments()->create(['amount' => 100000, 'proof_path' => 'bukti.jpg', 'status' => 'pending', 'submitted_at' => now()]);
        $this->actingAs($customer)->get(route('rental.orders.unit', [$order, $unit]))->assertOk()->assertSee('Bunga merah')->assertSee('Alamat penerimaan')->assertSee('Alamat pengembalian')->assertSee('Belum lunas')->assertSee('Bukti menunggu');
        $unit->payments()->update(['status' => 'verified']);
        $this->get(route('rental.orders.unit', [$order, $unit]))->assertOk()->assertSee('Lunas terverifikasi');
    }
    public function test_customer_is_guided_through_unit_specific_next_steps(): void
    {
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat', 'return_method' => 'dropoff']);
        $url = route('rental.orders.unit', [$order, $unit]);
        $this->actingAs($customer)->get($url)->assertOk()->assertSee('Menunggu konfirmasi admin. Stok belum dicadangkan.')->assertDontSee('Bayar melalui QRIS');
        $unit->update(['status' => 'confirmed', 'transport_quote' => 20]);
        $this->get($url)->assertOk()->assertSee('Penawaran ongkos siap diperiksa dan disetujui.')->assertSee('Menunggu preview desain dari admin.')->assertDontSee('Bayar melalui QRIS');
        $unit->update(['transport_approved_at' => now()]);
        $preview = $unit->previews()->create(['version' => 1, 'image_path' => 'preview.png', 'created_by' => $admin->id, 'status' => 'pending']);
        $this->get($url)->assertOk()->assertSee('Preview desain siap diperiksa')->assertSee('Bayar melalui QRIS')->assertSee('Lihat pembayaran');
        $preview->update(['status' => 'revision']);
        $unit->payments()->create(['amount' => 120, 'proof_path' => 'proof.jpg', 'submitted_at' => now()]);
        $this->get($url)->assertOk()->assertSee('Permintaan revisi sudah dikirim')->assertSee('Ada bukti pembayaran menunggu pemeriksaan admin.')->assertDontSee('Bayar melalui QRIS');
        $unit->update(['status' => 'cancelled', 'cancel_reason' => 'Dibatalkan admin']);
        $this->get($url)->assertOk()->assertSee('Unit ini dibatalkan.')->assertDontSee('Preview desain siap diperiksa');
    }
    public function test_detail_pages_enforce_order_ownership_and_unit_membership(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $otherOrder = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $other->id, 'confirmation_deadline' => now()->addDay()]);
        $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $unit = $order->units()->create($attrs);
        $otherUnit = $otherOrder->units()->create($attrs);
        $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee(route('rental.orders.unit', [$order, $unit]));
        $this->get(route('rental.orders.unit', [$order, $otherUnit]))->assertNotFound();
        $this->get(route('rental.orders.unit', [$otherOrder, $otherUnit]))->assertForbidden();
        $this->get(route('admin.rental.orders.show', $order))->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->get(route('admin.rental.orders.show', $order))->assertOk()->assertSee('Konfirmasi dan cadangkan');
    }

}
