<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentalAvailability
{
    // This is an estimate unless the caller holds the product row lock for the transaction.
    public function minimumAvailable(RentalProduct $product, string $startsOn, string $endsOn, bool $lock = false): int
    {
        $overlaps = RentalOrderUnit::where('rental_product_id', $product->id)->where('status', 'confirmed')
            ->whereDate('starts_on', '<=', $endsOn)->whereDate('ends_on', '>=', $startsOn)
            ->when($lock, fn ($query) => $query->lockForUpdate())->get(['starts_on', 'ends_on']);
        return $this->minimumFromOverlaps($product, $overlaps, $startsOn, $endsOn);
    }

    /** Estimates for catalogue search without issuing one query per product. */
    public function minimumAvailableForProducts($products, string $startsOn, string $endsOn): array
    {
        if ($products->isEmpty()) return [];
        $overlaps = RentalOrderUnit::whereIn('rental_product_id', $products->modelKeys())->where('status', 'confirmed')
            ->whereDate('starts_on', '<=', $endsOn)->whereDate('ends_on', '>=', $startsOn)
            ->get(['rental_product_id', 'starts_on', 'ends_on'])->groupBy('rental_product_id');
        $result = [];
        foreach ($products as $product) {
            $result[$product->id] = $this->minimumFromOverlaps($product, $overlaps->get($product->id, collect()), $startsOn, $endsOn);
        }
        return $result;
    }

    private function minimumFromOverlaps(RentalProduct $product, $overlaps, string $startsOn, string $endsOn): int
    {
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

    /** Periksa seluruh unit pada satu produk, termasuk overlap antarunit sendiri. */
    public function canFitRequested(RentalProduct $product, array $ranges, bool $lock = false): bool
    {
        if (! $ranges) return true;
        $starts = array_column($ranges, 'starts_on');
        $ends = array_column($ranges, 'ends_on');
        $existing = RentalOrderUnit::where('rental_product_id', $product->id)->where('status', 'confirmed')
            ->whereDate('starts_on', '<=', max($ends))->whereDate('ends_on', '>=', min($starts))
            ->when($lock, fn ($query) => $query->lockForUpdate())->get(['starts_on', 'ends_on']);
        $events = [];
        $add = function (string $date, int $reserved, int $requested) use (&$events) {
            $events[$date] ??= [0, 0];
            $events[$date][0] += $reserved;
            $events[$date][1] += $requested;
        };
        foreach ($existing as $unit) {
            $add($unit->starts_on->toDateString(), 1, 0);
            $add($unit->ends_on->copy()->addDay()->toDateString(), -1, 0);
        }
        foreach ($ranges as $range) {
            $add($range['starts_on'], 0, 1);
            $add(CarbonImmutable::parse($range['ends_on'])->addDay()->toDateString(), 0, -1);
        }
        ksort($events);
        $reserved = 0; $requested = 0;
        foreach ($events as [$r, $q]) {
            $reserved += $r; $requested += $q;
            if ($requested > 0 && $reserved + $requested > $product->stock) return false;
        }
        return true;
    }

    // Caller holds the product row lock throughout the transaction.
    public function assertAvailable(RentalProduct $product, RentalOrderUnit $unit): void
    {
        if (! $product->is_active || ! $product->category->is_active) throw ValidationException::withMessages(['unit' => 'Papan tidak aktif.']);
        if ($this->minimumAvailable($product, $unit->starts_on->toDateString(), $unit->ends_on->toDateString(), true) < 1) {
            throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
        }
    }
}
