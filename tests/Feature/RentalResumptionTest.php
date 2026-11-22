<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalResumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumption_rechecks_stock_and_keeps_cancellation_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
            $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-04', 'event_on' => '2026-12-04', 'ends_on' => '2026-12-04', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
            $unit = $order->units()->create($attrs + ['status' => 'confirmed']);
            $other = $order->units()->create($attrs);
            $this->actingAs($admin)->post(route('admin.rental.cancel', $unit), ['reason' => 'Perubahan', 'responsibility' => 'other'])->assertSessionHasNoErrors();
            $this->post(route('admin.rental.confirm', $other))->assertSessionHasNoErrors();
            $this->post(route('admin.rental.resume', $unit), ['reason' => 'Lanjut'])->assertSessionHasErrors('unit');
            $this->post(route('admin.rental.cancel', $other), ['reason' => 'Batal', 'responsibility' => 'customer'])->assertSessionHasNoErrors();
            $this->post(route('admin.rental.resume', $unit), ['reason' => 'Lanjut'])->assertSessionHasNoErrors();
            $this->assertSame('confirmed', $unit->fresh()->status);
            $this->assertSame(['cancel', 'resume'], $unit->cancellationEvents()->pluck('action')->all());
            $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee('Perubahan')->assertSee('Lanjut');
            $this->actingAs($admin)->post(route('admin.rental.cancel', $unit), ['reason' => 'Ulang', 'responsibility' => 'other'])->assertSessionHasNoErrors();
            Carbon::setTestNow(Carbon::parse('2026-12-04 13:00:00', 'Asia/Jakarta'));
            $this->post(route('admin.rental.resume', $unit), ['reason' => 'Lanjut'])->assertSessionHasErrors('unit');
            $unit->payments()->create(['amount' => 100, 'proof_path' => 'bukti.jpg', 'status' => 'verified', 'submitted_at' => now(), 'verified_at' => now(), 'verified_by' => $admin->id]);
            $this->post(route('admin.rental.resume', $unit), ['reason' => 'Lanjut lunas'])->assertSessionHasNoErrors();
            $this->post(route('admin.rental.cancel', $unit), ['reason' => 'Ulang lagi', 'responsibility' => 'other'])->assertSessionHasNoErrors();
            Carbon::setTestNow(Carbon::parse('2026-12-05 00:00:00', 'Asia/Jakarta'));
            $this->post(route('admin.rental.resume', $unit), ['reason' => 'Terlambat'])->assertSessionHasErrors('unit');
        } finally { Carbon::setTestNow(); }
    }
}
