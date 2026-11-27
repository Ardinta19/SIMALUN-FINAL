<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Menutup unit yang sudah diserahkan tetapi tidak pernah ditandai kembali.
 *
 * Tanpa penutupan, unit menggantung di `handed_over` selamanya: dasbor terus
 * menghitungnya sebagai aktif dan pelanggan tidak bisa memberi rating pesanan
 * karena rating menuntut seluruh unit selesai atau dibatalkan. Penutupan
 * otomatis baru dilakukan setelah masa tenggang yang lebar, dan setiap
 * penutupan dicatat di riwayat progres sebagai tindakan Sistem agar admin
 * tetap bisa menelusurinya.
 */
class RentalAutoClose
{
    /** Hari toleransi setelah tanggal kembali sebelum ditutup otomatis. */
    public const GRACE_DAYS = 7;

    /**
     * @return array{closed:int}
     */
    public function run(): array
    {
        $cutoff = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDays(self::GRACE_DAYS)->toDateString();
        $closed = 0;

        RentalOrderUnit::query()->with('order')
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->where('progress', 'handed_over')
            ->where('ends_on', '<=', $cutoff)
            ->chunkById(100, function ($units) use (&$closed) {
                foreach ($units as $unit) {
                    $didClose = DB::transaction(function () use ($unit) {
                        $fresh = RentalOrderUnit::lockForUpdate()->find($unit->id);
                        if (! $fresh || $fresh->status !== RentalOrderUnit::STATUS_CONFIRMED || $fresh->progress !== 'handed_over') {
                            return false;
                        }
                        // Kolom pencatat riwayat wajib berisi pengguna. Penutupan ini
                        // tindakan sistem, jadi dicatat atas nama admin terakhir yang
                        // menangani unit (atau admin aktif pertama) dan catatannya
                        // menyebut jelas bahwa ini penutupan otomatis.
                        $recorder = $fresh->progressEvents()->latest('id')->value('recorded_by')
                            ?? User::where('role', 'admin')->where('is_active', true)->value('id');
                        if (! $recorder) {
                            return false;
                        }
                        $fresh->update(['progress' => 'returned']);
                        $fresh->progressEvents()->create([
                            'progress' => 'returned',
                            'recorded_by' => $recorder,
                            'note' => 'Ditutup otomatis oleh sistem '.self::GRACE_DAYS.' hari setelah tanggal kembali karena belum ditandai admin.',
                        ]);

                        return true;
                    }, 3);

                    if (! $didClose) continue;

                    app(RentalNotifications::class)->customer(
                        $unit->order,
                        'Unit ditutup otomatis',
                        'Unit '.$unit->product_name.' otomatis ditandai sudah dikembalikan karena belum ditandai admin. Hubungi admin bila papan sebenarnya belum kembali.'
                    );
                    app(RentalNotifications::class)->admins(
                        $unit->order,
                        'Unit ditutup otomatis',
                        'Unit '.$unit->product_name.' melewati '.self::GRACE_DAYS.' hari dari tanggal kembali tanpa penandaan. Sistem menandainya sudah dikembalikan; periksa bila papan belum diterima.'
                    );
                    $closed++;
                }
            });

        return ['closed' => $closed];
    }
}
