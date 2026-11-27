<?php

namespace Tests\Feature;

use App\Support\Laundry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kaki halaman katalog memuat identitas toko yang bisa dihubungi.
 *
 * Sebelumnya footer hanya berbunyi "© tahun Berkah Kreatif": nama toko
 * ditulis mati (bukan dari config). Sejak §9.134 usaha ini belum punya outlet
 * resmi, jadi alamat jalan dan tautan peta tidak lagi dipaksakan: yang
 * ditampilkan hanya kanal yang benar-benar bisa dihubungi — Instagram,
 * TikTok, dan WhatsApp dari `config/laundry.php` lewat `App\Support\Laundry`.
 */
class CatalogFooterTest extends TestCase
{
    use RefreshDatabase;

    private function layout(): string
    {
        return (string) file_get_contents(resource_path('views/catalog/layout.blade.php'));
    }

    public function test_footer_memakai_data_usaha_dari_config(): void
    {
        $layout = $this->layout();

        $this->assertStringContainsString('Laundry::name()', $layout);
        $this->assertStringContainsString('Laundry::address()', $layout);
        $this->assertStringContainsString('Laundry::googleMaps()', $layout);
        $this->assertStringContainsString('WhatsApp::link(', $layout);
        $this->assertStringNotContainsString('© {{ date(\'Y\') }} Berkah Kreatif', $layout, 'Nama toko tidak boleh ditulis mati.');
    }

    public function test_halaman_katalog_menampilkan_kanal_yang_bisa_dihubungi(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(Laundry::instagram(), false)
            ->assertSee(Laundry::tiktok(), false)
            ->assertSee('Instagram')
            ->assertSee('TikTok')
            ->assertSee('WhatsApp '.Laundry::phoneDisplay())
            // Belum punya outlet resmi: alamat karangan tidak boleh muncul lagi.
            ->assertDontSee('Lokasi di Google Maps')
            ->assertDontSee('Mayang Mangurai');
    }

    public function test_footer_menyembunyikan_kanal_yang_belum_diatur(): void
    {
        config([
            'laundry.instagram' => '',
            'laundry.tiktok' => '',
            'laundry.whatsapp' => '',
            'laundry.phone_display' => '',
            'laundry.address' => '',
            'laundry.google_maps' => '',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Instagram')
            ->assertDontSee('TikTok')
            ->assertDontSee('wa.me/')
            ->assertDontSee('© '.date('Y').' Berkah Kreatif ·');
    }
}
