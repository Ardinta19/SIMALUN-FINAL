<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Memulihkan akun yang pernah dihapus pelanggan.
 *
 * Penghapusan akun memakai *soft delete*: baris pengguna tetap ada supaya
 * riwayat pesanan dan pembayaran tidak ikut hilang, tetapi tidak bisa masuk
 * lagi. Nomor HP dan emailnya juga masih menempati aturan unik, sehingga
 * pelanggan yang ingin kembali tidak dapat mendaftar ulang dan halaman daftar
 * menyuruhnya menghubungi admin. Perintah ini yang dimaksud "dihubungi admin".
 *
 * Pemakaian:
 *   php artisan user:restore
 *   php artisan user:restore 081234567890
 */
class RestoreUser extends Command
{
    protected $signature = 'user:restore {identifier? : Email, nomor HP, atau ID akun yang dihapus}';

    protected $description = 'Pulihkan akun pelanggan yang sudah dihapus (nomor HP/email/ID)';

    public function handle(): int
    {
        $key = trim((string) ($this->argument('identifier') ?? $this->ask('Cari akun terhapus berdasarkan email / nomor HP / ID')));

        if ($key === '') {
            $this->error('Input pencarian tidak boleh kosong.');

            return self::FAILURE;
        }

        // Normalisasi nomor HP dengan aturan yang sama seperti pendaftaran.
        $phoneKey = preg_replace('/[\s\-\.]/', '', $key);
        $phoneKey = preg_replace('/^(\+?62|0)/', '', (string) $phoneKey);

        // Kelompokkan syarat pencarian: tanpa grup, cabang orWhere melepas
        // syarat onlyTrashed() sehingga akun aktif bisa ikut terpilih.
        $terhapus = User::onlyTrashed()->where(function ($query) use ($key, $phoneKey) {
            $query->where('email', strtolower($key))
                ->orWhere('phone', $phoneKey)
                ->orWhere('id', is_numeric($key) ? (int) $key : 0);
        })->first();

        if (! $terhapus) {
            $aktif = User::where(function ($query) use ($key, $phoneKey) {
                $query->where('email', strtolower($key))->orWhere('phone', $phoneKey);
            })->first();
            $this->error($aktif
                ? 'Akun dengan data itu masih aktif; tidak ada yang perlu dipulihkan.'
                : 'Tidak ada akun terhapus untuk: '.$key);

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Akun terhapus ditemukan:');
        $this->table(
            ['ID', 'Nama', 'Email', 'Nomor HP', 'Role', 'Dihapus'],
            [[$terhapus->id, $terhapus->name, $terhapus->email ?? '-', $terhapus->phone, $terhapus->role, optional($terhapus->deleted_at)->format('d/m/Y H:i') ?? '-']]
        );

        if (! $this->confirm('Pulihkan akun ini? Riwayat pesanannya akan terlihat lagi oleh pelanggan.', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $terhapus->restore();

        $this->info('Akun dipulihkan. Pelanggan dapat masuk lagi dengan nomor HP atau email yang sama.');

        return self::SUCCESS;
    }
}
