<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Membersihkan pemberitahuan lama.
 *
 * Setiap perubahan status pesanan menulis satu baris `notifications` untuk
 * pelanggan dan satu untuk tiap admin (konfirmasi, pembayaran, desain,
 * progres, pengingat). Daftar notifikasi di layar hanya menampilkan 20 baris
 * terakhir, tetapi tabelnya terus bertambah — pada akhir tahun isinya puluhan
 * ribu baris yang tidak pernah dibuka lagi dan ikut tersalin setiap kali
 * basis data di-backup.
 *
 * Yang dihapus hanya pemberitahuan yang **sudah dibaca** dan lebih tua dari
 * batas hari; yang belum dibaca tidak pernah disentuh, sekalipun berumur
 * setahun.
 */
class PruneNotifications extends Command
{
    protected $signature = 'rental:prune-notifications {--days=90 : Umur minimum pemberitahuan yang sudah dibaca}';

    protected $description = 'Hapus pemberitahuan yang sudah dibaca dan lebih tua dari batas hari';

    public function handle(): int
    {
        $hari = (int) $this->option('days');

        if ($hari < 30) {
            $this->error('Batas hari minimal 30 supaya riwayat terbaru tidak ikut terhapus.');

            return self::FAILURE;
        }

        $batas = now()->subDays($hari);

        $terhapus = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', $batas)
            ->delete();

        $this->info('Pemberitahuan dibaca yang dihapus: '.$terhapus.' (lebih tua dari '.$hari.' hari).');

        return self::SUCCESS;
    }
}
