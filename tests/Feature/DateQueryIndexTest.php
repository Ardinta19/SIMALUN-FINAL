<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Saringan tanggal memakai perbandingan biasa (`where('ends_on', '<', ...)`)
 * alih-alih `whereDate()`. Pada MySQL, `whereDate()` membungkus kolom menjadi
 * `date(ends_on) <= ?`, sehingga indeks kolom itu tidak terpakai — padahal
 * dasbor admin dan penjadwal menyaring tabel yang terus bertambah.
 *
 * Perbandingan biasa setara karena ketiga kolom itu bertipe DATE dan nilai
 * pembandingnya selalu "Y-m-d" (hasil `toDateString()` atau input
 * `date_format:Y-m-d`). Tes ini menjaga kesetaraan itu sekaligus mencegah
 * `whereDate()` kembali dipakai pada kolom DATE.
 */
class DateQueryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_tanggal_tidak_disaring_dengan_where_date(): void
    {
        $masalah = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            $isi = File::get($berkas->getPathname());

            foreach (['starts_on', 'event_on', 'ends_on', 'entry_date'] as $kolom) {
                if (preg_match("/whereDate\\('".$kolom."'/", $isi)) {
                    $masalah[] = str_replace(base_path().'/', '', $berkas->getPathname()).' → '.$kolom;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($masalah)), 'Kolom DATE masih disaring lewat whereDate(): '.implode(', ', $masalah));
    }

    /** @return array{0: User, 1: RentalProduct} */
    private function context(): array
    {
        $customer = User::factory()->create(['role' => 'customer', 'phone' => '81234567890']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => 'Papan',
            'base_price' => 100000,
            'price_unit' => 'day',
            'stock' => 5,
            'is_active' => true,
        ]);

        return [$customer, $product];
    }

    public function test_perbandingan_biasa_setara_dengan_where_date(): void
    {
        [$customer, $product] = $this->context();

        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);

        foreach (['2026-12-09', '2026-12-10', '2026-12-11'] as $tanggal) {
            $order->units()->create([
                'rental_product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => 100000,
                'price_unit' => 'day',
                'rental_amount' => 1,
                'starts_on' => $tanggal,
                'event_on' => $tanggal,
                'ends_on' => $tanggal,
                'recipient_name' => 'Penerima',
                'greeting' => 'Selamat',
                'delivery_method' => 'pickup',
                'return_method' => 'dropoff',
                'status' => 'pending',
                'progress' => 'waiting',
            ]);
        }

        foreach (['<', '<=', '=', '>=', '>'] as $operator) {
            foreach (['starts_on', 'event_on', 'ends_on'] as $kolom) {
                $biasa = RentalOrderUnit::where($kolom, $operator, '2026-12-10')->orderBy('id')->pluck('id')->all();
                $whereDate = RentalOrderUnit::whereDate($kolom, $operator, '2026-12-10')->orderBy('id')->pluck('id')->all();

                $this->assertSame($whereDate, $biasa, "Hasil berbeda untuk {$kolom} {$operator} 2026-12-10.");
            }
        }
    }

    public function test_hasil_pencarian_tanggal_pada_daftar_pesanan_admin(): void
    {
        [$customer, $product] = $this->context();
        $admin = User::factory()->create(['role' => 'admin']);

        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);

        $order->units()->create([
            'rental_product_id' => $product->id,
            'product_name' => 'Papan Merah',
            'unit_price' => 100000,
            'price_unit' => 'day',
            'rental_amount' => 1,
            'starts_on' => '2026-12-10',
            'event_on' => '2026-12-10',
            'ends_on' => '2026-12-11',
            'recipient_name' => 'Penerima',
            'greeting' => 'Selamat',
            'delivery_method' => 'pickup',
            'return_method' => 'dropoff',
            'status' => 'pending',
            'progress' => 'waiting',
        ]);

        // Pesanan pembanding di luar rentang: kalau saringan diabaikan, nomor
        // referensinya akan ikut muncul di halaman.
        $luar = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);
        $luar->units()->create([
            'rental_product_id' => $product->id,
            'product_name' => 'Papan Hijau',
            'unit_price' => 100000,
            'price_unit' => 'day',
            'rental_amount' => 1,
            'starts_on' => '2026-12-20',
            'event_on' => '2026-12-20',
            'ends_on' => '2026-12-20',
            'recipient_name' => 'Penerima',
            'greeting' => 'Selamat',
            'delivery_method' => 'pickup',
            'return_method' => 'dropoff',
            'status' => 'pending',
            'progress' => 'waiting',
        ]);

        // Saringan "dari/sampai" pada daftar pesanan admin tetap menemukan unit
        // yang tanggal acaranya tepat di batas. Daftar pesanan hanya memuat
        // identitas pesanan (nomor referensi), bukan nama papan — nama papan
        // diperiksa di halaman detail.
        $this->actingAs($admin)
            ->get(route('admin.rental.orders', ['from' => '2026-12-10', 'to' => '2026-12-10']))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertDontSee($luar->reference)
            ->assertSee('1 pesanan ditemukan');

        $this->get(route('admin.rental.orders.show', $order))
            ->assertOk()
            ->assertSee('Papan Merah');
    }
}
