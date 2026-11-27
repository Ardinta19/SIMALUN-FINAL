<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use App\Support\OrderedItems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Putaran 40 — tampilan "Item yang dipesan".
 *
 * Daftar isi pesanan tampil di tiga halaman dengan sumber data berbeda:
 * checkout membaca sesi keranjang, dua halaman lain membaca unit pesanan.
 * Supaya tidak menjadi tiga daftar yang lambat laun berbeda, semuanya
 * dinormalkan di `App\Support\OrderedItems` dan dirender satu partial.
 *
 * Tes ini menjaga dua hal yang mudah hilang: (1) partial tetap dipakai ketiga
 * halaman, dan (2) gayanya berada di berkas yang dimuat **kedua** layout —
 * `berkah-ui-kit.css`, bukan `berkah-experience.css`, karena layout admin tidak
 * memuat berkas terakhir.
 */
class OrderedItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_dipakai_checkout_pesanan_pelanggan_dan_admin(): void
    {
        foreach ([
            'resources/views/rental/checkout.blade.php',
            'resources/views/rental/show.blade.php',
            'resources/views/rental/admin-detail.blade.php',
        ] as $berkas) {
            $view = (string) file_get_contents(base_path($berkas));

            $this->assertStringContainsString("@include('rental._ordered_items'", $view, $berkas.' harus memakai partial daftar item.');
            $this->assertStringContainsString('App\\Support\\OrderedItems::', $view, $berkas.' harus memakai data hasil normalisasi.');
        }
    }

    public function test_partial_menampilkan_nomor_nama_warna_tanggal_item_tambahan_dan_harga(): void
    {
        $partial = (string) file_get_contents(resource_path('views/rental/_ordered_items.blade.php'));

        foreach ([
            'Item yang dipesan',
            'bk-items__num',
            "str_pad((string) \$item['no']",
            "\$item['name']",
            "\$item['variant_name']",
            'bk-swatch',
            "\$item['event_on']->format('d/m/Y')",
            "\$item['addons']",
            "\$item['price_amount']",
            "'hari' : 'acara'",
            'bk-status-chip',
        ] as $needle) {
            $this->assertStringContainsString($needle, $partial, 'Daftar item kehilangan: '.$needle);
        }

        // Judul kartu memakai id yang diturunkan dari isinya, agar dua daftar di
        // satu halaman tidak pernah berbagi id.
        $this->assertStringContainsString("\$judulId = 'bk-items-title-'", $partial);
        $this->assertStringContainsString('aria-labelledby="{{ $judulId }}"', $partial);
    }

    /** Kartu daftar "Pesanan saya" menyebut isi pesanan, bukan hanya jumlah unit. */
    public function test_kartu_pesanan_menyebut_nama_papan_tanpa_n_plus_satu(): void
    {
        $view = (string) file_get_contents(resource_path('views/rental/index.blade.php'));
        $this->assertStringContainsString('$itemNames = $order->units->take(3)', $view);
        $this->assertStringContainsString('bk-order-items', $view);
        $this->assertStringContainsString('papan lain', $view);

        $controller = (string) file_get_contents(app_path('Http/Controllers/RentalOrderController.php'));
        $this->assertStringContainsString(
            "->with(['units' => fn (\$q) => \$q->select('id', 'rental_order_id', 'product_name', 'variant_name')->orderBy('id')])",
            $controller,
            'Nama papan harus dimuat bersama daftar, bukan kueri per kartu.'
        );
    }

    public function test_gaya_daftar_item_dimuat_layout_katalog_dan_admin(): void
    {
        $uiKit = (string) file_get_contents(public_path('css/berkah-ui-kit.css'));
        foreach (['.bk-items {', '.bk-items__row', '.bk-items__name', '.bk-items__price', '.bk-items__addons', 'html[data-theme="dark"] .bk-items'] as $needle) {
            $this->assertStringContainsString($needle, $uiKit, 'Gaya daftar item harus ada di berkas bersama: '.$needle);
        }

        // `berkah-ui-kit.css` dimuat kedua layout; `berkah-experience.css` hanya
        // dimuat layout katalog, sehingga tidak boleh diandalkan.
        foreach (['layouts/admin.blade.php', 'catalog/layout.blade.php'] as $layout) {
            $isi = (string) file_get_contents(resource_path('views/'.$layout));
            $this->assertStringContainsString('_theme_head', $isi, $layout.' harus memuat kepala tema bersama.');
        }
        foreach (['.bk-items {', '.bk-items__row'] as $needle) {
            $this->assertStringNotContainsString($needle, (string) file_get_contents(public_path('css/berkah-experience.css')));
        }

        // Saat berada di dalam kartu admin, daftar item tidak menggambar kartu kedua.
        $admin = (string) file_get_contents(public_path('css/berkah-admin.css'));
        $this->assertStringContainsString('.adm-card.bk-items {', $admin);
    }

    public function test_from_units_meringkas_unit_pesanan(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Ucapan', 'base_price' => 125000, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan Ucapan', 'variant_name' => 'Putih', 'variant_color' => '#ffffff',
            'unit_price' => 125000, 'price_unit' => 'day', 'rental_amount' => 125000,
            'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(4),
            'recipient_name' => 'Ibu Sari', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
        ]);
        $unit->addons()->create(['name' => 'Lilin', 'price' => 12000, 'quantity' => 2, 'total' => 24000]);

        $items = OrderedItems::fromUnits([$unit->fresh(['addons'])], fn ($u) => 'unit-'.$u->id);

        $this->assertCount(1, $items);
        $this->assertSame(1, $items[0]['no']);
        $this->assertSame('Papan Ucapan', $items[0]['name']);
        $this->assertSame('Putih', $items[0]['variant_name']);
        $this->assertSame([['label' => 'Lilin × 2', 'amount' => 24000]], $items[0]['addons']);
        $this->assertSame(125000, $items[0]['price_amount']);
        $this->assertSame('day', $items[0]['price_unit']);
        $this->assertSame('Menunggu konfirmasi', $items[0]['status_label']);
        $this->assertTrue($items[0]['dates_known'], 'Unit tersimpan selalu punya tanggal acara.');
        $this->assertSame('unit-'.$unit->id, $items[0]['url']);
    }

    public function test_from_cart_memakai_harga_dasar_dan_warna_terpilih(): void
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan Ucapan', 'base_price' => 100000, 'price_unit' => 'event', 'stock' => 3]);
        $variant = $product->variants()->create(['name' => 'Marun', 'color' => '#800000', 'stock' => 2, 'is_active' => true]);

        $items = OrderedItems::fromCart([
            'a' => ['product_id' => $product->id, 'variant_id' => $variant->id, 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-02'],
        ], RentalProduct::with('variants')->whereKey($product->id)->get()->keyBy('id'));

        $this->assertCount(1, $items);
        $this->assertSame('Marun', $items[0]['variant_name']);
        $this->assertSame(100000, $items[0]['price_amount'], 'Warna tidak mengubah harga; harga dasar dipakai.');
        $this->assertSame('event', $items[0]['price_unit']);
        $this->assertSame('01/12/2026', $items[0]['event_on']->format('d/m/Y'));
        $this->assertFalse($items[0]['dates_known'], 'Tanggal checkout masih bisa berubah, jadi tidak ditampilkan sebagai fakta.');
        $this->assertNull($items[0]['status_label']);
    }
}
