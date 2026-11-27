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
 * Putaran 44 — urutan daftar pesanan admin.
 *
 * Sebelumnya daftar selalu "terbaru", sehingga pesanan yang tenggatnya tinggal
 * sehari bisa berada di halaman kedua. Sekarang admin bisa memilih urutan
 * tenggat konfirmasi atau tanggal acara. Urutan bawaan tidak berubah supaya
 * antrean konfirmasi tetap seperti sebelumnya.
 */
class AdminOrderSortTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private RentalProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role' => 'admin'])->save();

        $this->customer = User::factory()->create();
        $this->customer->forceFill(['role' => 'customer'])->save();

        // `rental_product_id` wajib (foreign key tanpa nullable).
        $kategori = RentalCategory::create(['name' => 'Wisuda']);
        $this->product = RentalProduct::create([
            'rental_category_id' => $kategori->id,
            'name' => 'Papan Uji',
            'base_price' => 100,
            'price_unit' => 'day',
            'stock' => 8,
        ]);
    }

    /** @return array{0: RentalOrder, 1: RentalOrder} */
    private function duaPesanan(): array
    {
        // B: dibuat lebih dulu, tetapi tenggat dan acaranya paling dekat.
        $b = RentalOrder::create([
            'reference' => 'SORT-B-'.Str::random(6),
            'customer_id' => $this->customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);
        $b->forceFill(['created_at' => now()->subDays(2)])->save();

        $a = RentalOrder::create([
            'reference' => 'SORT-A-'.Str::random(6),
            'customer_id' => $this->customer->id,
            'confirmation_deadline' => now()->addDays(6),
        ]);

        foreach ([[$b, 2], [$a, 12]] as [$order, $hari]) {
            $order->units()->create([
                'rental_product_id' => $this->product->id,
                'product_name' => 'Papan '.$order->id,
                'unit_price' => 100,
                'price_unit' => 'day',
                'rental_amount' => 100,
                'starts_on' => now()->addDays($hari),
                'event_on' => now()->addDays($hari),
                'ends_on' => now()->addDays($hari + 1),
                'recipient_name' => 'Penerima',
                'greeting' => 'Selamat',
                'delivery_method' => 'pickup',
                'return_method' => 'dropoff',
                'status' => 'confirmed',
                'progress' => 'production',
            ]);
        }

        return [$a, $b];
    }

    public function test_urutan_bawaan_tetap_pesanan_terbaru(): void
    {
        [$a, $b] = $this->duaPesanan();

        $this->actingAs($this->admin)->get(route('admin.rental.orders'))
            ->assertOk()
            ->assertSeeInOrder([$a->reference, $b->reference]);
    }

    public function test_urutan_tenggat_konfirmasi_menaikkan_yang_paling_dekat(): void
    {
        [$a, $b] = $this->duaPesanan();

        $this->actingAs($this->admin)->get(route('admin.rental.orders', ['sort' => 'tenggat']))
            ->assertOk()
            ->assertSeeInOrder([$b->reference, $a->reference])
            ->assertSee('Urutan: Tenggat konfirmasi');
    }

    public function test_urutan_tanggal_acara_menaikkan_acara_terdekat(): void
    {
        [$a, $b] = $this->duaPesanan();

        $this->actingAs($this->admin)->get(route('admin.rental.orders', ['sort' => 'acara']))
            ->assertOk()
            ->assertSeeInOrder([$b->reference, $a->reference])
            ->assertSee('Urutan: Tanggal acara');
    }

    public function test_pilihan_bawaan_mengosongkan_urutan_tanpa_galat(): void
    {
        [$a, $b] = $this->duaPesanan();

        // Formulir mengirim `sort=` ketika "Bawaan" dipilih. Middleware
        // `ConvertEmptyStringsToNull` mengubahnya jadi null; kalau tidak,
        // `Rule::in` akan menolak string kosong dan halaman balik dengan galat.
        $this->actingAs($this->admin)->get(route('admin.rental.orders', ['sort' => '']))
            ->assertOk()
            ->assertSessionHasNoErrors()
            ->assertSeeInOrder([$a->reference, $b->reference]);
    }

    public function test_nilai_urutan_asing_ditolak(): void
    {
        $this->actingAs($this->admin)->get(route('admin.rental.orders', ['sort' => 'ngawur']))
            ->assertSessionHasErrors('sort');
    }

    public function test_halaman_pertama_tetap_memuat_kartu_pesanan(): void
    {
        [$a] = $this->duaPesanan();

        $this->actingAs($this->admin)->get(route('admin.rental.orders'))
            ->assertOk()
            ->assertSee($a->reference)
            ->assertSee('adm-tasklist', false);
    }
}
