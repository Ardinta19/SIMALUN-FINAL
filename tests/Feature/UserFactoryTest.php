<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Factory pengguna harus benar-benar membuat peran yang diminta.
 *
 * `User::$fillable` tidak memuat `role`, `is_active`, dan `email_verified_at`
 * (anti privilege escalation). Karena factory Laravel membangun model lewat
 * konstruktor — yang memakai mass assignment — atribut itu dibuang tanpa
 * pesan. Akibatnya `User::factory()->create(['role' => 'admin'])` menghasilkan
 * pelanggan, dan tes halaman admin berjalan dengan akun yang salah.
 * `UserFactory::newModel()` sekarang menerapkan ketiga kolom itu dengan
 * `forceFill`.
 *
 * Penjagaan anti privilege escalation untuk jalur aplikasi tetap diuji di
 * sini: `User::create(['role' => 'admin'])` harus tetap menghasilkan
 * pelanggan.
 */
class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_menghormati_peran_yang_diminta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertSame('admin', $admin->fresh()->role);

        $this->assertSame('admin', User::factory()->admin()->create()->fresh()->role);
        $this->assertSame('customer', User::factory()->customer()->create()->fresh()->role);
    }

    public function test_factory_menghormati_status_nonaktif_dan_email_belum_terverifikasi(): void
    {
        $nonaktif = User::factory()->create(['is_active' => false, 'email_verified_at' => null]);

        $this->assertFalse((bool) $nonaktif->fresh()->is_active);
        $this->assertNull($nonaktif->fresh()->email_verified_at);
    }

    public function test_mass_assignment_peran_tetap_ditolak_di_jalur_aplikasi(): void
    {
        $user = User::create([
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'phone' => '81200000001',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
        ]);

        $this->assertSame('customer', $user->fresh()->role);
    }
}
