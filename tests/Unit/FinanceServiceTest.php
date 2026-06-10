<?php

namespace Tests\Unit;

use App\Models\FinanceEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit test untuk FinanceService (memanggil service langsung, bukan
 * lewat HTTP). Fokus pada perhitungan amount dari komponen — cabang
 * yang belum tercakup feature test: item lines & diskon.
 */
class FinanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_termasuk_item_lines_dan_dikurangi_diskon(): void
    {
        [$order, $itemService] = $this->buatOrder(serviceCost: 14000, pickup: 5000, discount: 2000);

        // Item dengan service BERBEDA dari layanan utama → ikut dihitung.
        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $itemService->id,
            'item_description' => 'Bedcover',
            'qty' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $itemService->id,
            'item_description' => 'Jas',
            'qty' => 1,
            'unit_price' => 5000,
            'line_total' => 5000,
        ]);

        FinanceService::recordIncomeFromOrder($order->fresh());

        $entry = FinanceEntry::where('order_id', $order->id)->first();
        $this->assertNotNull($entry);
        // 14.000 (service) + 15.000 (items) + 5.000 (pickup) - 2.000 (diskon) = 32.000
        $this->assertSame(32000, (int) $entry->amount);
    }

    public function test_item_dengan_service_sama_seperti_layanan_utama_dikecualikan(): void
    {
        [$order] = $this->buatOrder(serviceCost: 14000, pickup: 5000, discount: 0);

        // Item ber-service_id = layanan utama → DIKECUALIKAN dari itemTotal
        // (mencegah dobel hitung dengan service_cost).
        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $order->service_id,
            'item_description' => 'Kiloan utama',
            'qty' => 2,
            'unit_price' => 7000,
            'line_total' => 14000,
        ]);

        FinanceService::recordIncomeFromOrder($order->fresh());

        $entry = FinanceEntry::where('order_id', $order->id)->first();
        // Item utama dikecualikan → 14.000 + 0 + 5.000 - 0 = 19.000
        $this->assertSame(19000, (int) $entry->amount);
    }

    public function test_idempotent_saat_dipanggil_berulang_langsung_di_service(): void
    {
        [$order] = $this->buatOrder(serviceCost: 14000, pickup: 5000, discount: 0);

        FinanceService::recordIncomeFromOrder($order);
        FinanceService::recordIncomeFromOrder($order);
        FinanceService::recordIncomeFromOrder($order);

        $this->assertSame(1, FinanceEntry::where('order_id', $order->id)->count());
    }

    public function test_amount_dari_service_cost_dan_pickup_saja_tanpa_item(): void
    {
        [$order] = $this->buatOrder(serviceCost: 8000, pickup: 0, discount: 0);

        FinanceService::recordIncomeFromOrder($order);

        $entry = FinanceEntry::where('order_id', $order->id)->first();
        $this->assertSame(8000, (int) $entry->amount);
    }

    /**
     * @return array{0: Order, 1: Service}
     */
    private function buatOrder(int $serviceCost, int $pickup, int $discount): array
    {
        $mainService = Service::create([
            'name' => 'Kiloan Unit '.uniqid(),
            'slug' => 'kiloan-unit-'.uniqid(),
            'pricing_model' => 'per_kg',
            'unit_price' => 7000,
            'unit_type' => 'kg',
            'price_per_kg' => 7000,
            'estimated_hours' => 24,
            'description' => 'Unit test',
            'is_active' => true,
        ]);

        $itemService = Service::create([
            'name' => 'Satuan Unit '.uniqid(),
            'slug' => 'satuan-unit-'.uniqid(),
            'pricing_model' => 'per_item',
            'unit_price' => 10000,
            'unit_type' => 'item',
            'price_per_kg' => 10000,
            'estimated_hours' => 24,
            'description' => 'Unit test item',
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-UNIT-'.uniqid(),
            'customer_id' => User::factory()->create(['role' => 'customer'])->id,
            'service_id' => $mainService->id,
            'address' => 'Jl. Unit Test No. 1',
            'address_note' => null,
            'zone' => 'A',
            'pickup_cost' => $pickup,
            'pickup_date' => now()->toDateString(),
            'pickup_time' => 'siang',
            'weight_estimate' => 2,
            'weight_actual' => 2,
            'service_cost' => $serviceCost,
            'discount' => $discount,
            'total_cost' => $serviceCost + $pickup - $discount,
            'status' => 'selesai',
            'payment_method' => 'cod',
            'is_paid' => true,
            'paid_at' => now(),
        ]);

        return [$order, $itemService];
    }
}
