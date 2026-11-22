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
