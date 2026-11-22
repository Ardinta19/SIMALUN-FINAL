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

class RentalConfirmationExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_cancels_only_pending_units_at_or_after_deadline_and_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $customer = User::factory()->create();
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
            $expired = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()]);
            $future = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addSecond()]);
            $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-04', 'event_on' => '2026-12-04', 'ends_on' => '2026-12-04', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
            $pending = $expired->units()->create($attrs);
            $confirmed = $expired->units()->create(array_merge($attrs, ['status' => 'confirmed']));
            $notDue = $future->units()->create($attrs);
            $this->artisan('rental:expire-confirmations')->assertExitCode(0);
            $this->assertSame('cancelled', $pending->fresh()->status);
            $this->assertSame('confirmed', $confirmed->fresh()->status);
            $this->assertSame('pending', $notDue->fresh()->status);
            $this->artisan('rental:expire-confirmations')->expectsOutput('Unit dibatalkan: 0')->assertExitCode(0);
        } finally { Carbon::setTestNow(); }
    }

    public function test_close_schedule_needs_explicit_special_confirmation_and_expired_unit_cannot_be_confirmed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
            $customer = User::factory()->create();
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-02', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
            $unit = $order->units()->create($attrs);
            $this->actingAs($admin)->post(route('admin.rental.confirm', $unit))->assertSessionHasErrors('unit');
            $this->post(route('admin.rental.confirm', $unit), ['special_confirmation' => 1])->assertSessionHasNoErrors();
            $this->assertTrue($unit->fresh()->special_confirmation);
            $late = $order->units()->create($attrs);
            Carbon::setTestNow(Carbon::parse('2026-12-02 10:00:00', 'Asia/Jakarta'));
            $this->post(route('admin.rental.confirm', $late), ['special_confirmation' => 1])->assertSessionHasErrors('unit');
            $this->artisan('rental:expire-confirmations')->assertExitCode(0);
            $this->assertSame('cancelled', $late->fresh()->status);
        } finally { Carbon::setTestNow(); }
    }
}
