<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Edit data pengguna yang sudah ada (No. HP, nama, email, role, gender,
 * status aktif, password) secara interaktif dari terminal.
 *
 * Pelengkap `user:create` — berguna untuk memperbaiki akun admin/driver
 * (mis. salah ketik saat dibuat) tanpa harus utak-atik database langsung.
 *
 * Pemakaian:
 *   php artisan user:edit
 */
class EditUser extends Command
{
    protected $signature = 'user:edit';

    protected $description = 'Edit data pengguna yang sudah ada (No. HP, email, role, dll) secara interaktif';

    public function handle(): int
    {
        $this->info('=== Edit Akun Pengguna — Azka Laundry ===');

        $key = trim((string) $this->ask('Cari akun berdasarkan email / No. HP / ID'));
        if ($key === '') {
            $this->error('Input pencarian tidak boleh kosong.');

            return self::FAILURE;
        }

        // Normalisasi kemungkinan input HP supaya cocok dengan format DB.
        $phoneKey = preg_replace('/[\s\-\.]/', '', $key);
        $phoneKey = preg_replace('/^(\+?62|0)/', '', $phoneKey);

        $user = User::where('email', strtolower($key))
            ->orWhere('phone', $phoneKey)
            ->orWhere('id', is_numeric($key) ? (int) $key : 0)
            ->first();

        if (! $user) {
            $this->error('Akun tidak ditemukan untuk: '.$key);

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Akun ditemukan:');
        $this->table(
            ['ID', 'Nama', 'Email', 'No. HP', 'Role', 'Gender', 'Aktif'],
            [[$user->id, $user->name, $user->email ?? '-', $user->phone, $user->role, $user->gender ?? '-', $user->is_active ? 'ya' : 'tidak']]
        );
        $this->line('Tekan ENTER untuk mempertahankan nilai saat ini.');
        $this->newLine();

        // Setiap prompt memakai nilai sekarang sebagai default — ENTER = tetap.
        $name = (string) $this->ask('Nama', $user->name);

        $emailInput = trim((string) $this->ask('Email', $user->email ?? ''));
        $email = $emailInput !== '' ? strtolower($emailInput) : null;

        $phoneInput = (string) $this->ask('No. HP', $user->phone);
        $phone = preg_replace('/[\s\-\.]/', '', $phoneInput);
        $phone = preg_replace('/^(\+?62|0)/', '', $phone);

        $role = $this->choice('Role', ['customer', 'admin', 'driver'], $user->role);

        $genderDefault = $user->gender ?? '(lewati)';
        $gender = $this->choice('Jenis kelamin', ['L', 'P', '(lewati)'], $genderDefault);
        $gender = $gender === '(lewati)' ? null : $gender;

        $isActive = $this->choice('Status akun', ['aktif', 'nonaktif'], $user->is_active ? 'aktif' : 'nonaktif') === 'aktif';

        // Password opsional — hanya diubah kalau diminta.
        $newPassword = null;
        if ($this->confirm('Ganti password?', false)) {
            $newPassword = (string) $this->secret('Password baru (min. 8 karakter)');
            $confirm = (string) $this->secret('Ulangi password baru');
            if ($newPassword !== $confirm) {
                $this->error('Konfirmasi password tidak cocok.');

                return self::FAILURE;
            }
        }

        // Validasi — unique mengabaikan akun ini sendiri.
        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'nullable',
                'email:rfc',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^8[0-9]{8,12}$/',
                Rule::unique('users', 'phone')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'role' => ['required', 'in:customer,admin,driver'],
            'gender' => ['nullable', 'in:L,P'],
        ];
        $payload = compact('name', 'email', 'phone', 'role', 'gender');

        if ($newPassword !== null) {
            $rules['password'] = ['required', Password::min(8)];
            $payload['password'] = $newPassword;
        }

        $validator = Validator::make($payload, $rules, [
            'phone.regex' => 'Format No. HP tidak valid (setelah normalisasi harus seperti 81234567890).',
            'phone.unique' => 'No. HP sudah dipakai akun lain.',
            'email.unique' => 'Email sudah dipakai akun lain.',
        ]);

        if ($validator->fails()) {
            $this->error('Gagal menyimpan perubahan:');
            foreach ($validator->errors()->all() as $err) {
                $this->line('  - '.$err);
            }

            return self::FAILURE;
        }

        // Field non-sensitif via fill; role & is_active via property
        // assignment (konsisten pola anti privilege-escalation).
        $user->fill([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'gender' => $gender,
        ]);
        $user->role = $role;
        $user->is_active = $isActive;
        if ($newPassword !== null) {
            $user->password = Hash::make($newPassword);
        }
        $user->save();

        $this->newLine();
        $this->info('Perubahan tersimpan:');
        $this->table(
            ['ID', 'Nama', 'Email', 'No. HP', 'Role', 'Gender', 'Aktif'],
            [[$user->id, $user->name, $user->email ?? '-', $user->phone, $user->role, $user->gender ?? '-', $user->is_active ? 'ya' : 'tidak']]
        );

        return self::SUCCESS;
    }
}
