<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Voucher alur sewa: admin membuat kode, pelanggan memakainya di checkout.
 * Potongan disimpan per unit agar tagihan, pembayaran, dan pengingat yang
 * sudah dihitung per unit tetap konsisten.
 */
class RentalVoucherTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $price = 100000, int $stock = 3): RentalProduct
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);

        return RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => 'Papan Bunga',
            'base_price' => $price,
            'price_unit' => 'day',
            'stock' => $stock,
        ]);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    /**
     * @param  array<string, array<string, mixed>>  $cart
     * @return array<string, array<string, mixed>>
     */
    private function checkoutPayload(array $cart, array $overrides = []): array
    {
        $payload = [];
        $start = now('Asia/Jakarta')->addDays(3)->toDateString();
        $end = now('Asia/Jakarta')->addDays(4)->toDateString();

        foreach (array_keys($cart) as $key) {
            $payload[$key] = array_merge([
                'starts_on' => $start,
                'event_on' => $start,
                'ends_on' => $end,
                'recipient_name' => 'Penerima',
                'greeting' => 'Selamat',
                'delivery_method' => 'pickup',
                'return_method' => 'dropoff',
            ], $overrides);
        }

        return $payload;
    }

    private function voucher(array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'code' => 'WISUDA10',
            'description' => 'Potongan uji',
            'type' => 'percent',
            'value' => 10,
            'min_order' => 0,
            'max_discount' => null,
            'usage_limit' => 10,
            'valid_from' => now('Asia/Jakarta')->toDateString(),
            'valid_until' => now('Asia/Jakarta')->addMonths(2)->toDateString(),
            'is_active' => true,
            'scope' => Voucher::SCOPE_RENTAL,
            'per_customer_limit' => 1,
        ], $attributes));
    }

    public function test_voucher_persen_memotong_tagihan_dan_mencatat_pemakaian(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $voucher = $this->voucher();

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $payload = $this->checkoutPayload((array) session('rental_cart'));

        $this->post(route('rental.checkout.store'), [
            'units' => $payload,
            'terms' => '1',
            'voucher_code' => 'wisuda10',
        ])->assertSessionHasNoErrors();

        $order = RentalOrder::with('units')->firstOrFail();
        $unit = $order->units()->firstOrFail();

        $this->assertSame(200000, (int) $unit->rental_amount);
        $this->assertSame(20000, (int) $unit->discount_amount);
        $this->assertSame(20000, (int) $order->discount_amount);
        $this->assertSame('WISUDA10', $order->voucher_code);
        $this->assertSame($voucher->id, (int) $order->voucher_id);
        $this->assertSame(180000, $unit->fresh()->payableAmount());
        $this->assertSame(1, (int) $voucher->fresh()->used_count);
        $this->assertDatabaseHas('rental_voucher_usages', [
            'voucher_id' => $voucher->id,
            'rental_order_id' => $order->id,
            'customer_id' => $customer->id,
            'discount_amount' => 20000,
        ]);
    }

    public function test_checkout_tanpa_voucher_tetap_menghasilkan_tagihan_penuh(): void
    {
        $product = $this->product();
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $payload = $this->checkoutPayload((array) session('rental_cart'));

        $this->post(route('rental.checkout.store'), ['units' => $payload, 'terms' => '1'])->assertSessionHasNoErrors();

        $order = RentalOrder::with('units')->firstOrFail();
        $unit = $order->units()->firstOrFail();

        $this->assertNull($order->voucher_code);
        $this->assertSame(0, (int) $order->discount_amount);
        $this->assertSame(0, (int) $unit->discount_amount);
        $this->assertSame(200000, $unit->fresh()->payableAmount());
    }

    public function test_minimum_belanja_ditolak_dan_keranjang_tidak_hilang(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->voucher(['code' => 'BESAR', 'type' => 'fixed', 'value' => 50000, 'min_order' => 500000]);

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $payload = $this->checkoutPayload((array) session('rental_cart'));

        $this->post(route('rental.checkout.store'), ['units' => $payload, 'terms' => '1', 'voucher_code' => 'BESAR'])
            ->assertSessionHasErrors('voucher_code');

        $this->assertDatabaseCount('rental_orders', 0);
        $this->assertNotEmpty(session('rental_cart'), 'Keranjang harus tetap ada agar pelanggan bisa mencoba kode lain.');
    }

    public function test_voucher_hanya_bisa_dipakai_sekali_per_pelanggan(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->voucher();

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'units' => $this->checkoutPayload((array) session('rental_cart')),
            'terms' => '1',
            'voucher_code' => 'WISUDA10',
        ])->assertSessionHasNoErrors();

        $this->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'units' => $this->checkoutPayload((array) session('rental_cart')),
            'terms' => '1',
            'voucher_code' => 'WISUDA10',
        ])->assertSessionHasErrors('voucher_code');

        $this->assertStringContainsString('sudah memakai voucher', (string) session('errors')?->first('voucher_code'));
        $this->assertSame(1, RentalOrder::count());
    }

    public function test_kuota_total_habis_ditolak(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->voucher(['usage_limit' => 1, 'used_count' => 1]);

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'units' => $this->checkoutPayload((array) session('rental_cart')),
            'terms' => '1',
            'voucher_code' => 'WISUDA10',
        ])->assertSessionHasErrors('voucher_code');

        $this->assertSame(0, RentalOrder::count());
    }

    public function test_voucher_tidak_aktif_dan_kedaluwarsa_ditolak(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->voucher(['code' => 'MATI', 'is_active' => false]);
        $this->voucher(['code' => 'LAMA', 'valid_until' => now('Asia/Jakarta')->subDay()->toDateString()]);

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $payload = $this->checkoutPayload((array) session('rental_cart'));

        $this->post(route('rental.checkout.store'), ['units' => $payload, 'terms' => '1', 'voucher_code' => 'MATI'])
            ->assertSessionHasErrors('voucher_code');
        $this->post(route('rental.checkout.store'), ['units' => $payload, 'terms' => '1', 'voucher_code' => 'LAMA'])
            ->assertSessionHasErrors('voucher_code');

        $this->assertSame(0, RentalOrder::count());
    }

    public function test_pemeriksaan_kode_di_checkout_mengembalikan_perkiraan_potongan(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->voucher();

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);

        // Tanpa isian tanggal, keranjang dihitung satu hari: 10% dari 100.000.
        $this->postJson(route('rental.voucher.check'), ['code' => 'wisuda10'])
            ->assertOk()
            ->assertJson(['ok' => true, 'code' => 'WISUDA10', 'discount' => 10000]);

        // Formulir checkout mengirim perkiraan harga dari tanggal/paket yang
        // sedang diisi; dua hari berarti 200.000 dan potongannya 20.000.
        $this->postJson(route('rental.voucher.check'), ['code' => 'WISUDA10', 'base' => 200000])
            ->assertOk()
            ->assertJson(['ok' => true, 'code' => 'WISUDA10', 'discount' => 20000]);

        $this->postJson(route('rental.voucher.check'), ['code' => 'TIDAKADA'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_voucher_scope_ongkos_menghitung_ulang_setelah_ongkos_disetujui(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $admin = $this->admin();
        $this->voucher(['code' => 'SEWAONGKOS', 'scope' => Voucher::SCOPE_RENTAL_TRANSPORT]);

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $payload = $this->checkoutPayload((array) session('rental_cart'), [
            'delivery_method' => 'delivery',
            'delivery_address' => 'Jl. Uji No. 1',
        ]);

        $this->post(route('rental.checkout.store'), [
            'units' => $payload,
            'terms' => '1',
            'voucher_code' => 'SEWAONGKOS',
        ])->assertSessionHasNoErrors();

        $order = RentalOrder::with('units')->firstOrFail();
        $unit = $order->units()->firstOrFail();

        // Ongkos belum ada: potongan baru dihitung dari harga sewa.
        $this->assertSame(20000, (int) $unit->discount_amount);
        $this->assertNull($unit->fresh()->payableAmount());

        $this->actingAs($admin)->post(route('admin.rental.confirm', $unit))->assertSessionHasNoErrors();
        $this->post(route('admin.rental.transport.quote', $unit), ['amount' => 50000])->assertSessionHasNoErrors();
        $this->actingAs($customer)->post(route('rental.transport.approve', $unit), ['version' => 1])->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame(25000, (int) $order->fresh()->discount_amount);
        $this->assertSame(25000, $unit->totalDiscount());
        $this->assertSame(225000, $unit->payableAmount());
    }

    public function test_admin_membuat_dan_menonaktifkan_voucher(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.rental.vouchers.store'), [
            'code' => 'akhir-tahun',
            'description' => 'Potongan akhir tahun',
            'type' => 'fixed',
            'scope' => Voucher::SCOPE_RENTAL,
            'value' => 25000,
            'min_order' => 100000,
            'usage_limit' => 20,
            'per_customer_limit' => 1,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $voucher = Voucher::firstOrFail();
        $this->assertSame('AKHIR-TAHUN', $voucher->code);
        $this->assertSame(25000, (int) $voucher->value);

        $this->get(route('admin.rental.vouchers.index'))->assertOk()->assertSee('AKHIR-TAHUN');

        $this->patch(route('admin.rental.vouchers.toggle', $voucher))->assertSessionHasNoErrors();
        $this->assertFalse($voucher->fresh()->is_active);
    }
}
