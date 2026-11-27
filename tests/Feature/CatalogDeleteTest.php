<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalDurationPackage;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\RentalProductVariant;
use App\Models\RentalUniversity;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Hapus data katalog: kategori, papan, paket durasi, voucher, universitas.
 *
 * Aturan yang dijaga:
 *  - yang boleh dihapus hanya data yang belum pernah dipakai pesanan;
 *  - data yang sudah dipakai pesanan ditolak dengan kalimat yang memberi jalan
 *    keluar ("nonaktifkan"), bukan galat SQL;
 *  - papan yang dihapus membawa warna, paket, dan fotonya;
 *  - tombol hapus selalu dua langkah (tidak memakai `confirm()`).
 */
class CatalogDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function kategori(string $nama = 'Wisuda'): RentalCategory
    {
        return RentalCategory::create(['name' => $nama]);
    }

    private function papan(?RentalCategory $kategori = null): RentalProduct
    {
        return RentalProduct::create([
            'rental_category_id' => ($kategori ?? $this->kategori())->id,
            'name' => 'Papan Mawar',
            'base_price' => 100000,
            'price_unit' => 'day',
            'stock' => 2,
        ]);
    }

    private function unit(RentalProduct $papan, array $atribut = []): RentalOrderUnit
    {
        $customer = User::factory()->create();
        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);

        return $order->units()->create(array_merge([
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
        ], $atribut));
    }

    private function voucher(array $atribut = []): Voucher
    {
        return Voucher::create(array_merge([
            'code' => 'WISUDA10',
            'description' => 'Potongan uji',
            'type' => 'percent',
            'value' => 10,
            'min_order' => 0,
            'usage_limit' => 10,
            'valid_from' => now('Asia/Jakarta')->toDateString(),
            'valid_until' => now('Asia/Jakarta')->addMonth()->toDateString(),
            'is_active' => true,
            'scope' => Voucher::SCOPE_RENTAL,
            'per_customer_limit' => 1,
        ], $atribut));
    }

    public function test_rute_hapus_tersedia_untuk_setiap_data_katalog(): void
    {
        foreach ([
            'admin.catalog.categories.destroy' => 'DELETE',
            'admin.catalog.destroy' => 'DELETE',
            'admin.catalog.packages.destroy' => 'DELETE',
            'admin.catalog.variants.destroy' => 'DELETE',
            'admin.rental.addons.destroy' => 'DELETE',
            'admin.rental.vouchers.destroy' => 'DELETE',
            'admin.rental.universities.destroy' => 'DELETE',
            'admin.rental.cashbook.destroy' => 'DELETE',
        ] as $nama => $metode) {
            $this->assertTrue(Route::has($nama), 'Rute hapus hilang: '.$nama);
            $this->assertContains($metode, Route::getRoutes()->getByName($nama)->methods(), $nama.' bukan '.$metode);
        }
    }

    public function test_tombol_hapus_dua_langkah_tanpa_confirm_bawaan_peramban(): void
    {
        $partial = (string) file_get_contents(resource_path('views/layouts/component/admin/_delete_form.blade.php'));

        // Komentar menjelaskan kenapa `confirm()` tidak dipakai; yang diperiksa
        // markup formnya.
        $markup = (string) preg_replace('/\{\{--.*?--\}\}/s', ' ', $partial);

        $this->assertStringContainsString('@method(\'DELETE\')', $markup);
        $this->assertStringContainsString('Ya, hapus permanen', $markup);
        $this->assertStringContainsString('$blocked', $markup);
        $this->assertStringNotContainsString('confirm(', $markup);

        // Setiap halaman yang punya hapus memakai partial yang sama.
        foreach ([
            'resources/views/catalog/admin.blade.php',
            'resources/views/rental/addons.blade.php',
            'resources/views/rental/vouchers.blade.php',
            'resources/views/rental/universities.blade.php',
            'resources/views/rental/cashbook.blade.php',
        ] as $berkas) {
            $this->assertStringContainsString(
                "layouts.component.admin._delete_form",
                (string) file_get_contents(base_path($berkas)),
                $berkas.' belum memakai partial hapus bersama.'
            );
        }
    }

    public function test_kategori_yang_masih_berisi_papan_ditolak_dengan_jalan_keluar(): void
    {
        $kategori = $this->kategori();
        $this->papan($kategori);

        $this->actingAs($this->admin())
            ->delete(route('admin.catalog.categories.destroy', $kategori))
            ->assertRedirect()
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('rental_categories', ['id' => $kategori->id]);
    }

    public function test_kategori_kosong_bisa_dihapus(): void
    {
        $kategori = $this->kategori('Tanpa papan');

        $this->actingAs($this->admin())
            ->delete(route('admin.catalog.categories.destroy', $kategori))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('rental_categories', ['id' => $kategori->id]);
    }

    public function test_papan_yang_sudah_dipesan_ditolak_bukan_galat_sql(): void
    {
        $papan = $this->papan();
        $this->unit($papan);

        $this->actingAs($this->admin())
            ->delete(route('admin.catalog.destroy', $papan))
            ->assertRedirect()
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('rental_products', ['id' => $papan->id]);
    }

    public function test_papan_tanpa_pesanan_dihapus_beserta_warna_dan_paket(): void
    {
        $papan = $this->papan();
        $varian = RentalProductVariant::create([
            'rental_product_id' => $papan->id,
            'name' => 'Maroon',
            'color' => '#85092d',
            'stock' => 1,
            'is_active' => true,
        ]);
        $paket = RentalDurationPackage::create([
            'rental_product_id' => $papan->id,
            'name' => 'Paket 3 hari',
            'days' => 3,
            'price' => 250000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.catalog.destroy', $papan))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('rental_products', ['id' => $papan->id]);
        $this->assertDatabaseMissing('rental_product_variants', ['id' => $varian->id]);
        $this->assertDatabaseMissing('rental_duration_packages', ['id' => $paket->id]);
    }

    public function test_paket_durasi_yang_dipakai_pesanan_ditolak(): void
    {
        $papan = $this->papan();
        $paket = RentalDurationPackage::create([
            'rental_product_id' => $papan->id,
            'name' => 'Paket 3 hari',
            'days' => 3,
            'price' => 250000,
            'is_active' => true,
        ]);
        $this->unit($papan, ['rental_duration_package_id' => $paket->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.catalog.packages.destroy', $paket))
            ->assertSessionHasErrors('package');

        $this->assertDatabaseHas('rental_duration_packages', ['id' => $paket->id]);
    }

    public function test_voucher_terpakai_ditolak_voucher_baru_dihapus(): void
    {
        $dipakai = $this->voucher();
        $papan = $this->papan();
        $unit = $this->unit($papan);
        \App\Models\RentalVoucherUsage::create([
            'voucher_id' => $dipakai->id,
            'rental_order_id' => $unit->rental_order_id,
            'customer_id' => $unit->order->customer_id,
            'discount_amount' => 10000,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.rental.vouchers.destroy', $dipakai))
            ->assertSessionHasErrors('voucher');
        $this->assertDatabaseHas('vouchers', ['id' => $dipakai->id]);

        $baru = $this->voucher(['code' => 'BARU10']);
        $this->actingAs($this->admin())
            ->delete(route('admin.rental.vouchers.destroy', $baru))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('vouchers', ['id' => $baru->id]);
    }

    public function test_universitas_boleh_dihapus_karena_pesanan_menyimpan_salinan_tarif(): void
    {
        $universitas = RentalUniversity::create([
            'name' => 'Universitas Sriwijaya',
            'delivery_fee' => 15000,
            'collection_fee' => 20000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.rental.universities.destroy', $universitas))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('rental_universities', ['id' => $universitas->id]);
    }
}
