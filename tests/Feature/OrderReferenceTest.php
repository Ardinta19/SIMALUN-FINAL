<?php

namespace Tests\Feature;

use App\Models\RentalOrder;
use App\Models\User;
use App\Support\OrderReference;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nomor pesanan yang bisa dibaca: BK-261127-001.
 *
 * Sebelumnya `reference` diisi UUID. UUID unik, tetapi tidak bisa diucapkan
 * lewat telepon, tidak bisa ditulis di buku catatan, dan di layar hanya terbaca
 * sebagai deretan acak. Tes ini mengunci bentuk, urutan, dan pemakaiannya.
 */
class OrderReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bentuk_nomor_dikenali_dan_dipendekkan(): void
    {
        $this->assertTrue(OrderReference::isReadable('BK-261127-001'));
        $this->assertTrue(OrderReference::isReadable('BK-261127-042'));
        $this->assertFalse(OrderReference::isReadable('9f1c0b1a-6f4d-4f8e-9a0b-7c2e5d1a3b44'));
        $this->assertFalse(OrderReference::isReadable('BK-2611-01'));
        $this->assertFalse(OrderReference::isReadable(null));

        $this->assertSame('261127-001', OrderReference::short('BK-261127-001'));
        // Nomor lama (UUID) tetap tampil sebagai potongan, bukan kosong.
        $this->assertSame('9f1c0b1a', OrderReference::short('9f1c0b1a-6f4d-4f8e-9a0b-7c2e5d1a3b44'));
        $this->assertSame('-', OrderReference::short(null));
    }

    public function test_nomor_berurutan_pada_hari_yang_sama(): void
    {
        $hari = CarbonImmutable::parse('2026-11-27 09:00:00', 'Asia/Jakarta');

        $this->assertSame('BK-261127-001', OrderReference::make($hari));
        $this->buatPesanan('BK-261127-001');
        $this->assertSame('BK-261127-002', OrderReference::make($hari));

        // Hari lain mulai dari 001 lagi.
        $this->assertSame('BK-261128-001', OrderReference::make($hari->addDay()));
    }

    public function test_nomor_tidak_dipakai_ulang_walau_ada_celah(): void
    {
        $hari = CarbonImmutable::parse('2026-11-27 09:00:00', 'Asia/Jakarta');
        $this->buatPesanan('BK-261127-001');
        $this->buatPesanan('BK-261127-002');
        $this->buatPesanan('BK-261127-003');

        // Pesanan 002 dihapus (mis. purge sebagian): nomor berikutnya tetap 004,
        // bukan 003 yang bisa membingungkan dua pesanan berbeda.
        RentalOrder::where('reference', 'BK-261127-002')->delete();

        $this->assertSame('BK-261127-004', OrderReference::make($hari));
    }

    public function test_checkout_memakai_nomor_yang_rapi_bukan_uuid(): void
    {
        $sumber = (string) file_get_contents(app_path('Http/Controllers/RentalOrderController.php'));

        $this->assertStringContainsString('OrderReference::make()', $sumber);
        $this->assertStringNotContainsString("'reference' => (string) Str::uuid()", $sumber);
    }

    public function test_panel_menampilkan_nomor_pesanan_dengan_contoh_pencarian(): void
    {
        $daftar = (string) file_get_contents(resource_path('views/rental/admin.blade.php'));

        $this->assertStringContainsString('Nomor pesanan', $daftar);
        $this->assertStringContainsString('adm-ref', $daftar);
        $this->assertStringContainsString('Contoh: BK-261127-001', $daftar);
    }

    private function buatPesanan(string $reference): RentalOrder
    {
        return RentalOrder::create([
            'reference' => $reference,
            'customer_id' => User::factory()->create()->id,
            'confirmation_deadline' => now()->addDay(),
        ]);
    }
}
