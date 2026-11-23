<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Services\RentalAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_estimate_uses_peak_daily_reservations_without_reserving_stock(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Jakarta'));
        try {
            $category = RentalCategory::create(['name' => 'Wisuda']);
            $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
            $customer = User::factory()->create();
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
            $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
            $order->units()->create($base + ['starts_on' => '2026-12-03', 'event_on' => '2026-12-03', 'ends_on' => '2026-12-04', 'status' => 'confirmed']);
            $order->units()->create($base + ['starts_on' => '2026-12-05', 'event_on' => '2026-12-05', 'ends_on' => '2026-12-06', 'status' => 'confirmed']);
            $order->units()->create($base + ['starts_on' => '2026-12-04', 'event_on' => '2026-12-04', 'ends_on' => '2026-12-05', 'status' => 'cancelled']);
            $this->assertSame(1, app(RentalAvailability::class)->minimumAvailable($product, '2026-12-03', '2026-12-06'));
            $this->get(route('catalog.show', [$product, 'starts_on' => '2026-12-03', 'ends_on' => '2026-12-06']))->assertOk()->assertSee('1 unit')->assertSee('belum menahan tanggal');
            $this->assertDatabaseCount('rental_order_units', 3);
            $this->get(route('catalog.show', [$product, 'starts_on' => '2026-12-06', 'ends_on' => '2026-12-03']))->assertSessionHasErrors('ends_on');
        } finally { Carbon::setTestNow(); }
    }
}
