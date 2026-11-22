<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentalAvailability
{
    // This is an estimate unless the caller holds the product row lock for the transaction.
    public function minimumAvailable(RentalProduct $product, string $startsOn, string $endsOn): int
    {
        $overlaps = RentalOrderUnit::where('rental_product_id', $product->id)->where('status', 'confirmed')
            ->whereDate('starts_on', '<=', $endsOn)->whereDate('ends_on', '>=', $startsOn)->get(['starts_on', 'ends_on']);
        $events = [];
        foreach ($overlaps as $existing) {
            $start = max($existing->starts_on->toDateString(), $startsOn);
            $end = min($existing->ends_on->toDateString(), $endsOn);
            $events[$start] = ($events[$start] ?? 0) + 1;
            $after = CarbonImmutable::parse($end)->addDay()->toDateString();
            $events[$after] = ($events[$after] ?? 0) - 1;
        }
        ksort($events);
        $reserved = 0; $maximum = 0;
        foreach ($events as $change) {
            $reserved += $change;
            $maximum = max($maximum, $reserved);
        }
        return max(0, (int) $product->stock - $maximum);
    }

    // Caller holds the product row lock throughout the transaction.
    public function assertAvailable(RentalProduct $product, RentalOrderUnit $unit): void
    {
        if (! $product->is_active || ! $product->category->is_active) throw ValidationException::withMessages(['unit' => 'Papan tidak aktif.']);
        if ($this->minimumAvailable($product, $unit->starts_on->toDateString(), $unit->ends_on->toDateString()) < 1) {
            throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
        }
    }
}
