<?php

use Illuminate\Support\Facades\Artisan;

// Requires a running Laravel scheduler; execution is idempotent.
Artisan::command('rental:expire-confirmations', function (\App\Services\ExpireRentalConfirmations $expiry) {
    $this->info('Unit dibatalkan: '.$expiry->run());
})->purpose('Batalkan unit yang melewati batas konfirmasi admin');

\Illuminate\Support\Facades\Schedule::command('rental:expire-confirmations')->everyMinute()->withoutOverlapping();

Artisan::command('rental:expire-payments', function (\App\Services\ExpireRentalPayments $expiry) {
    $result = $expiry->run();
    $this->info('Unit dibatalkan: '.$result['cancelled'].'; masa kelanjutan berakhir: '.$result['forfeited']);
})->purpose('Proses tenggat pelunasan sewa');
\Illuminate\Support\Facades\Schedule::command('rental:expire-payments')->everyMinute()->withoutOverlapping();

// Pengingat dua arah: bukti bayar yang menganggur, pelunasan H-1, toleransi
// pembayaran terlambat (H+1 pukul 12.00 WIB), dan respons desain. Tidak mengubah
// status apa pun; hanya memberi tahu pihak yang seharusnya bergerak. Idempoten
// per unit+jenis+penerima+tanggal.
Artisan::command('rental:remind', function (\App\Services\RentalReminders $reminders) {
    $result = $reminders->run();
    $this->info('Pengingat terkirim: bukti='.$result['proof'].', pelunasan='.$result['due'].', toleransi='.$result['grace'].' (admin '.$result['grace_admin'].'), desain='.$result['design'].', eskalasi='.$result['design_escalation'].', kembali='.$result['return'].', ongkos_admin='.$result['transport_quote'].', ongkos_pelanggan='.$result['transport_approval'].', acara_lewat='.$result['event_overdue']);
})->purpose('Kirim pengingat bukti bayar, pelunasan, toleransi, desain, ongkos, acara lewat, dan pengembalian');

\Illuminate\Support\Facades\Schedule::command('rental:remind')->everyFiveMinutes()->withoutOverlapping();

// Penutupan otomatis unit yang telanjur tidak ditandai kembali oleh admin.
// Berjalan sekali sehari dini hari agar antrean "aktif" dan rating pelanggan
// tidak menggantung selamanya; setiap penutupan tercatat di riwayat progres
// sebagai tindakan Sistem dan memberi tahu pelanggan serta admin.
Artisan::command('rental:close-events', function (\App\Services\RentalAutoClose $closer) {
    $result = $closer->run();
    $this->info('Unit ditutup otomatis: '.$result['closed'].' (toleransi '.\App\Services\RentalAutoClose::GRACE_DAYS.' hari setelah tanggal kembali).');
})->purpose('Tutup unit yang tidak ditandai kembali setelah masa tenggang');

\Illuminate\Support\Facades\Schedule::command('rental:close-events')->dailyAt('02:00')->timezone('Asia/Jakarta')->withoutOverlapping();
