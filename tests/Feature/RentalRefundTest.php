<?php

namespace Tests\Feature;

use App\Models\FinanceEntry;
use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_refunds_are_linked_to_verified_payment_and_recorded_once(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $payment = $unit->payments()->create(['amount' => 100, 'proof_path' => 'proof.jpg', 'status' => 'verified', 'submitted_at' => now(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        $url = route('admin.rental.payment.refund', $payment);
        $data = ['amount' => 40, 'reason' => 'Pengembalian sebagian', 'request_key' => (string) Str::uuid(), 'confirmed' => 1];
        $this->actingAs($customer)->post($url, $data)->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->post($url, $data)->assertSessionHasNoErrors();
        $this->post($url, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rental_payment_refunds', 1);
        $this->assertSame(1, $customer->notifications()->get()->filter(fn ($notification) => $notification->data['title'] === 'Pengembalian dana dicatat')->count());
        $this->assertSame(60, $unit->verifiedNet());
        $this->get(route('admin.rental.orders'))->assertOk()->assertSee('Catat pengembalian dana')->assertSee('Pengembalian sebagian');
        $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee('Pengembalian sebagian');
        $this->actingAs($admin);
        $refund = $payment->refunds()->firstOrFail();
        $this->assertDatabaseHas('finance_entries', ['id' => $refund->finance_entry_id, 'entry_type' => 'expense', 'source_type' => 'rental_refund', 'source_id' => $refund->id, 'amount' => 40]);
        $this->post($url, ['amount' => 61, 'reason' => 'Lebih', 'request_key' => (string) Str::uuid(), 'confirmed' => 1])->assertSessionHasErrors('refund');
        $this->post(route('admin.rental.cancel', $unit), ['reason' => 'Batal', 'responsibility' => 'seller'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $unit->fresh()->status);
        $this->get(route('admin.rental.orders'))->assertOk()->assertSee('Kesalahan penjual: kembalikan pembayaran pelanggan');
        $this->post($url, ['amount' => 60, 'reason' => 'Sisa', 'request_key' => (string) Str::uuid(), 'confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $unit->fresh()->status);
        $this->assertSame(100, (int) FinanceEntry::where('source_type', 'rental_refund')->sum('amount'));
    }
    public function test_paid_unit_can_be_cancelled_release_stock_and_pending_proof_reviewed(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $cancelled = $order->units()->create($attrs + ['status' => 'confirmed']);
        $other = $order->units()->create($attrs);
        $proof = $cancelled->payments()->create(['amount' => 100, 'proof_path' => 'proof.jpg', 'submitted_at' => now()]);
        $this->actingAs($admin)->post(route('admin.rental.cancel', $cancelled), ['reason' => 'Kesalahan penjual', 'responsibility' => 'seller'])->assertSessionHasNoErrors();
        $this->assertSame($admin->id, $cancelled->fresh()->cancelled_by);
        $this->post(route('admin.rental.confirm', $other))->assertSessionHasNoErrors();
        $this->post(route('admin.rental.payment.verify', $proof))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_payments', ['id' => $proof->id, 'status' => 'verified']);
        $this->post(route('admin.rental.payment.refund', $proof), ['amount' => 100, 'reason' => 'Dikembalikan', 'request_key' => (string) Str::uuid(), 'confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame(0, $cancelled->fresh()->verifiedNet());
    }
}
