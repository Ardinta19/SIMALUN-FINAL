<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentalAvailability
{
    /**
     * Perkiraan sisa unit pada rentang tanggal. Secara bawaan status yang
     * menahan stok ikut dihitung: pesanan pending sudah mengunci tanggal
     * sampai admin mengonfirmasi atau tenggatnya lewat.
     *
     * This is an estimate unless the caller holds the product row lock for the transaction.
     */
    public function minimumAvailable(
        RentalProduct $product,
        string $startsOn,
        string $endsOn,
        bool $lock = false,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): int {
        $overlaps = RentalOrderUnit::where('rental_product_id', $product->id)->whereIn('status', $statuses)
            ->whereDate('starts_on', '<=', $endsOn)->whereDate('ends_on', '>=', $startsOn)
            ->when($lock, fn ($query) => $query->lockForUpdate())->get(['starts_on', 'ends_on']);
        return $this->minimumFromOverlaps($product, $overlaps, $startsOn, $endsOn);
    }

    /** Estimates for catalogue search without issuing one query per product. */
    public function minimumAvailableForProducts(
        $products,
        string $startsOn,
        string $endsOn,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): array {
        if ($products->isEmpty()) return [];
        $overlaps = RentalOrderUnit::whereIn('rental_product_id', $products->modelKeys())->whereIn('status', $statuses)
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

    /**
     * Tanggal yang stoknya sudah habis (seluruh unit terpakai) dalam rentang
     * tertentu. Dipakai kalender pemesanan agar tanggal yang sudah dibooking
     * tidak bisa dipilih, bukan hanya ditolak saat dikirim.
     *
     * @return array<int, string> daftar tanggal Y-m-d yang tidak tersedia
     */
    public function fullyBookedDates(
        RentalProduct $product,
        int $days = 120,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): array {
        $stock = (int) $product->stock;
        if ($stock < 1) return [];
        $from = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $to = $from->addDays(max(1, $days) - 1);
        $units = RentalOrderUnit::where('rental_product_id', $product->id)->whereIn('status', $statuses)
            ->whereDate('starts_on', '<=', $to->toDateString())->whereDate('ends_on', '>=', $from->toDateString())
            ->get(['starts_on', 'ends_on']);
        $counts = [];
        foreach ($units as $unit) {
            $cursor = CarbonImmutable::parse(max($unit->starts_on->toDateString(), $from->toDateString()));
            $last = CarbonImmutable::parse(min($unit->ends_on->toDateString(), $to->toDateString()));
            while ($cursor->lessThanOrEqualTo($last)) {
                $key = $cursor->toDateString();
                $counts[$key] = ($counts[$key] ?? 0) + 1;
                $cursor = $cursor->addDay();
            }
        }
        $full = array_keys(array_filter($counts, fn (int $used): bool => $used >= $stock));
        sort($full);
        return $full;
    }

    /** Periksa seluruh unit pada satu produk, termasuk overlap antarunit sendiri. */
    public function canFitRequested(
        RentalProduct $product,
        array $ranges,
        bool $lock = false,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): bool {
        if (! $ranges) return true;
        $starts = array_column($ranges, 'starts_on');
        $ends = array_column($ranges, 'ends_on');
        $existing = RentalOrderUnit::where('rental_product_id', $product->id)->whereIn('status', $statuses)
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
        // Saat mengonfirmasi, yang dihitung hanya unit terkonfirmasi: unit
        // pending lain belum tentu jadi dan tidak boleh menggagalkan konfirmasi.
        if ($this->minimumAvailable(
            $product,
            $unit->starts_on->toDateString(),
            $unit->ends_on->toDateString(),
            true,
            RentalOrderUnit::COMMITTED_STATUSES
        ) < 1) {
            throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
        }
    }
}
