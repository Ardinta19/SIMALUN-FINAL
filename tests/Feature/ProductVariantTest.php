<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Varian warna papan: stok per warna, pemilih swatch di katalog, harga yang
 * berubah saat jumlah unit berubah, dan keranjang yang bergerak tanpa memuat
 * ulang halaman.
 *
 * Semua pemeriksaan di sini membaca berkas, bukan basis data, karena tes
 * berbasis Eloquent dilewati di lingkungan tanpa ekstensi database. Perilaku
 * yang butuh render halaman tetap dijaga tes fitur lain di mesin pengembang;
 * yang dijaga di sini adalah hal-hal yang paling mudah hilang saat seseorang
 * menyunting view, CSS, atau skrip.
 */
class ProductVariantTest extends TestCase
{
    public function test_tabel_varian_dan_salinan_pada_unit_pesanan_ada(): void
    {
        $tabel = File::get(base_path('database/migrations/2026_11_22_000026_create_rental_product_variants.php'));
        $this->assertStringContainsString("Schema::create('rental_product_variants'", $tabel);
        $this->assertStringContainsString("\$table->string('name', 60)", $tabel);
        $this->assertStringContainsString("\$table->string('color', 7)", $tabel);
        $this->assertStringContainsString("\$table->unsignedInteger('stock')", $tabel);
        $this->assertStringContainsString("\$table->string('image_path')->nullable()", $tabel);
        $this->assertStringContainsString("unique(['rental_product_id', 'name'])", $tabel);

        $unit = File::get(base_path('database/migrations/2026_11_22_000027_add_rental_unit_variant.php'));
        $this->assertStringContainsString("\$table->foreignId('rental_product_variant_id')->nullable()", $unit);
        $this->assertStringContainsString("constrained('rental_product_variants')->nullOnDelete()", $unit);
        // Nama & warna disalin saat checkout supaya riwayat tidak berubah
        // ketika admin mengganti nama warna di katalog.
        $this->assertStringContainsString("\$table->string('variant_name', 60)", $unit);
        $this->assertStringContainsString("\$table->string('variant_color', 7)", $unit);
    }

    public function test_model_varian_dan_relasi_produk(): void
    {
        $model = File::get(app_path('Models/RentalProductVariant.php'));
        $this->assertStringContainsString('class RentalProductVariant extends Model', $model);
        $this->assertStringContainsString("'stock', 'sort_order', 'is_active'", $model);

        $product = File::get(app_path('Models/RentalProduct.php'));
        $this->assertStringContainsString('public function variants(): HasMany', $product);
        $this->assertStringContainsString('public function activeVariants(): HasMany', $product);
        $this->assertStringContainsString('public function hasVariants(): bool', $product);
        $this->assertStringContainsString('public function findVariant($id)', $product);
        $this->assertStringContainsString('public function activeVariantList()', $product);

        $unit = File::get(app_path('Models/RentalOrderUnit.php'));
        $this->assertStringContainsString("'rental_product_variant_id'", $unit);
        $this->assertStringContainsString("'variant_name'", $unit);
        $this->assertStringContainsString("'variant_color'", $unit);
    }

    public function test_stok_dihitung_per_varian_tanpa_mengubah_papan_lama(): void
    {
        $service = File::get(app_path('Services/RentalAvailability.php'));

        $this->assertStringContainsString('public function variantStocks(', $service);
        $this->assertStringContainsString('public function stockForVariant(', $service);
        // Jalur lama harus tetap ada: papan tanpa varian memakai
        // `rental_products.stock`, jadi produk yang sudah berjalan tidak
        // berubah perilakunya.
        $this->assertStringContainsString('return max(0, $stock - $maximum);', $service);
        $this->assertStringContainsString('minimumFromStock((int) $product->stock', $service);
        // Konfirmasi unit bervarian diperiksa terhadap warnanya sendiri.
        $this->assertStringContainsString('$unit->rental_product_variant_id ?? 0', $service);
        // Rentang dari keranjang membawa `variant_id`, dan pemeriksa
        // ketersediaan memakainya untuk memisahkan stok antarvarna.
        $this->assertStringContainsString("\$range['variant_id'] ?? \$range['rental_product_variant_id'] ?? 0", $service);
        $this->assertStringContainsString('fits((int) $product->stock', $service);
    }

    public function test_katalog_menampilkan_pemilih_warna_tanpa_kolom_total_beranimasi(): void
    {
        $index = File::get(resource_path('views/catalog/index.blade.php'));
        $show = File::get(resource_path('views/catalog/show.blade.php'));
        $picker = File::get(resource_path('views/catalog/_variant_picker.blade.php'));

        foreach ([$index, $show] as $view) {
            $this->assertStringContainsString("@include('catalog._variant_picker'", $view);
            $this->assertStringContainsString('name="variant_id"', $view);
            // Putaran 36: pemilik usaha meminta kolom "Total" yang berubah
            // setiap kali jumlah disentuh dicabut. Harga satuan tetap ditulis
            // server, dan hitungan uang terjadi di keranjang & halaman pesanan.
            $this->assertStringContainsString('class="price"', $view);
            $this->assertStringNotContainsString('data-bk-line-total', $view);
        }

        // Pemilih: satu tombol per warna, cincin lewat variabel CSS (bukan
        // gaya sebaris bebas), dan warna habis tidak bisa dipilih.
        $this->assertStringContainsString('data-bk-variants', $picker);
        $this->assertStringContainsString('data-variant-color="{{ $variant->color }}"', $picker);
        $this->assertStringContainsString('style="--bk-swatch:{{ $variant->color }}"', $picker);
        $this->assertStringContainsString('aria-pressed=', $picker);
        $this->assertStringContainsString('@if($vkHabis) disabled aria-disabled="true" @endif', $picker);
        $this->assertStringNotContainsString('<style', $picker);
    }

