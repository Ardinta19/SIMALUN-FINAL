<?php

namespace Tests\Feature;

use App\Support\Laundry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Identitas usaha yang benar-benar dimiliki Berkah Kreatif.
 *
 * Usaha ini belum punya outlet resmi, jadi situs tidak boleh lagi menyiratkan
 * adanya alamat fisik atau meminta pelanggan "datang ke outlet". Yang
 * ditampilkan hanya kanal yang betul-betul ada: WhatsApp 0895-1405-7305,
 * Instagram, dan TikTok. Nomor/alasan ini datang langsung dari pemilik usaha,
 * bukan karangan audit.
 */
class BusinessIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tidak_ada_view_yang_menyebut_outlet(): void
    {
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $isi = (string) File::get($file->getPathname());

            // Komentar tidak dirender; abaikan supaya catatan sejarah tidak
            // memicu kegagalan.
            $isi = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $isi);
            $isi = (string) preg_replace('/<!--.*?-->/s', '', $isi);

            if (preg_match('/outlet/i', $isi)) {
                $pelanggaran[] = str_replace(resource_path('views').'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $pelanggaran, 'Usaha belum punya outlet resmi; kata "outlet" harus hilang dari view.');
    }

    public function test_checkout_memakai_label_serah_terima_tanpa_outlet_dan_hint_nya_ada(): void
    {
        $checkout = (string) File::get(resource_path('views/rental/checkout.blade.php'));

        $this->assertStringContainsString('Diambil sendiri di lokasi kami', $checkout);
        $this->assertStringContainsString('Dikembalikan sendiri ke lokasi kami', $checkout);
        $this->assertStringNotContainsString('Diambil di outlet', $checkout);

        // `aria-describedby` menunjuk id yang harus benar-benar ada; sebelumnya
        // kedua id hint ini hanya dirujuk tanpa pernah dibuat.
        $this->assertStringContainsString('id="checkout-{{ $unitNumber }}-delivery-hint"', $checkout);
        $this->assertStringContainsString('id="checkout-{{ $unitNumber }}-return-hint"', $checkout);
    }

    public function test_halaman_bantuan_menampilkan_sosial_media(): void
    {
        $customer = \App\Models\User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('customer.help'))
            ->assertOk()
            ->assertSee(Laundry::instagram(), false)
            ->assertSee(Laundry::tiktok(), false)
            ->assertSee('Instagram')
            ->assertSee('TikTok')
            ->assertDontSee('outlet');
    }

    public function test_nomor_whatsapp_dan_alamat_mengikuti_konfigurasi_usaha(): void
    {
        $this->assertSame('6289514057305', Laundry::whatsapp());
        $this->assertSame('0895-1405-7305', Laundry::phoneDisplay());
        $this->assertFalse(Laundry::hasAddress(), 'Belum punya outlet: alamat bawaan harus kosong.');
        $this->assertSame('', Laundry::googleMaps(), 'Tautan peta tidak boleh menunjuk lokasi yang tidak ada.');
    }
}
