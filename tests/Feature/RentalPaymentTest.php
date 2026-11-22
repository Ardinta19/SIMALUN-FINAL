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

class RentalPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_is_private_and_income_recorded_once_on_verification(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100000, 'price_unit' => 'day', 'rental_amount' => 100000, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $this->actingAs($customer)->post(route('rental.payment.store', $unit), ['amount' => 20000, 'proof' => UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasNoErrors();
        $payment = $unit->payments()->firstOrFail();
        $this->assertDatabaseMissing('finance_entries', ['source_type' => 'rental_payment', 'source_id' => $payment->id]);
        $this->actingAs($other)->get(route('rental.payment.proof', $payment))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.rental.payment.verify', $payment))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('finance_entries', ['source_type' => 'rental_payment', 'source_id' => $payment->id, 'amount' => 20000]);
        $this->post(route('admin.rental.payment.verify', $payment))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('finance_entries', 1);
    }
}
