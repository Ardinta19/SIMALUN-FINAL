<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
    $this->info('Pengingat terkirim: bukti='.$result['proof'].', pelunasan='.$result['due'].', toleransi='.$result['grace'].' (admin '.$result['grace_admin'].'), desain='.$result['design'].', eskalasi='.$result['design_escalation']);
})->purpose('Kirim pengingat bukti bayar, pelunasan H-1, dan respons desain');

\Illuminate\Support\Facades\Schedule::command('rental:remind')->everyFiveMinutes()->withoutOverlapping();
