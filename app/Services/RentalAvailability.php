<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentalAvailability
{
    /**
     * Jendela pemesanan: hari ini sampai hari ini + 365 (satu tahun).
     *
     * Satu angka ini dipakai untuk tiga hal yang harus selalu sama:
     * tanggal yang ditandai penuh di kalender, batas `max` input tanggal,
     * dan validasi server. Sebelumnya kalender hanya memeriksa 120 hari
     * tetapi server menerima tanggal jauh di luar itu, sehingga pesanan
     * berjarak lebih dari 120 hari tidak pernah tampil sebagai terblokir.
     */
    public const BOOKING_HORIZON_DAYS = 366;

    /** Hari pertama dan terakhir yang masih boleh dipesan. */
    public static function bookingWindow(): array
    {
        $from = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        return ['from' => $from, 'to' => $from->addDays(self::BOOKING_HORIZON_DAYS - 1)];
    }

    /**
     * Validasi rentang tanggal pesanan terhadap jendela pemesanan.
     *
     * $field dan $prefix disesuaikan dengan formulir pemanggil agar pesan
     * galat tetap menempel pada kolom yang benar (mis. "Unit 2: ...").
     */
    public static function assertBookablePeriod(string $startsOn, string $endsOn, string $field = 'starts_on', string $prefix = ''): void
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $startsOn, 'Asia/Jakarta');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $endsOn, 'Asia/Jakarta');
        $window = self::bookingWindow();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages([$field => $prefix.'tanggal pengembalian tidak boleh sebelum tanggal acara dan penerimaan.']);
        }
        if ($start->lessThan($window['from'])) {
            throw ValidationException::withMessages([$field => $prefix.'tanggal acara tidak boleh di masa lalu.']);
        }
        if ($start->greaterThan($window['to']) || $end->greaterThan($window['to'])) {
            throw ValidationException::withMessages([$field => $prefix.'tanggal pemesanan paling jauh '.self::BOOKING_HORIZON_DAYS.' hari dari hari ini.']);
        }
        if ($start->diffInDays($end) >= self::BOOKING_HORIZON_DAYS) {
            throw ValidationException::withMessages([$field => $prefix.'periode sewa maksimal '.self::BOOKING_HORIZON_DAYS.' hari.']);
        }
    }

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
            ->where('starts_on', '<=', $endsOn)->where('ends_on', '>=', $startsOn)
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
            ->where('starts_on', '<=', $endsOn)->where('ends_on', '>=', $startsOn)
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
        int $days = self::BOOKING_HORIZON_DAYS,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): array {
        $stock = (int) $product->stock;
        if ($stock < 1) return [];
        $from = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $to = $from->addDays(max(1, $days) - 1);
        $units = RentalOrderUnit::where('rental_product_id', $product->id)->whereIn('status', $statuses)
            ->where('starts_on', '<=', $to->toDateString())->where('ends_on', '>=', $from->toDateString())
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

    /**
     * Tanggal yang tidak bisa dipakai satu pun produk aktif.
     *
     * Dipakai input tanggal di halaman katalog: filter di sana mencari papan
     * mana saja yang tersedia, jadi tanggal baru layak diblokir bila semua
     * papan sudah penuh. Kalau masih ada satu papan kosong, tanggalnya tetap
     * bisa dipilih karena hasil filternya akan berisi papan itu.
     *
     * @param  \Illuminate\Support\Collection<int, RentalProduct>  $products
     * @return array<int, string> daftar tanggal Y-m-d yang tidak tersedia
     */
    public function datesWithNoAvailableProduct($products, int $days = self::BOOKING_HORIZON_DAYS): array
    {
        $products = $products->filter(fn ($product): bool => (int) $product->stock > 0);
        if ($products->isEmpty()) return [];
        $from = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $to = $from->addDays(max(1, $days) - 1);
        $units = RentalOrderUnit::whereIn('rental_product_id', $products->modelKeys())->whereIn('status', RentalOrderUnit::RESERVING_STATUSES)
            ->where('starts_on', '<=', $to->toDateString())->where('ends_on', '>=', $from->toDateString())
            ->get(['rental_product_id', 'starts_on', 'ends_on']);
        $used = [];
        foreach ($units as $unit) {
            $cursor = CarbonImmutable::parse(max($unit->starts_on->toDateString(), $from->toDateString()));
            $last = CarbonImmutable::parse(min($unit->ends_on->toDateString(), $to->toDateString()));
            while ($cursor->lessThanOrEqualTo($last)) {
                $date = $cursor->toDateString();
                $used[$unit->rental_product_id][$date] = ($used[$unit->rental_product_id][$date] ?? 0) + 1;
                $cursor = $cursor->addDay();
            }
        }
        $stocks = $products->mapWithKeys(fn ($product): array => [$product->id => (int) $product->stock]);
        $result = [];
        for ($cursor = $from; $cursor->lessThanOrEqualTo($to); $cursor = $cursor->addDay()) {
            $date = $cursor->toDateString();
            $allFull = true;
            foreach ($stocks as $productId => $stock) {
                if (($used[$productId][$date] ?? 0) < $stock) {
                    $allFull = false;
                    break;
                }
            }
            if ($allFull) $result[] = $date;
        }
        return $result;
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
            ->where('starts_on', '<=', max($ends))->where('ends_on', '>=', min($starts))
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
