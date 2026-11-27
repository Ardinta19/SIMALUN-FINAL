<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Indeks unik kolom `phone` dan `email` di tabel `users` berlaku untuk
 * **semua** baris, termasuk akun yang sudah soft delete. Aturan validasi
 * `user:create` dan `user:edit` pernah memakai `whereNull('deleted_at')`,
 * sehingga nomor milik akun terhapus lolos validasi lalu gagal di tingkat
 * SQL dengan tumpukan galat yang tidak bisa dibaca operator.
 */
class UserCommandValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_aturan_unik_perintah_tidak_mengabaikan_akun_terhapus(): void
    {
        foreach (['CreateUser.php', 'EditUser.php'] as $berkas) {
            $isi = File::get(app_path('Console/Commands/'.$berkas));

            $this->assertStringNotContainsString(
                "whereNull('deleted_at')",
                $isi,
                $berkas.' tidak boleh mengecualikan akun terhapus pada aturan unik.'
            );
        }
    }

    public function test_pesan_galat_perintah_menyebut_pemulihan_akun(): void
    {
        foreach (['CreateUser.php', 'EditUser.php'] as $berkas) {
            $isi = File::get(app_path('Console/Commands/'.$berkas));

            $this->assertStringContainsString('user:restore', $isi, $berkas.' harus menyarankan user:restore.');
        }
    }

    /**
     * Bukti bahwa pembatasan di basis data memang tidak membedakan akun
     * terhapus — jadi aturan validasi harus ikut.
     */
    public function test_indeks_unik_tetap_menahan_nomor_milik_akun_terhapus(): void
    {
        $lama = User::factory()->create(['phone' => '81234567890']);
        $lama->delete();

        $this->expectException(QueryException::class);

        User::factory()->create(['phone' => '81234567890']);
    }
}
