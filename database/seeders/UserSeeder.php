<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo memakai sandi yang tertulis di kode. Menjalankannya di
        // server produksi berarti membuat akun admin dengan sandi yang bisa
        // ditebak siapa pun yang pernah membaca repositori ini. Karena itu
        // seeder hanya berjalan di lingkungan lokal/pengujian, dan akun
        // produksi dibuat lewat `php artisan user:create`.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Seeder akun demo dilewati: hanya untuk lingkungan local/testing. Pakai php artisan user:create untuk akun sungguhan.');

            return;
        }

        // Catatan: $role di-set lewat property assignment, bukan mass-fill,
        // karena `role` sudah dikeluarkan dari User::$fillable (anti
        // privilege escalation). Pattern ini dipakai di seluruh codebase
        // — RegisterController, register & seeder ini.
        $this->buatUser('Test Customer', 'customer@test.com', '081234567890', 'customer');
        $this->buatUser('Test Admin', 'admin@test.com', '081234567891', 'admin');
    }

    private function buatUser(string $name, string $email, string $phone, string $role): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password123'),
        ]);
        $user->role = $role;
        $user->save();

        return $user;
    }
}
