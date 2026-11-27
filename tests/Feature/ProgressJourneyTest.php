<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Putaran 35 — progres yang terlihat.
 *
 * Sebelumnya pelanggan hanya mendapat notifikasi "Perkembangan unit telah
 * diperbarui" dan harus membuka tiap unit satu per satu untuk tahu posisinya.
 * Tes ini mengunci tiga hal: notifikasi menyebut langkahnya, stepper tampil di
 * halaman unit/pesanan, dan beranda pelanggan menampilkan progres berjalan.
 */
class ProgressJourneyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:RentalOrder,3:\App\Models\RentalOrderUnit} */
    private function fixture(): array
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan Bunga', 'unit_price' => 100000, 'price_unit' => 'day',
            'rental_amount' => 100000, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3),
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'waiting',
        ]);

        return [$admin, $customer, $order, $unit];
    }

    public function test_notifikasi_progres_menyebut_langkah_dan_arahan_berikutnya(): void
    {
        [$admin, $customer, $order, $unit] = $this->fixture();
        // Langkah menuju produksi mensyaratkan desain disetujui pelanggan.
        $unit->previews()->create(['version' => 1, 'image_path' => 'test.jpg', 'status' => 'approved', 'created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.rental.progress.advance', $unit), ['expected_progress' => 'waiting', 'note' => 'Bahan sudah disiapkan'])->assertSessionHasNoErrors();

        $notification = $customer->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Progres: Dalam pengerjaan', $notification->data['title']);
        $this->assertStringContainsString('Papan sedang dikerjakan', $notification->data['message']);
        $this->assertStringContainsString('Catatan admin: Bahan sudah disiapkan', $notification->data['message']);
    }

    public function test_stepper_tampil_di_halaman_unit_dan_daftar_pesanan(): void
    {
        [$admin, $customer, $order, $unit] = $this->fixture();
        $unit->forceFill(['progress' => 'production'])->save();

        $this->actingAs($customer)->get(route('rental.orders.unit', [$order, $unit]))
            ->assertOk()
            ->assertSee('bk-progress', false)
            ->assertSee('data-state="current"', false)
            ->assertSee('Dalam pengerjaan')
            ->assertSee('Papan sedang dikerjakan');

        // Putaran 42: stepper LENGKAP tinggal di halaman pesanan (detail pesanan
        // dan halaman unit). Sebelumnya halaman ini memakai versi padat supaya
        // kartunya pendek, tetapi justru membuat tahap sulit dibaca.
        $this->actingAs($customer)->get(route('rental.orders.show', $order))
            ->assertOk()
            ->assertSee('bk-progress', false)
            ->assertDontSee('bk-progress--compact', false)
            ->assertSee('Papan sedang dikerjakan');

        // Kartu pesanan: ringkasan progres tanpa harus membuka unit.
        $this->actingAs($customer)->get(route('rental.orders.index'))
            ->assertOk()
            ->assertSee('1 dikerjakan');

        // Beranda pelanggan: unit yang sedang berjalan ikut tampil.
        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Progres pesananmu')
            ->assertSee('bk-tracking-item', false)
            // Tindakan yang menunggu pelanggan tampil tanpa membuka unit.
            ->assertSee('Belum lunas')
            // Beranda tidak lagi menjejalkan lima lingkaran per unit: cukup
            // satu baris tahap, sedangkan stepper lengkap ada di halaman pesanan.
            ->assertSee('bk-stage', false)
            ->assertSee('Tahap 2 dari 5')
            ->assertDontSee('bk-progress__dot', false);
    }

    public function test_unit_dibatalkan_tidak_ditandai_selesai_di_stepper(): void
    {
        [$admin, $customer, $order, $unit] = $this->fixture();
        $unit->forceFill(['status' => 'cancelled', 'progress' => 'production'])->save();

        $response = $this->actingAs($customer)->get(route('rental.orders.unit', [$order, $unit]))->assertOk();
        $response->assertSee('Unit ini dibatalkan; alur pengerjaan berhenti di langkah tersebut.');
        $this->assertSame(0, substr_count((string) $response->getContent(), 'data-state="current"'));
    }
}
