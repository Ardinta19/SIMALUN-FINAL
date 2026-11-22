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
        $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee('Bunga merah')->assertSee('Alamat penerimaan')->assertSee('Alamat pengembalian')->assertSee('Belum lunas')->assertSee('Bukti menunggu');
        $unit->payments()->update(['status' => 'verified']);
        $this->get(route('rental.orders.show', $order))->assertOk()->assertSee('Lunas terverifikasi');
    }
}
