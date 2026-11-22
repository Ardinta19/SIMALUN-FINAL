<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use Illuminate\Support\Facades\DB;

class ExpireRentalConfirmations
{
    public function run(): int
    {
        $count = 0;
        RentalOrderUnit::query()->where('status', 'pending')
            ->whereHas('order', fn ($query) => $query->where('confirmation_deadline', '<=', now()))
            ->chunkById(100, function ($units) use (&$count) {
                foreach ($units as $candidate) {
                    $changed = DB::transaction(function () use ($candidate) {
                        $unit = RentalOrderUnit::lockForUpdate()->find($candidate->id);
                        if (! $unit || $unit->status !== 'pending' || $unit->order->confirmation_deadline->isFuture()) return false;
                        $unit->update(['status' => 'cancelled', 'cancel_reason' => 'Dibatalkan otomatis karena batas konfirmasi admin 24 jam terlewati.', 'cancel_responsibility' => 'seller', 'cancelled_at' => now()]);
                        return true;
                    }, 3);
                    if ($changed) $count++;
                }
            });
        return $count;
    }
}
