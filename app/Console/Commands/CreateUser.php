<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Buat akun pengguna (terutama admin & driver) secara aman dari terminal.
 *
 * Kenapa lewat command, bukan form publik?
 *  - Role admin/driver sengaja TIDAK punya pendaftaran publik (anti
 *    privilege escalation). Role di-set lewat property assignment,
 *    konsisten dengan RegisterController & UserSeeder.
 *  - Email diisi dengan Gmail asli orangnya supaya bisa dipakai untuk
 *    login Google (sign-in only) nanti.
 *
 * Pemakaian:
 *   php artisan user:create
 */
class CreateUser extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Buat akun pengguna baru (customer/admin/driver) secara interaktif';

    public function handle(): int
    {
        $this->info('=== Buat Akun Pengguna Baru — Azka Laundry ===');

        $name = (string) $this->ask('Nama lengkap');

        $email = trim((string) $this->ask('Email (Gmail asli, dipakai untuk login Google)'));
        $email = $email !== '' ? strtolower($email) : null;

        // Normalisasi HP ke format kanonik DB: 8xxxxxxxxxx (sama seperti
        // RegisterController & LoginController).
        $phoneRaw = (string) $this->ask('No. HP (contoh: 081234567890)');
        $phone = preg_replace('/[\s\-\.]/', '', $phoneRaw);
        $phone = preg_replace('/^(\+?62|0)/', '', $phone);

        $role = $this->choice('Role', ['customer', 'admin', 'driver'], 'admin');

        $gender = $this->choice('Jenis kelamin', ['L', 'P', '(lewati)'], '(lewati)');
        $gender = $gender === '(lewati)' ? null : $gender;

        $password = (string) $this->secret('Password (min. 8 karakter)');
        $passwordConfirm = (string) $this->secret('Ulangi password');

        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'gender' => $gender,
            'password' => $password,
            'password_confirmation' => $passwordConfirm,
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'nullable',
                'email:rfc',
                'max:150',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^8[0-9]{8,12}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ],
            'role' => ['required', 'in:customer,admin,driver'],
            'gender' => ['nullable', 'in:L,P'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.regex' => 'Format No. HP tidak valid (setelah normalisasi harus seperti 81234567890).',
            'phone.unique' => 'No. HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        if ($validator->fails()) {
            $this->error('Gagal membuat akun:');
            foreach ($validator->errors()->all() as $err) {
                $this->line('  - '.$err);
            }

            return self::FAILURE;
        }

        // Role di-set lewat property assignment, BUKAN mass-fill — supaya
        // konsisten dengan pola anti privilege-escalation di seluruh app.
        $user = new User([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'gender' => $gender,
            'password' => Hash::make($password),
        ]);
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        $this->newLine();
        $this->info('Akun berhasil dibuat:');
        $this->table(
            ['ID', 'Nama', 'Email', 'No. HP', 'Role', 'Aktif'],
            [[$user->id, $user->name, $user->email ?? '-', $user->phone, $user->role, $user->is_active ? 'ya' : 'tidak']]
        );

        return self::SUCCESS;
    }
}
