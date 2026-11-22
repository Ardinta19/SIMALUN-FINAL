<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentalAvailability
{
    // Caller holds the product row lock throughout the transaction.
    public function assertAvailable(RentalProduct $product, RentalOrderUnit $unit): void
    {
        if (! $product->is_active || ! $product->category->is_active) throw ValidationException::withMessages(['unit' => 'Papan tidak aktif.']);
        $overlaps = RentalOrderUnit::where('rental_product_id', $product->id)->where('status', 'confirmed')
            ->whereDate('starts_on', '<=', $unit->ends_on->toDateString())->whereDate('ends_on', '>=', $unit->starts_on->toDateString())->get(['starts_on', 'ends_on']);
        $events = [];
        foreach ($overlaps as $existing) {
            $start = max($existing->starts_on->toDateString(), $unit->starts_on->toDateString());
            $end = min($existing->ends_on->toDateString(), $unit->ends_on->toDateString());
            $events[$start] = ($events[$start] ?? 0) + 1;
            $after = CarbonImmutable::parse($end)->addDay()->toDateString();
            $events[$after] = ($events[$after] ?? 0) - 1;
        }
        ksort($events);
        $reserved = 0;
        foreach ($events as $change) {
            $reserved += $change;
            if ($reserved >= $product->stock) throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
        }
        if ($product->stock < 1) throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
    }
}
