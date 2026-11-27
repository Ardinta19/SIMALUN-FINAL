<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Tests\TestCase;

/**
 * Batas panjang alamat tersimpan harus sama dengan batas checkout.
 *
 * Halaman checkout hanya menawarkan alamat tersimpan yang panjangnya
 * ≤ 1000 karakter (`RentalOrderController::create`) dan kolom alamat
 * pesanan dibatasi `max:1000`. Sebelumnya alamat tersimpan tidak punya
 * batas atas, sehingga alamat yang sangat panjang bisa disimpan lalu
 * hilang diam-diam dari daftar pilihan saat checkout — pelanggan tidak
 * pernah tahu alasannya.
 */
class CustomerAddressLimitTest extends TestCase
{
    public function test_validasi_alamat_tersimpan_dibatasi_1000_karakter(): void
    {
        foreach ([
            'app/Http/Controllers/CustomerAddressController.php',
            'app/Http/Controllers/ProfileController.php',
        ] as $berkas) {
            $sumber = (string) file_get_contents(base_path($berkas));
            $this->assertStringContainsString(
                "'full_address' => ['required', 'string', 'min:10', 'max:1000'],",
                $sumber,
                $berkas.' belum membatasi panjang alamat.'
            );
            $this->assertStringNotContainsString(
                "'full_address' => ['required', 'string', 'min:10'],",
                $sumber,
                $berkas.' masih memakai aturan lama tanpa batas atas.'
            );
        }
    }

    public function test_alamat_terlalu_panjang_ditolak_saat_menyimpan_alamat_utama(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->post(route('profile.primary-address'), [
            'label' => 'Rumah',
            'recipient_name' => 'Siti',
            'full_address' => str_repeat('Jl. Merdeka No. 12, ', 60),
        ]);

        $response->assertSessionHasErrors('full_address');
        $this->assertSame(0, CustomerAddress::where('customer_id', $user->id)->count());
    }
}