    public function test_keranjang_menampilkan_warna_dan_bisa_diperbarui_tanpa_muat_ulang(): void
    {
        $cart = File::get(resource_path('views/catalog/cart.blade.php'));
        $controller = File::get(app_path('Http/Controllers/RentalCartController.php'));

        $this->assertStringContainsString('data-cart-group="{{ $group[\'key\'] }}"', $cart);
        $this->assertStringContainsString('data-cart-count', $cart);
        $this->assertStringContainsString('data-cart-subtotal', $cart);
        $this->assertStringContainsString('data-cart-total', $cart);
        $this->assertStringContainsString('data-cart-live', $cart);
        $this->assertStringContainsString('Warna <strong>{{ $variant->name }}</strong>', $cart);

        // Kelompok dipisah per warna, bukan hanya per papan + tanggal.
        $this->assertStringContainsString("(int) (\$item['variant_id'] ?? 0)", $controller);
        $this->assertStringContainsString('wantsJson()', $controller);
        $this->assertStringContainsString("'total_label'", $controller);
        $this->assertStringContainsString('Pilih warna papan lebih dulu.', $controller);
        $this->assertStringContainsString("whereIn('status', RentalOrderUnit::RESERVING_STATUSES)", File::get(app_path('Http/Controllers/RentalCatalogController.php')));
    }

    public function test_skrip_varian_dan_keranjang_langsung_memakai_data_server(): void
    {
        $variants = File::get(public_path('js/berkah-variants.js'));
        $this->assertStringContainsString('input[name="variant_id"]', $variants);
        $this->assertStringContainsString('input.setAttribute(\'max\'', $variants);
        // Kolom total baris tidak lagi diperbarui dari sisi peramban.
        $this->assertStringNotContainsString('data-bk-line-total', $variants);

        $live = File::get(public_path('js/berkah-cart-live.js'));
        $this->assertStringContainsString("Accept: 'application/json'", $live);
        $this->assertStringContainsString('FormData(form)', $live);
        $this->assertStringContainsString('window.location.reload()', $live, 'Kegagalan harus kembali ke jalur formulir biasa agar pesan server tampil.');

        $layout = File::get(resource_path('views/catalog/layout.blade.php'));
        $this->assertSame(1, substr_count($layout, 'berkah-variants.js'));
        $this->assertSame(1, substr_count($layout, 'berkah-cart-live.js'));
    }

    public function test_admin_mengelola_warna_lewat_rute_dan_formulirnya(): void
    {
        $routes = File::get(base_path('routes/web.php'));
        foreach (['catalog.variants.store', 'catalog.variants.update', 'catalog.variants.toggle', 'catalog.variants.destroy'] as $name) {
            $this->assertStringContainsString($name, $routes);
        }

        $controller = File::get(app_path('Http/Controllers/RentalCatalogController.php'));
        foreach (['variantStore', 'variantUpdate', 'variantToggle', 'variantDestroy'] as $method) {
            $this->assertStringContainsString('public function '.$method.'(', $controller);
        }
        $this->assertStringContainsString("regex:/^#[0-9a-fA-F]{6}$/", $controller);
        $this->assertStringContainsString('Stok warna tidak dapat dikurangi', $controller);

        $admin = File::get(resource_path('views/catalog/admin.blade.php'));
        $this->assertStringContainsString('Warna &amp; foto (varian)', $admin);
        $this->assertStringContainsString("route('admin.catalog.variants.store'", $admin);
        $this->assertStringContainsString('data-variant-delete', $admin);
        $this->assertStringContainsString('showConfirmModal', $admin);
        $this->assertStringNotContainsString('confirm(', $admin, 'Hapus warna tidak boleh memakai balon bawaan peramban.');
    }

    public function test_pesanan_menyimpan_warna_dan_menampilkannya(): void
    {
        $order = File::get(app_path('Http/Controllers/RentalOrderController.php'));
        $this->assertStringContainsString("'variant_name' => \$variant?->name", $order);
        $this->assertStringContainsString("'variant_color' => \$variant?->color", $order);
        $this->assertStringContainsString('warna papan sudah tidak tersedia', $order);

        $checkout = File::get(resource_path('views/rental/checkout.blade.php'));
        $this->assertStringContainsString('Warna <strong>{{ $checkoutVariant->name }}</strong>', $checkout);

        foreach (['rental/show.blade.php', 'rental/receipt.blade.php', 'rental/unit.blade.php', 'rental/admin-detail.blade.php'] as $view) {
            $this->assertStringContainsString("\$unit->variant_name", File::get(resource_path('views/'.$view)), $view.' harus menampilkan warna yang dipesan.');
        }
    }

    public function test_nama_kolom_varian_memakai_bahasa_indonesia(): void
    {
        $lang = File::get(base_path('lang/id/validation.php'));
        $this->assertStringContainsString("'variant_id' => 'warna papan',", $lang);
        $this->assertStringContainsString("'color' => 'warna',", $lang);
    }

    public function test_warna_ikut_tema_gelap(): void
    {
        $theme = File::get(public_path('css/berkah-theme.css'));
        $this->assertStringContainsString('html[data-theme="dark"] .bk-variant', $theme);
        $this->assertStringContainsString('html[data-theme="dark"] .bk-swatch', $theme);
        $this->assertStringContainsString('html[data-theme="dark"] .cart-total', $theme);

        $css = File::get(public_path('css/berkah-experience.css'));
        $this->assertStringContainsString('.bk-swatch', $css);
        $this->assertStringContainsString('@keyframes bk-total-pop', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
