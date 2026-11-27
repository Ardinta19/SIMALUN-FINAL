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

    /* ── Stok per varian ────────────────────────────────────────────────────
       Papan tanpa varian tetap memakai `rental_products.stock`; papan
       bervarian memakai `rental_product_variants.stock` per warna. Kedua
       jalur dihitung terpisah supaya produk lama tidak berubah perilakunya
       hanya karena tabel varian ada. */

    /** @return array<int,int> id varian aktif => stok */
    public function variantStocks(RentalProduct $product): array
    {
        return $product->activeVariantList()
            ->mapWithKeys(fn ($variant) => [(int) $variant->id => (int) $variant->stock])
            ->all();
    }

    /** Stok satu varian; null bila varian tidak dikenal produk ini. */
    public function stockForVariant(RentalProduct $product, int $variantId): ?int
    {
        $stocks = $this->variantStocks($product);

        return $stocks[$variantId] ?? null;
    }

    /**
     * Perkiraan sisa unit pada rentang tanggal. Secara bawaan status yang
     * menahan stok ikut dihitung: pesanan pending sudah mengunci tanggal
     * sampai admin mengonfirmasi atau tenggatnya lewat.
     *
     * Untuk papan bervarian, hasilnya adalah sisa varian TERBAIK — katalog
     * hanya perlu tahu apakah masih ada warna yang bisa dipesan.
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
        $stocks = $this->variantStocks($product);

        if ($stocks) {
            $overlaps = $this->overlaps($product->id, $startsOn, $endsOn, $lock, $statuses, true);
            $best = 0;
            foreach ($stocks as $variantId => $stock) {
                $best = max($best, $this->minimumFromStock($stock, $overlaps->where('rental_product_variant_id', $variantId), $startsOn, $endsOn));
            }

            return $best;
        }

        $overlaps = $this->overlaps($product->id, $startsOn, $endsOn, $lock, $statuses);

        return $this->minimumFromStock((int) $product->stock, $overlaps, $startsOn, $endsOn);
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
            ->get(['rental_product_id', 'rental_product_variant_id', 'starts_on', 'ends_on'])->groupBy('rental_product_id');
        $result = [];
        foreach ($products as $product) {
            $rows = $overlaps->get($product->id, collect());
            $stocks = $product->activeVariantList()
                ->mapWithKeys(fn ($variant) => [(int) $variant->id => (int) $variant->stock])
                ->all();

            if (! $stocks) {
                $result[$product->id] = $this->minimumFromStock((int) $product->stock, $rows, $startsOn, $endsOn);
                continue;
            }

            $best = 0;
            foreach ($stocks as $variantId => $stock) {
                $best = max($best, $this->minimumFromStock($stock, $rows->where('rental_product_variant_id', $variantId), $startsOn, $endsOn));
            }
            $result[$product->id] = $best;
        }

        return $result;
    }

    private function minimumFromStock(int $stock, $overlaps, string $startsOn, string $endsOn): int
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

        return max(0, $stock - $maximum);
    }

    /** Unit yang menahan stok pada rentang tertentu. */
    private function overlaps(
        int $productId,
        string $startsOn,
        string $endsOn,
        bool $lock = false,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES,
        bool $withVariant = false
    ) {
        return RentalOrderUnit::where('rental_product_id', $productId)->whereIn('status', $statuses)
            ->where('starts_on', '<=', $endsOn)->where('ends_on', '>=', $startsOn)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get($withVariant ? ['rental_product_variant_id', 'starts_on', 'ends_on'] : ['starts_on', 'ends_on']);
    }

    /**
     * Tanggal yang stoknya sudah habis (seluruh unit terpakai) dalam rentang
     * tertentu. Dipakai kalender pemesanan agar tanggal yang sudah dibooking
     * tidak bisa dipilih, bukan hanya ditolak saat dikirim.
     *
     * Untuk papan bervarian, tanggal baru dianggap penuh bila SEMUA warna
     * sudah habis — selama satu warna masih ada, tanggalnya masih bisa dipilih.
     *
     * @return array<int, string> daftar tanggal Y-m-d yang tidak tersedia
     */
    public function fullyBookedDates(
        RentalProduct $product,
        int $days = self::BOOKING_HORIZON_DAYS,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): array {
        $stocks = $this->variantStocks($product);
        if ($stocks) {
            return $this->fullyBookedForVariants($product, $stocks, $days, $statuses);
        }

        $stock = (int) $product->stock;
        if ($stock < 1) return [];
        $range = $this->calendarRange($days);
        $units = RentalOrderUnit::where('rental_product_id', $product->id)->whereIn('status', $statuses)
            ->where('starts_on', '<=', $range['to']->toDateString())->where('ends_on', '>=', $range['from']->toDateString())
            ->get(['starts_on', 'ends_on']);
        $counts = [];
        foreach ($units as $unit) {
            foreach ($this->datesBetween($unit, $range) as $date) {
                $counts[$date] = ($counts[$date] ?? 0) + 1;
            }
        }
        $full = array_keys(array_filter($counts, fn (int $used): bool => $used >= $stock));
        sort($full);

        return $full;
    }

    private function fullyBookedForVariants(RentalProduct $product, array $stocks, int $days, array $statuses): array
    {
        $range = $this->calendarRange($days);
        $units = RentalOrderUnit::where('rental_product_id', $product->id)->whereIn('status', $statuses)
            ->where('starts_on', '<=', $range['to']->toDateString())->where('ends_on', '>=', $range['from']->toDateString())
            ->get(['rental_product_variant_id', 'starts_on', 'ends_on']);
        $used = [];
        foreach ($units as $unit) {
            $variantId = (int) $unit->rental_product_variant_id;
            if (! isset($stocks[$variantId])) continue; // varian sudah dihapus/nonaktif
            foreach ($this->datesBetween($unit, $range) as $date) {
                $used[$variantId][$date] = ($used[$variantId][$date] ?? 0) + 1;
            }
        }
        $full = [];
        for ($cursor = $range['from']; $cursor->lessThanOrEqualTo($range['to']); $cursor = $cursor->addDay()) {
            $date = $cursor->toDateString();
            $anyFree = false;
            foreach ($stocks as $variantId => $stock) {
                if (($used[$variantId][$date] ?? 0) < $stock) { $anyFree = true; break; }
            }
            if (! $anyFree) $full[] = $date;
        }

        return $full;
    }

    private function calendarRange(int $days): array
    {
        $from = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        return ['from' => $from, 'to' => $from->addDays(max(1, $days) - 1)];
    }

    /** @return array<int,string> tanggal unit yang masuk jendela kalender */
    private function datesBetween(RentalOrderUnit $unit, array $range): array
    {
        $cursor = CarbonImmutable::parse(max($unit->starts_on->toDateString(), $range['from']->toDateString()));
        $last = CarbonImmutable::parse(min($unit->ends_on->toDateString(), $range['to']->toDateString()));
        $dates = [];
        while ($cursor->lessThanOrEqualTo($last)) {
            $dates[] = $cursor->toDateString();
            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    /**
     * Tanggal yang tidak bisa dipakai satu pun produk aktif.
     *
     * Dipakai input tanggal di halaman katalog: filter di sana mencari papan
     * mana saja yang tersedia, jadi tanggal baru layak diblokir bila semua
     * papan sudah penuh. Kalau masih ada satu papan kosong — atau satu warna
     * pada papan bervarian masih ada — tanggalnya tetap bisa dipilih.
     *
     * @param  \Illuminate\Support\Collection<int, RentalProduct>  $products
     * @return array<int, string> daftar tanggal Y-m-d yang tidak tersedia
     */
    public function datesWithNoAvailableProduct($products, int $days = self::BOOKING_HORIZON_DAYS): array
    {
        $products = $products->filter(fn ($product): bool => (int) $product->stock > 0 || $product->activeVariantList()->isNotEmpty());
        if ($products->isEmpty()) return [];
        $range = $this->calendarRange($days);
        $units = RentalOrderUnit::whereIn('rental_product_id', $products->modelKeys())->whereIn('status', RentalOrderUnit::RESERVING_STATUSES)
            ->where('starts_on', '<=', $range['to']->toDateString())->where('ends_on', '>=', $range['from']->toDateString())
            ->get(['rental_product_id', 'rental_product_variant_id', 'starts_on', 'ends_on']);
        $used = [];
        foreach ($units as $unit) {
            $variantId = (int) $unit->rental_product_variant_id;
            foreach ($this->datesBetween($unit, $range) as $date) {
                $key = $unit->rental_product_id.'|'.$variantId;
                $used[$key][$date] = ($used[$key][$date] ?? 0) + 1;
            }
        }
        $capacities = [];
        foreach ($products as $product) {
            $variants = $product->activeVariantList();
            $capacities[$product->id] = $variants->isEmpty()
                ? ['legacy' => max(0, (int) $product->stock)]
                : $variants->mapWithKeys(fn ($variant) => [(int) $variant->id => (int) $variant->stock])->all();
        }
        $result = [];
        for ($cursor = $range['from']; $cursor->lessThanOrEqualTo($range['to']); $cursor = $cursor->addDay()) {
            $date = $cursor->toDateString();
            $allFull = true;
            foreach ($capacities as $productId => $buckets) {
                $available = false;
                foreach ($buckets as $bucketId => $stock) {
                    $usedCount = ($used[$productId.'|'.($bucketId === 'legacy' ? 0 : $bucketId)][$date] ?? 0);
                    if ($usedCount < $stock) { $available = true; break; }
                }
                if ($available) { $allFull = false; break; }
            }
            if ($allFull) $result[] = $date;
        }

        return $result;
    }

    /**
     * Periksa seluruh unit pada satu produk, termasuk overlap antarunit sendiri.
     *
     * Setiap rentang boleh membawa `variant_id`. Bila produknya bervarian dan
     * semua rentang membawa varian, pemeriksaan dilakukan per warna: pesanan
     * merah tidak boleh memakan stok putih.
     */
    public function canFitRequested(
        RentalProduct $product,
        array $ranges,
        bool $lock = false,
        array $statuses = RentalOrderUnit::RESERVING_STATUSES
    ): bool {
        if (! $ranges) return true;

        $stocks = $this->variantStocks($product);
        if (! $stocks) {
            return $this->fits((int) $product->stock, $product->id, null, $ranges, $lock, $statuses, false);
        }

        $byVariant = [];
        foreach ($ranges as $range) {
            $variantId = (int) ($range['variant_id'] ?? $range['rental_product_variant_id'] ?? 0);
            // Rentang tanpa varian (keranjang lama) diperiksa terhadap stok
            // papan secara keseluruhan, bukan dibuang diam-diam.
            if ($variantId < 1) {
                return $this->fits((int) $product->stock, $product->id, null, $ranges, $lock, $statuses, false);
            }
            $byVariant[$variantId][] = $range;
        }

        foreach ($byVariant as $variantId => $group) {
            $stock = $stocks[$variantId] ?? 0;
            if ($stock < 1 || ! $this->fits($stock, $product->id, (int) $variantId, $group, $lock, $statuses, true)) return false;
        }

        return true;
    }

    private function fits(int $stock, int $productId, ?int $variantId, array $ranges, bool $lock, array $statuses, bool $filterVariant): bool
    {
        $starts = array_column($ranges, 'starts_on');
        $ends = array_column($ranges, 'ends_on');
        $existing = RentalOrderUnit::where('rental_product_id', $productId)->whereIn('status', $statuses)
            ->where('starts_on', '<=', max($ends))->where('ends_on', '>=', min($starts))
            ->when($filterVariant, fn ($query) => $query->where('rental_product_variant_id', $variantId))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get(['starts_on', 'ends_on']);
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
            if ($requested > 0 && $reserved + $requested > $stock) return false;
        }

        return true;
    }

    // Caller holds the product row lock throughout the transaction.
    public function assertAvailable(RentalProduct $product, RentalOrderUnit $unit): void
    {
        if (! $product->is_active || ! $product->category->is_active) throw ValidationException::withMessages(['unit' => 'Papan tidak aktif.']);

        // Unit bervarian diperiksa terhadap WARNA yang dipesan, bukan sisa
        // warna mana pun. Tanpa cabang ini, pesanan marun bisa lolos
        // konfirmasi hanya karena warna putih masih kosong.
        $variantId = (int) ($unit->rental_product_variant_id ?? 0);
        if ($variantId > 0) {
            $stock = $this->stockForVariant($product, $variantId);
            $startsOn = $unit->starts_on->toDateString();
            $endsOn = $unit->ends_on->toDateString();
            $overlaps = $this->overlaps($product->id, $startsOn, $endsOn, true, RentalOrderUnit::COMMITTED_STATUSES, true)
                ->where('rental_product_variant_id', $variantId);
            if (! $stock || $this->minimumFromStock($stock, $overlaps, $startsOn, $endsOn) < 1) {
                throw ValidationException::withMessages(['unit' => 'Stok warna '.$unit->variant_name.' tidak cukup pada periode tersebut.']);
            }

            return;
        }

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
