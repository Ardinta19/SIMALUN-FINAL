<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Tests\TestCase;

/**
 * Akun demo tidak boleh lahir di server produksi.
 *
 * `UserSeeder` membuat `admin@test.com` dengan sandi `password123` — nyaman
 * untuk pengembangan, tetapi berbahaya bila `php artisan migrate --seed`
 * dijalankan di server sungguhan: siapa pun yang membaca repositori tahu
 * kredensial admin tersebut. Seeder kini hanya berjalan di `local` dan
 * `testing`; akun produksi dibuat lewat `php artisan user:create`.
 */
class UserSeederTest extends TestCase
{
    public function test_seeder_menolak_berjalan_di_luar_local_dan_testing(): void
    {
        $berkas = (string) file_get_contents(base_path('database/seeders/UserSeeder.php'));

        $this->assertStringContainsString("app()->environment(['local', 'testing'])", $berkas);
        $this->assertStringContainsString('user:create', $berkas);
        $this->assertStringContainsString('return;', $berkas);
    }

    public function test_seeder_tidak_membuat_akun_saat_lingkungan_produksi(): void
    {
        $this->app['env'] = 'production';

        $this->seed(UserSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin@test.com']);
        $this->assertDatabaseMissing('users', ['email' => 'customer@test.com']);
    }
}
