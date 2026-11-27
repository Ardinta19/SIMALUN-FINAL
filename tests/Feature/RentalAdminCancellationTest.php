<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Services\RentalAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pembatalan oleh admin hanya untuk unit yang belum keluar dari toko.
 *
 * Pembatalan melepas reservasi tanggal sekaligus mengeluarkan unit dari
 * antrean pengembalian dan dari penutupan otomatis. Bila papan sudah
 * diserahkan, membatalkannya membuat papan yang masih di tangan pelanggan
 * hilang dari pelacakan, jadi form disembunyikan dan server menolak.
 */
class RentalAdminCancellationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:RentalProduct,3:RentalOrder,4:array<string,mixed>} */
    private function context(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => 'Papan',
            'base_price' => 100,
            'price_unit' => 'day',
            'stock' => 2,
        ]);
        $order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);
        $base = [
            'rental_product_id' => $product->id,
            'product_name' => 'Papan',
            'unit_price' => 100,
            'price_unit' => 'day',
            'rental_amount' => 100,
            'starts_on' => '2026-12-10',
            'event_on' => '2026-12-10',
            'ends_on' => '2026-12-11',
            'recipient_name' => 'Penerima',
            'greeting' => 'Turut berduka cita',
            'delivery_method' => 'pickup',
            'return_method' => 'dropoff',
            'status' => 'confirmed',
        ];

        return [$admin, $customer, $product, $order, $base];
    }

    public function test_papan_yang_sudah_diserahkan_tidak_bisa_dibatalkan_admin(): void
    {
        [$admin, , , $order, $base] = $this->context();
        $unit = $order->units()->create($base + ['progress' => 'handed_over']);

        $this->actingAs($admin)
            ->post(route('admin.rental.cancel', $unit), ['reason' => 'Salah input', 'responsibility' => 'other'])
            ->assertSessionHasErrors('unit');

        $fresh = $unit->fresh();
        $this->assertSame('confirmed', $fresh->status);
        $this->assertSame('handed_over', $fresh->progress);
        $this->assertSame(0, $fresh->cancellationEvents()->count(), 'Penolakan tidak boleh meninggalkan catatan pembatalan.');
    }

    public function test_form_pembatalan_hanya_muncul_untuk_unit_yang_belum_diserahkan(): void
    {
        [$admin, , , $order, $base] = $this->context();
        $handed = $order->units()->create($base + ['progress' => 'handed_over']);
        $waiting = $order->units()->create(array_merge($base, ['recipient_name' => 'Penerima dua', 'progress' => 'waiting']));

        $this->actingAs($admin)
            ->get(route('admin.rental.orders.show', $order))
            ->assertOk()
            ->assertDontSee(route('admin.rental.cancel', $handed))
            ->assertSee(route('admin.rental.cancel', $waiting))
            ->assertSee('Tandai pengembalian lewat bagian perkembangan lebih dulu');
    }

    public function test_unit_yang_belum_diserahkan_tetap_dapat_dibatalkan_dan_tanggalnya_lepas(): void
    {
        [$admin, , $product, $order, $base] = $this->context();
        $unit = $order->units()->create($base + ['progress' => 'production']);
        $availability = app(RentalAvailability::class);

        $this->assertSame(1, $availability->minimumAvailable($product, '2026-12-10', '2026-12-11'));

        $this->actingAs($admin)
            ->post(route('admin.rental.cancel', $unit), ['reason' => 'Pelanggan membatalkan', 'responsibility' => 'customer'])
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $unit->fresh()->status);
        $this->assertSame(2, $availability->minimumAvailable($product, '2026-12-10', '2026-12-11'), 'Tanggal harus kembali tersedia setelah pembatalan.');
    }
}
