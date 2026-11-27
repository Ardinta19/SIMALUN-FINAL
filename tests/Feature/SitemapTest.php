<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use Tests\TestCase;

/**
 * Peta situs hanya memuat halaman yang memang terbuka.
 *
 * Katalog sudah memasang judul, deskripsi, dan tag Open Graph, tetapi tidak
 * punya `sitemap.xml`, sehingga mesin pencari harus menemukan tiap papan
 * lewat tautan biasa. Peta situs ini mendaftarkan beranda dan papan aktif di
 * kategori aktif saja — halaman pesanan, akun, dan panel admin tetap tidak
 * didaftarkan karena bukan untuk umum.
 */
class SitemapTest extends TestCase
{
    public function test_rute_dan_tampilan_peta_situs_ada(): void
    {
        $rute = (string) file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("Route::get('/sitemap.xml'", $rute);
        $this->assertStringContainsString("->name('sitemap')", $rute);

        $berkas = resource_path('views/catalog/sitemap.blade.php');
        $this->assertFileExists($berkas);

        $isi = (string) file_get_contents($berkas);
        $this->assertStringContainsString('urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $isi);
        // Deklarasi XML tidak boleh ditulis mentah: PHP akan menganggapnya tag
        // pendek dan halaman gagal dirender.
        $this->assertStringContainsString("{!! '<?xml version=\"1.0\" encoding=\"UTF-8\"?>' !!}", $isi);
    }

    public function test_peta_situs_hanya_memuat_papan_aktif(): void
    {
        $kategori = RentalCategory::create(['name' => 'Ucapan', 'is_active' => true]);
        $papan = RentalProduct::create([
            'rental_category_id' => $kategori->id,
            'name' => 'Papan Ucapan Selamat',
            'base_price' => 350000,
            'stock' => 2,
            'is_active' => true,
        ]);
        $nonaktif = RentalProduct::create([
            'rental_category_id' => $kategori->id,
            'name' => 'Papan Arsip',
            'base_price' => 300000,
            'stock' => 1,
            'is_active' => false,
        ]);

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $response
            ->assertSee('<loc>'.route('welcome').'</loc>', false)
            ->assertSee(route('catalog.show', $papan), false)
            ->assertDontSee(route('catalog.show', $nonaktif), false)
            ->assertDontSee('/admin', false);
    }
}
