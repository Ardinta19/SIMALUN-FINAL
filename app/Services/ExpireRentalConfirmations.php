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
                        $unit->cancellationEvents()->create(['action' => 'cancel', 'previous_status' => 'pending', 'new_status' => 'cancelled', 'note' => 'Batas konfirmasi admin 24 jam terlewati.', 'responsibility' => 'seller']);
                        $unit->update(['status_before_cancel' => 'pending', 'cancel_code' => 'confirmation_timeout', 'status' => 'cancelled', 'cancel_reason' => 'Dibatalkan otomatis karena batas konfirmasi admin 24 jam terlewati.', 'cancel_responsibility' => 'seller', 'cancelled_at' => now()]);
                        return true;
                    }, 3);
                    if ($changed) $count++;
                }
            });
        return $count;
    }
}
