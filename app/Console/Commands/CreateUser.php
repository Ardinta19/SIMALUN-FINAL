<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Buat akun pengguna (terutama admin) secara aman dari terminal.
 *
 * Kenapa lewat command, bukan form publik?
 *  - Role admin sengaja TIDAK punya pendaftaran publik (anti
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

    protected $description = 'Buat akun pengguna baru (customer/admin) secara interaktif';

    public function handle(): int
    {
        $this->info('=== Buat Akun Pengguna Baru — ' . \App\Support\Laundry::name() . ' ===');

        $name = (string) $this->ask('Nama lengkap');

        $email = trim((string) $this->ask('Email (Gmail asli, dipakai untuk login Google)'));
        $email = $email !== '' ? strtolower($email) : null;

        // Normalisasi HP ke format kanonik DB: 8xxxxxxxxxx (sama seperti
        // RegisterController & LoginController).
        $phoneRaw = (string) $this->ask('Nomor HP (contoh: 0812 3456 7890)');
        $phone = preg_replace('/[\s\-\.]/', '', $phoneRaw);
        $phone = preg_replace('/^(\+?62|0)/', '', $phone);

        $role = $this->choice('Peran akun', ['customer', 'admin'], 'admin');

        $gender = $this->choice('Jenis kelamin', ['L', 'P', '(lewati)'], '(lewati)');
        $gender = $gender === '(lewati)' ? null : $gender;

        $password = (string) $this->secret('Kata sandi (min. 8 karakter)');
        $passwordConfirm = (string) $this->secret('Ulangi kata sandi');

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
                // Indeks unik di tabel users tidak membedakan akun terhapus,
                // jadi aturan validasi ini juga tidak boleh mengecualikannya —
                // operator harus mendapat pesan jelas, bukan galat SQL.
                Rule::unique('users', 'email'),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^8[0-9]{8,12}$/',
                Rule::unique('users', 'phone'),
            ],
            'role' => ['required', 'in:customer,admin'],
            'gender' => ['nullable', 'in:L,P'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.regex' => 'Format nomor HP tidak valid (setelah normalisasi harus seperti 81234567890).',
            'phone.unique' => 'Nomor HP sudah dipakai, termasuk oleh akun yang sudah dihapus. Pulihkan akunnya lewat php artisan user:restore, lalu coba lagi.',
            'email.unique' => 'Email sudah dipakai, termasuk oleh akun yang sudah dihapus. Pulihkan akunnya lewat php artisan user:restore, lalu coba lagi.',
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
            ['ID', 'Nama', 'Email', 'Nomor HP', 'Peran', 'Aktif'],
            [[$user->id, $user->name, $user->email ?? '-', $user->phone, $user->role, $user->is_active ? 'ya' : 'tidak']]
        );

        return self::SUCCESS;
    }
}
