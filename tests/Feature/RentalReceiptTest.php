<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_is_private_and_shows_unverified_evidence_separately(): void
    {
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $unit->payments()->create(['amount' => 50, 'proof_path' => 'proof.jpg', 'submitted_at' => now()]);
        $url = route('rental.receipt', $order);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($other)->get($url)->assertForbidden();
        $response = $this->actingAs($customer)->get($url)->assertOk()->assertSee('Penerima')->assertSee('Bukti menunggu verifikasi:')->assertSee('Rp 50');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->actingAs($admin)->get($url)->assertOk()->assertSee($order->reference);
    }
}
