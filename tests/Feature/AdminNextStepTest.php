<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Putaran 43 — panel "Langkah berikutnya" di detail pesanan admin.
 *
 * Yang diuji adalah keadaan, bukan tata letak: admin harus melihat tombol yang
 * benar, dan tombol itu harus nonaktif ketika aturan server menolaknya. Bila
 * panel ini meleset, admin menekan tombol lalu mendapat galat — persis keluhan
 * "membingungkan" yang ingin dihilangkan.
 */
class AdminNextStepTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private RentalOrder $order;

    private array $attrs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role' => 'admin'])->save();

        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();

        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create([
            'rental_category_id' => $category->id,
            'name' => 'Papan Mawar',
            'base_price' => 100,
            'price_unit' => 'day',
            'stock' => 4,
        ]);

        $this->order = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'confirmation_deadline' => now()->addDay(),
        ]);

        $this->attrs = [
            'rental_product_id' => $product->id,
            'product_name' => 'Papan Mawar',
            'unit_price' => 100,
            'price_unit' => 'day',
            'rental_amount' => 100,
            'starts_on' => now()->addDays(3),
            'event_on' => now()->addDays(3),
            'ends_on' => now()->addDays(4),
            'recipient_name' => 'Penerima',
            'greeting' => 'Selamat',
            'delivery_method' => 'pickup',
            'return_method' => 'dropoff',
        ];
    }

    private function unit(array $ganti = []): RentalOrderUnit
    {
        return $this->order->units()->create(array_merge($this->attrs, $ganti));
    }

    public function test_unit_menunggu_konfirmasi_menampilkan_tombol_konfirmasi(): void
    {
        $this->unit(['status' => 'pending']);

        $this->actingAs($this->admin)->get(route('admin.rental.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Langkah berikutnya')
            ->assertSee('Konfirmasi unit')
            ->assertDontSee('Mulai pengerjaan')
            ->assertDontSee('Tandai siap diserahkan');
    }

    public function test_pengerjaan_tertahan_sampai_desain_disetujui(): void
    {
        $unit = $this->unit(['status' => 'confirmed', 'progress' => 'waiting']);

        $halaman = $this->actingAs($this->admin)->get(route('admin.rental.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Mulai pengerjaan')
            ->assertSee('Pratinjau desain belum disetujui pelanggan.')
            ->assertSee('Buka pratinjau desain');

        $halaman->assertSee('disabled aria-describedby="blocker-', false);

        // Setelah pelanggan menyetujui pratinjau, tombolnya aktif kembali.
        $unit->previews()->create([
            'version' => 1,
            'image_path' => 'contoh.jpg',
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->get(route('admin.rental.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Mulai pengerjaan')
            ->assertDontSee('Pratinjau desain belum disetujui pelanggan.')
            ->assertDontSee('disabled aria-describedby="blocker-', false);
    }

    public function test_serah_terima_menyebut_kekurangan_pembayaran(): void
    {
        $unit = $this->unit(['status' => 'confirmed', 'progress' => 'ready']);
        $unit->previews()->create([
            'version' => 1,
            'image_path' => 'contoh.jpg',
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->get(route('admin.rental.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Tandai sudah diserahkan')
            ->assertSee('Pembayaran belum lunas: kurang Rp 100')
            ->assertSee('Buka pembayaran')
            ->assertSee('disabled aria-describedby="blocker-', false);
    }

    public function test_unit_batal_menawarkan_satu_formulir_lanjutkan(): void
    {
        $unit = $this->unit([
            'status' => 'cancelled',
            'status_before_cancel' => 'confirmed',
            'cancel_code' => 'manual',
            'cancel_reason' => 'Pelanggan membatalkan lewat WhatsApp.',
            'cancelled_at' => now(),
        ]);

        $halaman = $this->actingAs($this->admin)->get(route('admin.rental.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Lanjutkan unit')
            ->assertSee('Langkah berikutnya');

        // Satu formulir saja: dulu muncul tiga kali di halaman yang sama.
        $this->assertSame(1, substr_count($halaman->getContent(), route('admin.rental.resume', $unit)));
    }

    public function test_unit_batal_lewat_batas_tidak_menawarkan_tombol(): void
    {
        $this->unit([
            'status' => 'cancelled',
            'status_before_cancel' => 'confirmed',
            'cancel_code' => 'manual',
            'cancel_reason' => 'Dibatalkan setelah acara lewat.',
            'cancelled_at' => now(),
            'starts_on' => now()->subDays(3),
            'event_on' => now()->subDays(3),
            'ends_on' => now()->subDays(2),
        ]);

        $this->actingAs($this->admin)->get(route('admin.rental.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Unit dibatalkan')
            ->assertDontSee('Lanjutkan unit')
            ->assertSee('Batas melanjutkan sudah terlewati');
    }

    public function test_daftar_pesanan_menampilkan_tugas_dan_keadaan_bersih(): void
    {
        // Pesanan A: satu unit menunggu konfirmasi → ada tugas.
        $this->unit(['status' => 'pending']);

        $this->actingAs($this->admin)->get(route('admin.rental.orders'))
            ->assertOk()
            ->assertSee('Konfirmasi Papan Mawar')
            ->assertSee('adm-tasklist', false);

        // Pesanan B: unit dikerjakan tanpa tunggakan → kartu berkata terus terang.
        $bersih = RentalOrder::create([
            'reference' => (string) Str::uuid(),
            'customer_id' => $this->order->customer_id,
            'confirmation_deadline' => now()->addDay(),
        ]);
        $bersih->units()->create(array_merge($this->attrs, ['status' => 'confirmed', 'progress' => 'production']));

        $this->actingAs($this->admin)->get(route('admin.rental.orders'))
            ->assertOk()
            ->assertSee('Tidak ada yang menunggu admin di pesanan ini.');
    }
}
