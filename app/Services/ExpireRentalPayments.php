<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Illuminate\Support\Facades\DB;

class ExpireRentalPayments
{
    public function run(): array
    {
        $result = ['cancelled' => 0, 'forfeited' => 0];
        RentalOrderUnit::whereIn('status', ['confirmed', 'cancelled'])->whereDate('starts_on', '<=', now('Asia/Jakarta')->toDateString())
            ->chunkById(100, function ($units) use (&$result) {
                foreach ($units as $candidate) {
                    $change = DB::transaction(function () use ($candidate) {
                        RentalProduct::lockForUpdate()->findOrFail($candidate->rental_product_id);
                        $unit = RentalOrderUnit::lockForUpdate()->findOrFail($candidate->id);
                        $policy = app(RentalPaymentDeadline::class);
                        if ($unit->status === 'cancelled' && $unit->cancel_code === 'payment_timeout' && ! $unit->deposit_forfeited_at && now('Asia/Jakarta')->toDateString() > $unit->starts_on->toDateString()) {
                            $unit->update(['deposit_forfeited_at' => now()]);
                            return 'forfeited';
                        }
                        if ($unit->status !== 'confirmed' || ! $policy->isDue($unit) || in_array($unit->progress, ['handed_over', 'returned'], true)) return null;
                        if ($unit->payment_late_resumed_at && $unit->payableAmount() !== null && $unit->verifiedNet() >= $unit->payableAmount()) return null;
                        // Unsettled shipping and specially approved same-day orders need a human decision.
                        if ($unit->payableAmount() === null || (! $unit->payment_review_resolved && $unit->special_confirmation && $unit->confirmed_at && $unit->confirmed_at->greaterThanOrEqualTo($policy->cutoff($unit)))) {
                            $unit->update(['payment_review_hold' => true]);
                            return null;
                        }
                        if ($unit->payment_review_hold || $policy->timelyCoverage($unit) >= $unit->payableAmount()) return null;
                        $reason = 'Dibatalkan otomatis karena pelunasan atau bukti pelunasan tepat waktu belum memenuhi tagihan.';
                        $unit->cancellationEvents()->create(['action' => 'cancel', 'previous_status' => 'confirmed', 'new_status' => 'cancelled', 'note' => $reason, 'responsibility' => 'customer']);
                        $unit->update(['status_before_cancel' => 'confirmed', 'status' => 'cancelled', 'cancel_code' => 'payment_timeout', 'cancel_reason' => $reason, 'cancel_responsibility' => 'customer', 'cancelled_at' => now(), 'deposit_forfeited_at' => now('Asia/Jakarta')->toDateString() > $unit->starts_on->toDateString() ? now() : null]);
                        app(RentalNotifications::class)->customer($unit->order, 'Tenggat pelunasan terlewati', 'Unit '.$unit->product_name.' dibatalkan. Periksa detail dan pilihan kelanjutan.');
                        return 'cancelled';
                    }, 3);
                    if ($change) $result[$change]++;
                }
            });
        return $result;
    }
}
