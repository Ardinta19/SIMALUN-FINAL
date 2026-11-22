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

    public function test_transport_quote_requires_owner_approval_and_is_locked_afterwards(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $owner = User::factory()->create(); $owner->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $owner->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100000, 'price_unit' => 'day', 'rental_amount' => 100000, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $this->actingAs($owner)->post(route('rental.payment.store', $unit), ['amount' => 1000, 'proof' => UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasErrors('payment');
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 20000, 'note' => 'Antar'])->assertSessionHasNoErrors();
        $this->post(route('admin.rental.transport.quote', $unit), ['amount' => 25000, 'note' => 'Antar'])->assertSessionHasNoErrors();
        $this->actingAs($other)->post(route('rental.transport.approve', $unit), ['version' => 2])->assertForbidden();
        $this->actingAs($owner)->post(route('rental.transport.approve', $unit), ['version' => 1])->assertSessionHasErrors('transport');
        $this->post(route('rental.transport.approve', $unit), ['version' => 2])->assertSessionHasNoErrors();
        $this->assertSame(125000, $unit->fresh()->payableAmount());
        $this->actingAs($admin)->post(route('admin.rental.transport.quote', $unit), ['amount' => 1])->assertSessionHasErrors('transport');
        $this->actingAs($owner)->post(route('rental.payment.store', $unit), ['amount' => 125000, 'proof' => UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasNoErrors();
        $payment = $unit->payments()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.rental.payment.verify', $payment))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_payments', ['id' => $payment->id, 'status' => 'verified']);
    }
}
