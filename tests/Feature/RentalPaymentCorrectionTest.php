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

class RentalPaymentCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_amount_correction_updates_linked_cash_entry_and_preserves_history(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $payment = $unit->payments()->create(['amount' => 50, 'proof_path' => 'proof.jpg', 'submitted_at' => now()]);
        $this->actingAs($admin)->post(route('admin.rental.payment.verify', $payment))->assertSessionHasNoErrors();
        $entry = FinanceEntry::findOrFail($payment->fresh()->finance_entry_id); $date = $entry->entry_date->toDateString();
        $url = route('admin.rental.payment.correct', $payment); $data = ['expected_amount' => 50, 'amount' => 70, 'reason' => 'Koreksi nominal'];
        $this->actingAs($customer)->post($url, $data)->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->post($url, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_payments', ['id' => $payment->id, 'amount' => 70]);
        $this->assertDatabaseHas('finance_entries', ['id' => $entry->id, 'amount' => 70, 'entry_date' => $date]);
        $this->assertDatabaseHas('rental_payment_corrections', ['rental_payment_id' => $payment->id, 'old_amount' => 50, 'new_amount' => 70, 'reason' => 'Koreksi nominal']);
        $this->post($url, $data)->assertSessionHasErrors('payment');
        $this->post($url, ['expected_amount' => 70, 'amount' => 101, 'reason' => 'Terlalu besar'])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('finance_entries', 1);
        $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee('Koreksi nominal');
    }
}
