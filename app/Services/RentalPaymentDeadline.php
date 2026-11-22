<?php

namespace App\Services;

use App\Models\RentalOrderUnit;

class RentalPaymentDeadline
{
    // Exclusive upper bound: midnight on the day of receipt, in WIB.
    public function cutoff(RentalOrderUnit $unit): \Carbon\CarbonImmutable
    {
        return $unit->plannedPaymentDeadline()->addSecond();
    }

    public function timelyCoverage(RentalOrderUnit $unit): int
    {
        $cutoff = $this->cutoff($unit)->setTimezone(config('app.timezone'));
        $paid = $unit->payments()->whereIn('status', ['pending', 'verified'])->where('submitted_at', '<', $cutoff)->sum('amount');
        return max(0, (int) $paid - (int) $unit->refunds()->sum('rental_payment_refunds.amount'));
    }

    public function isDue(RentalOrderUnit $unit): bool { return now()->greaterThanOrEqualTo($this->cutoff($unit)); }

    public function eligibleForLatePayment(RentalOrderUnit $unit): bool
    {
        return $unit->status === 'cancelled' && $unit->cancel_code === 'payment_timeout'
            && ! $unit->deposit_forfeited_at && now('Asia/Jakarta')->toDateString() === $unit->starts_on->toDateString();
    }
}
