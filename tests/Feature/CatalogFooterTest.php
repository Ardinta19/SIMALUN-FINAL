<?php

namespace Tests\Feature;

use App\Support\Laundry;
use Tests\TestCase;

/**
 * Kaki halaman katalog memuat identitas toko yang bisa dihubungi.
 *
 * Sebelumnya footer hanya berbunyi "© tahun Berkah Kreatif": nama toko
 * ditulis mati (bukan dari config) dan alamat outlet tidak pernah muncul di
 * situs, padahal opsi "Diambil di outlet" ada di checkout dan struk. Alamat
 * beserta tautan Google Maps dan WhatsApp kini diambil dari
 * `config/laundry.php` lewat `App\Support\Laundry` dan `WhatsApp::link()`.
 */
class CatalogFooterTest extends TestCase
{
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

    public function test_halaman_katalog_menampilkan_alamat_dan_tautan_peta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(Laundry::address())
            ->assertSee('Lokasi di Google Maps')
            ->assertSee('WhatsApp');
    }
}
