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

/**
 * `php artisan rental:purge-orders` — mengosongkan riwayat pesanan untuk UAT.
 *
 * Aturan yang dijaga:
 *  - yang dihapus hanya data pesanan; katalog (kategori/papan) tetap utuh
 *    sehingga aplikasi langsung bisa dipakai lagi;
 *  - `--dry-run` menghitung tanpa menghapus;
 *  - di produksi `--force` wajib, supaya tidak ada penghapusan tak sengaja;
 *  - `--keep-finance` mempertahankan catatan buku kas dari pembayaran sewa.
 */
class PurgeRentalOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function pesanan(): RentalOrder
    {
        $kategori = RentalCategory::create(['name' => 'Wisuda']);
        $papan = RentalProduct::create([
            'rental_category_id' => $kategori->id,
            'name' => 'Papan Mawar',
            'base_price' => 100000,
            'price_unit' => 'day',
            'stock' => 2,
        ]);

        $customer = User::factory()->create();
        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);

        $order->units()->create([
            'rental_product_id' => $papan->id,
            'product_name' => $papan->name,
            'unit_price' => 100000,
            'price_unit' => 'day',
            'rental_amount' => 100000,
            'starts_on' => '2026-12-01',
            'event_on' => '2026-12-02',
            'ends_on' => '2026-12-03',
            'recipient_name' => 'Penerima',
            'greeting' => 'Selamat',
            'delivery_method' => 'pickup',
            'return_method' => 'dropoff',
            'status' => 'confirmed',
        ]);

        return $order;
    }

    public function test_perintah_terdaftar_dengan_pilihan_aman(): void
    {
        $perintah = (string) file_get_contents(app_path('Console/Commands/PurgeRentalOrders.php'));

        foreach (['--dry-run', '--force', '--keep-finance', '--with-notifications'] as $pilihan) {
            $this->assertStringContainsString($pilihan, $perintah, 'Pilihan hilang: '.$pilihan);
        }

        // Urutan hapus wajib anak dulu: pembayaran sebelum unit, unit sebelum pesanan.
        $this->assertLessThan(
            strpos($perintah, "['rental_orders',"),
            strpos($perintah, "['rental_payments',"),
            'Pembayaran harus dihapus sebelum pesanan.'
        );
        $this->assertLessThan(
            strpos($perintah, "['rental_orders',"),
            strpos($perintah, "['rental_order_units',"),
            'Unit harus dihapus sebelum pesanan.'
        );
        // Perintah ini tidak boleh memanggil migrate:fresh (menghapus katalog
        // dan akun juga). Komentar boleh menyebutnya; panggilannya tidak.
        $this->assertStringNotContainsString("'migrate:fresh'", $perintah);
        $this->assertStringNotContainsString('Artisan::call', $perintah);
    }

    public function test_dry_run_tidak_menghapus_apa_pun(): void
    {
        $this->pesanan();

        $this->artisan('rental:purge-orders', ['--dry-run' => true])
            ->expectsOutputToContain('Mode uji')
            ->assertSuccessful();

        $this->assertDatabaseCount('rental_orders', 1);
        $this->assertDatabaseCount('rental_order_units', 1);
    }

    public function test_purge_menghapus_pesanan_tetapi_katalog_tetap_utuh(): void
    {
        $this->pesanan();
        $this->artisan('rental:purge-orders', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('rental_orders', 0);
        $this->assertDatabaseCount('rental_order_units', 0);
        // Justru ini bedanya dengan migrate:fresh: katalog tidak ikut hilang.
        $this->assertDatabaseCount('rental_products', 1);
        $this->assertDatabaseCount('rental_categories', 1);
    }

    public function test_keep_finance_mempertahankan_catatan_buku_kas(): void
    {
        $this->pesanan();
        FinanceEntry::create([
            'entry_date' => '2026-11-15',
            'period_key' => '2026-11',
            'entry_type' => 'income',
            'category' => 'income',
            'amount' => 100000,
            'source_type' => 'rental_payment',
            'source_id' => 99,
        ]);

        $this->artisan('rental:purge-orders', ['--force' => true, '--keep-finance' => true])->assertSuccessful();
        $this->assertDatabaseCount('finance_entries', 1);

        // Tanpa pilihan itu, catatan kas dari sewa ikut dibersihkan.
        $this->artisan('rental:purge-orders', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('finance_entries', 0);
    }

    public function test_produksi_wajib_force(): void
    {
        $this->pesanan();
        $this->app['env'] = 'production';

        $this->artisan('rental:purge-orders')->assertFailed();
        $this->assertDatabaseCount('rental_orders', 1);

        $this->artisan('rental:purge-orders', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('rental_orders', 0);
    }
}
