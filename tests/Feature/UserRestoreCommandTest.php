<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Akun yang dihapus pelanggan disimpan (soft delete) agar riwayat pesanan dan
 * pembayaran tetap utuh, tetapi nomor HP/emailnya menempati aturan unik
 * sehingga pendaftaran ulang ditolak. Perintah `user:restore` adalah jalan
 * keluar yang ditawarkan halaman daftar kepada pelanggan.
 */
class UserRestoreCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_terhapus_dapat_dipulihkan_lewat_nomor_hp(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => '81234567890']);
        $user->delete();
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->artisan('user:restore', ['identifier' => '081234567890'])
            ->expectsConfirmation('Pulihkan akun ini? Riwayat pesanannya akan terlihat lagi oleh pelanggan.', 'yes')
            ->assertExitCode(0);

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_akun_yang_masih_aktif_tidak_dipulihkan(): void
    {
        User::factory()->create(['role' => 'customer', 'phone' => '81234567891']);

        $this->artisan('user:restore', ['identifier' => '81234567891'])
            ->expectsOutput('Akun dengan data itu masih aktif; tidak ada yang perlu dipulihkan.')
            ->assertExitCode(1);
    }

    public function test_label_terminal_berbahasa_indonesia(): void
    {
        foreach (['CreateUser', 'EditUser', 'RestoreUser'] as $nama) {
            $berkas = (string) file_get_contents(app_path('Console/Commands/'.$nama.'.php'));
            $this->assertStringNotContainsString("'Role'", $berkas, $nama.' masih memakai label "Role".');
            $this->assertStringNotContainsString("'Gender'", $berkas, $nama.' masih memakai label "Gender".');
        }

        $this->assertStringContainsString("'Peran akun'", (string) file_get_contents(app_path('Console/Commands/CreateUser.php')));
    }
}
