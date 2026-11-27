<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_shows_booked_days_and_pending_without_cancelled_units(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Papan Mawar', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-11-30', 'event_on' => '2026-12-01', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff'];
        $order->units()->create($base + ['status' => 'confirmed']);
        $order->units()->create(array_merge($base, ['product_name' => 'Papan Menunggu', 'status' => 'pending']));
        $order->units()->create(array_merge($base, ['product_name' => 'Papan Dibatalkan', 'status' => 'cancelled']));
        $url = route('admin.rental.schedule', ['month' => '2026-12']);
        $this->actingAs($customer)->get($url)->assertRedirect(route('customer.dashboard'));
        // Putaran 45: satu label keadaan per unit (bukan status + progres yang
        // bisa bertentangan), dan kegiatan hari itu berlabel tegas.
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Papan Mawar')
            ->assertSee('Antre pengerjaan')      // confirmed, progres masih waiting
            ->assertSee('Pengembalian')
            ->assertSee('Papan Menunggu')->assertSee('Menunggu konfirmasi')
            ->assertDontSee('belum dicadangkan')
            ->assertDontSee('Acara hari ini')     // baris yang mengulang tanggal
            ->assertDontSee('Papan Dibatalkan');
    }
    public function test_activity_and_status_filters_show_the_correct_day_and_handoff_details(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Mawar unik', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-01', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-03', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat antar', 'return_method' => 'collection', 'return_address' => 'Alamat jemput'];
        $order->units()->create($base + ['status' => 'confirmed']);
        $order->units()->create(array_merge($base, ['product_name' => 'Menunggu unik', 'status' => 'pending']));
        $url = route('admin.rental.schedule');
        $response = $this->actingAs($admin)->get($url.'?month=2026-12&activity=receiving&status=confirmed')->assertOk()->assertSee('Alamat antar')->assertDontSee('Alamat jemput')->assertDontSee('Menunggu unik');
        $this->assertSame(1, substr_count($response->getContent(), 'Mawar unik'));
        $this->get($url.'?month=2026-12&activity=returning')->assertOk()->assertSee('Alamat jemput')->assertDontSee('Alamat antar');
        $this->from($url)->get($url.'?activity=invalid')->assertRedirect($url)->assertSessionHasErrors('activity');
    }
    public function test_daily_sheet_is_admin_only_and_lists_only_confirmed_handoffs_for_the_date(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $base = ['rental_product_id' => $product->id, 'product_name' => 'Mawar terkonfirmasi', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-01', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-03', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat antar', 'return_method' => 'collection', 'return_address' => 'Alamat jemput'];
        $order->units()->create($base + ['status' => 'confirmed']);
        $order->units()->create(array_merge($base, ['product_name' => 'Belum dikonfirmasi', 'status' => 'pending']));
        $url = route('admin.rental.schedule.daily-sheet', ['date' => '2026-12-01']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->get($url)->assertRedirect(route('customer.dashboard'));
        $response = $this->actingAs($admin)->get($url)->assertOk()->assertSee('Mawar terkonfirmasi')->assertSee('Alamat antar')->assertDontSee('Alamat jemput')->assertDontSee('Belum dikonfirmasi');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('admin.rental.schedule.daily-sheet', ['date' => '2026-12-03']))->assertOk()->assertSee('Alamat jemput')->assertDontSee('Alamat antar');
        $this->get(route('admin.rental.schedule.daily-sheet', ['date' => '2026-12-02']))->assertOk()->assertDontSee('Mawar terkonfirmasi');
        $this->from(route('admin.rental.schedule'))->get(route('admin.rental.schedule.daily-sheet', ['date' => 'invalid']))->assertSessionHasErrors('date');
    }
}
