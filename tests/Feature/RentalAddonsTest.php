<?php

namespace Tests\Feature;

use App\Models\RentalAddon;
use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderReference;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Item tambahan (dipilih per unit) dan foto referensi desain (per pesanan).
 *
 * Aturan yang dijaga:
 *  - admin mengelola satu katalog item tambahan untuk semua papan;
 *  - harga item disalin ke pesanan saat checkout sehingga ikut tagihan unit;
 *  - item nonaktif tidak bisa dipesan lewat permintaan yang disusun manual;
 *  - foto referensi disimpan di disk privat dan hanya bisa dibuka pemilik
 *    pesanan atau admin.
 */
class RentalAddonsTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $price = 100000): RentalProduct
    {
        $category = RentalCategory::create(['name' => 'Wisuda']);

        return RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => 'Papan',
            'base_price' => $price,
            'price_unit' => 'day',
            'stock' => 2,
        ]);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    /** @return array<string, array<string, mixed>> */
    private function unitPayload(RentalProduct $product, array $extra = []): array
    {
        $date = now('Asia/Jakarta')->addDays(3)->toDateString();
        $units = [];

        foreach (array_keys(session('rental_cart')) as $key) {
            $units[$key] = array_merge([
                'event_on' => $date,
                'ends_on' => $date,
                'recipient_name' => 'Penerima',
                'greeting' => 'Selamat',
                'delivery_method' => 'pickup',
                'return_method' => 'dropoff',
            ], $extra);
        }

        return $units;
    }

    public function test_halaman_panel_item_tambahan_tampil_dan_menjelaskan_aturan(): void
    {
        $this->actingAs($this->admin())->get(route('admin.rental.addons.index'))->assertOk()
            ->assertSee('Item tambahan')
            ->assertSee('Belum ada item tambahan')
            ->assertSee('Berlaku untuk semua papan');

        $addon = RentalAddon::create(['name' => 'Lilin', 'price' => 12000]);

        // Selama belum dipakai, tombol Hapus tersedia (form _method DELETE).
        $this->get(route('admin.rental.addons.index'))->assertOk()
            ->assertSee('Lilin')
            ->assertSee('Rp 12.000')
            ->assertSee('value="DELETE"', false);

        // Item yang sudah dipakai pesanan tidak menampilkan tombol Hapus.
        $product = $this->product(100000);
        $this->actingAs($this->customer())->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 1]]),
        ])->assertSessionHasNoErrors();

        // URL ubah dan hapus sama (/item-tambahan/{id}); pembeda form hapus
        // adalah _method DELETE. Saat item sudah dipakai, itu harus hilang.
        $this->actingAs($this->admin())->get(route('admin.rental.addons.index'))->assertOk()
            ->assertSee('Dipakai pesanan')
            ->assertSee('Ubah item')
            ->assertDontSee('value="DELETE"', false);
    }

    public function test_admin_menambah_item_tambahan_dan_tercatat_di_audit(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.rental.addons.store'), [
            'name' => 'Kartu ucapan',
            'description' => 'Kartu A5 dengan nama penerima.',
            'price' => 15000,
            'sort_order' => 2,
            'image' => UploadedFile::fake()->image('kartu.jpg', 600, 600),
        ])->assertSessionHasNoErrors();

        $addon = RentalAddon::firstOrFail();
        $this->assertSame('Kartu ucapan', $addon->name);
        $this->assertSame(15000, $addon->price);
        $this->assertTrue($addon->is_active);
        $this->assertNotNull($addon->image_path);
        Storage::disk('public')->assertExists($addon->image_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rental.addon.create']);
    }

    public function test_harga_item_tambahan_masuk_tagihan_unit_dan_disalin_ke_pesanan(): void
    {
        $product = $this->product(100000);
        $addon = RentalAddon::create(['name' => 'Lilin', 'price' => 12000]);
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 2]]),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $unit = RentalOrderUnit::firstOrFail();
        $this->assertSame(24000, (int) $unit->addon_amount);
        $this->assertSame(124000, $unit->payableAmount(), 'Tagihan unit harus mencakup item tambahan.');

        $line = $unit->addons()->firstOrFail();
        $this->assertSame('Lilin', $line->name);
        $this->assertSame(12000, $line->price);
        $this->assertSame(2, $line->quantity);
        $this->assertSame(24000, $line->total);
        $this->assertSame($addon->id, $line->rental_addon_id);

        // Katalog berubah setelah pesanan dibuat: salinan lama tidak ikut berubah.
        $addon->update(['name' => 'Lilin besar', 'price' => 20000]);
        $unit->refresh();
        $this->assertSame('Lilin', $unit->addons()->firstOrFail()->name);
        $this->assertSame(24000, (int) $unit->addon_amount);
    }

    /** Semua pesan galat dari permintaan terakhir, digabung agar mudah dicari. */
    private function errorText(): string
    {
        $bag = session('errors');

        return $bag ? implode(' | ', $bag->getBag('default')->all()) : '';
    }

    public function test_pesan_galat_item_tambahan_berbahasa_indonesia(): void
    {
        $product = $this->product(100000);
        $addon = RentalAddon::create(['name' => 'Lilin', 'price' => 12000]);
        $this->actingAs($this->customer())->post(route('cart.store', $product), ['quantity' => 1]);

        // Jumlah per item di atas 5: pesan menyebut batasnya, tanpa nama field
        // teknis seperti "units.1.addons.3".
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 6]]),
        ])->assertSessionHasErrors();

        $this->assertStringContainsString('Jumlah tiap item tambahan maksimal 5.', $this->errorText());
        $this->assertStringNotContainsString('units.', $this->errorText());

        // Enam jenis berbeda melewati batas 5 jenis per unit.
        for ($i = 1; $i <= 5; $i++) {
            RentalAddon::create(['name' => 'Item '.$i, 'price' => 1000, 'sort_order' => $i]);
        }

        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => RentalAddon::pluck('id')->mapWithKeys(fn ($id) => [$id => 1])->all()]),
        ])->assertSessionHasErrors();

        $this->assertStringContainsString('Unit 1: pilih maksimal 5 jenis item tambahan per unit.', $this->errorText());
    }

    public function test_pesan_galat_foto_referensi_berbahasa_indonesia(): void
    {
        $product = $this->product(100000);
        $this->actingAs($this->customer())->post(route('cart.store', $product), ['quantity' => 1]);

        $payload = fn (array $files) => [
            'terms' => '1',
            'units' => $this->unitPayload($product),
            'reference_images' => $files,
        ];

        $enam = [];
        for ($i = 1; $i <= 6; $i++) {
            $enam[] = UploadedFile::fake()->image('contoh-'.$i.'.jpg');
        }

        $this->post(route('rental.checkout.store'), $payload($enam))->assertSessionHasErrors();
        $this->assertStringContainsString('Maksimal 5 foto referensi.', $this->errorText());

        $this->post(route('rental.checkout.store'), $payload([
            UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors();
        $this->assertStringContainsString('Berkas harus berupa gambar.', $this->errorText());

        // 25 MB per foto: 6 MB sudah boleh, 26 MB ditolak.
        $this->post(route('rental.checkout.store'), $payload([
            UploadedFile::fake()->create('besar.jpg', 26000, 'image/jpeg'),
        ]))->assertSessionHasErrors();
        $this->assertStringContainsString('Ukuran tiap foto maksimal 25 MB.', $this->errorText());
        $this->assertStringNotContainsString('reference_images', $this->errorText());
    }

    public function test_total_ukuran_foto_referensi_dibatasi_60_mb(): void
    {
        $product = $this->product(100000);
        $this->actingAs($this->customer())->post(route('cart.store', $product), ['quantity' => 1]);

        // Tiap foto lolos batas 25 MB, tetapi jumlahnya melewati batas total.
        $tiga = [];
        for ($i = 1; $i <= 3; $i++) {
            $tiga[] = UploadedFile::fake()->create('foto-'.$i.'.jpg', 24000, 'image/jpeg');
        }

        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product),
            'reference_images' => $tiga,
        ])->assertSessionHasErrors();

        $this->assertStringContainsString('Total ukuran foto referensi maksimal 60 MB.', $this->errorText());
        $this->assertSame(0, RentalOrder::count(), 'Pesanan tidak boleh dibuat saat unggahan ditolak.');
        $this->assertSame(0, RentalOrderReference::count());
    }

    public function test_foto_referensi_berukuran_besar_diterima_di_bawah_batas(): void
    {
        Storage::fake('local');
        $product = $this->product(100000);
        $this->actingAs($this->customer())->post(route('cart.store', $product), ['quantity' => 1]);

        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product),
            'reference_images' => [UploadedFile::fake()->create('foto.jpg', 20000, 'image/jpeg')],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $reference = RentalOrderReference::firstOrFail();
        Storage::disk('local')->assertExists($reference->image_path);
    }

    public function test_halaman_galat_413_memakai_bahasa_indonesia(): void
    {
        // Dipakai ketika PHP membuang unggahan karena post_max_size. Tanpa
        // halaman ini pelanggan melihat galat bawaan tanpa petunjuk apa pun.
        $this->view('errors.413')
            ->assertSee('Unggahan Terlalu Besar')
            ->assertSee('Perkecil foto')
            ->assertSee('Kembali ke Keranjang');
    }

    public function test_baris_item_tambahan_tampil_di_halaman_unit_tanpa_voucher(): void
    {
        $product = $this->product(100000);
        $addon = RentalAddon::create(['name' => 'Lilin', 'price' => 12000]);
        $this->actingAs($this->customer())->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 2]]),
        ])->assertSessionHasNoErrors();

        $unit = RentalOrderUnit::firstOrFail();
        $order = $unit->order;

        // Tanpa voucher: baris tetap tampil (dulu tidak sengaja terkunci oleh
        // blok potongan), dan tulisan jumlahnya baku "Nama × 2".
        $this->assertSame(0, $unit->totalDiscount());
        $this->assertSame('Lilin × 2', $unit->addons->first()->label());

        $this->actingAs($order->customer)
            ->get(route('rental.orders.unit', [$order, $unit]))
            ->assertOk()
            ->assertSee('Item tambahan')
            ->assertSee('Lilin × 2')
            ->assertSee('Rp 24.000');

        // Jumlah 1 ditulis tanpa pengali.
        $unit->addons()->first()->update(['quantity' => 1]);
        $this->assertSame('Lilin', $unit->addons()->first()->label());
    }

    public function test_item_tambahan_nonaktif_dan_jumlah_berlebih_ditolak(): void
    {
        $product = $this->product(100000);
        $addon = RentalAddon::create(['name' => 'Lilin', 'price' => 12000, 'is_active' => false]);
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);

        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 1]]),
        ])->assertSessionHasErrors('units');

        $this->assertSame(0, RentalOrder::count());
        $this->assertSame(0, RentalOrderUnit::count());

        // Jumlah di atas batas 5 ditolak oleh validasi, bukan disimpan diam-diam.
        $aktif = RentalAddon::create(['name' => 'Kartu', 'price' => 5000]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$aktif->id => 6]]),
        ])->assertSessionHasErrors();
        $this->assertSame(0, RentalOrder::count());
    }

    public function test_item_tambahan_yang_sudah_dipakai_tidak_bisa_dihapus(): void
    {
        $product = $this->product(100000);
        $addon = RentalAddon::create(['name' => 'Lilin', 'price' => 12000]);
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 1]]),
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin())->delete(route('admin.rental.addons.destroy', $addon))
            ->assertSessionHasErrors('addon');
        $this->assertDatabaseHas('rental_addons', ['id' => $addon->id]);

        // Menonaktifkan tetap boleh: item hilang dari checkout baru, salinan pesanan utuh.
        $this->patch(route('admin.rental.addons.toggle', $addon))->assertSessionHasNoErrors();
        $this->assertFalse($addon->fresh()->is_active);
        $this->assertSame(1, RentalOrderUnit::firstOrFail()->addons()->count());
    }

    public function test_foto_referensi_disimpan_privat_dan_dibatasi_lima(): void
    {
        Storage::fake('local');

        $product = $this->product(100000);
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);

        // Enam foto melebihi batas.
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product),
            'reference_images' => array_map(fn ($i) => UploadedFile::fake()->image("contoh-{$i}.jpg", 800, 600), range(1, 6)),
        ])->assertSessionHasErrors('reference_images');
        $this->assertSame(0, RentalOrder::count());

        // Dua foto lolos dan tersimpan di disk privat.
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product),
            'reference_images' => [
                UploadedFile::fake()->image('contoh-1.jpg', 800, 600),
                UploadedFile::fake()->image('contoh-2.png', 640, 480),
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $order = RentalOrder::firstOrFail();
        $this->assertSame(2, $order->references()->count());
        foreach ($order->references as $reference) {
            Storage::disk('local')->assertExists($reference->image_path);
            $this->assertStringStartsWith('rental-references/', $reference->image_path);
            $this->assertSame($customer->id, $reference->uploaded_by);
        }
    }

    public function test_foto_referensi_hanya_bisa_dibuka_pemilik_dan_admin(): void
    {
        Storage::fake('local');

        $product = $this->product(100000);
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product),
            'reference_images' => [UploadedFile::fake()->image('contoh.jpg', 800, 600)],
        ])->assertSessionHasNoErrors();

        $reference = RentalOrderReference::firstOrFail();

        $this->actingAs($customer)->get(route('rental.reference.image', $reference))->assertOk();
        $this->actingAs($this->admin())->get(route('rental.reference.image', $reference))->assertOk();
        $this->actingAs($this->customer())->get(route('rental.reference.image', $reference))->assertForbidden();
    }

    public function test_halaman_detail_menampilkan_item_tambahan_dan_foto_referensi(): void
    {
        Storage::fake('local');

        $product = $this->product(100000);
        $addon = RentalAddon::create(['name' => 'Pita emas', 'price' => 7000]);
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->post(route('rental.checkout.store'), [
            'terms' => '1',
            'units' => $this->unitPayload($product, ['addons' => [$addon->id => 3]]),
            'reference_images' => [UploadedFile::fake()->image('contoh.jpg', 800, 600)],
        ])->assertSessionHasNoErrors();

        $order = RentalOrder::firstOrFail();
        $this->get(route('rental.orders.show', $order))->assertOk()
            ->assertSee('Foto referensi desain')
            ->assertSee('Pita emas')
            ->assertSee('Rp 21.000');

        $this->actingAs($this->admin())->get(route('admin.rental.orders.show', $order))->assertOk()
            ->assertSee('Foto referensi desain')
            ->assertSee('Pita emas × 3');
    }
}
