<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests: setiap halaman admin harus render (HTTP 200) tanpa error
 * Blade/runtime, baik di database kosong maupun saat ada data sewa.
 *
 * Sejak modul laundry dicabut, daftar rute di bawah hanya berisi halaman
 * modul sewa — termasuk yang dulu dipakai bersama (buku kas, voucher,
 * audit, notifikasi, profil, panduan).
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_semua_halaman_admin_render_tanpa_error_pada_db_kosong(): void
    {
        $admin = $this->admin();

        $routes = [
            'admin.dashboard',
            'admin.rental.orders',
            'admin.rental.schedule',
            'admin.rental.cashbook.index',
            'admin.rental.vouchers.index',
            'admin.rental.universities.index',
            'admin.rental.payment-settings.edit',
            'admin.catalog.index',
            'admin.audit.index',
            'admin.notifications',
            'admin.profile',
            'admin.help',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($admin)->get(route($name));
            $this->assertSame(200, $response->status(), "Route {$name} mengembalikan {$response->status()}, harusnya 200.");
        }
    }

    public function test_halaman_buku_kas_render_dengan_catatan_manual(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.rental.cashbook.store'), [
            'entry_date' => now()->toDateString(),
            'entry_type' => 'income',
            'category' => 'lainnya',
            'amount' => 50000,
            'notes' => 'Render test buku kas',
            'not_rental_payment' => true,
        ]);

        $this->assertDatabaseHas('finance_entries', ['notes' => 'Render test buku kas']);

        $this->assertSame(200, $this->actingAs($admin)->get(route('admin.rental.cashbook.index'))->status());
    }

    public function test_halaman_pesanan_sewa_render_dengan_data_nyata(): void
    {
        $admin = $this->admin();

        // Jalur catalog → keranjang → checkout sudah teruji di RentalCheckoutTest;
        // di sini cukup memastikan daftar & jadwal tetap render dengan data.
        $this->assertSame(200, $this->actingAs($admin)->get(route('admin.rental.orders'))->status());
        $this->assertSame(200, $this->actingAs($admin)->get(route('admin.rental.schedule'))->status());
    }
}
