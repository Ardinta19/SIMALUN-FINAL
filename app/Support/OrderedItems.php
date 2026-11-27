<?php

namespace App\Support;

use App\Models\RentalOrderUnit;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Bentuk data tunggal untuk daftar "Item yang dipesan".
 *
 * Daftar ini tampil di tiga tempat (checkout, halaman pesanan pelanggan, detail
 * pesanan admin) dengan sumber data berbeda: checkout masih memakai isi sesi
 * keranjang, sedangkan dua halaman lain memakai unit pesanan yang sudah
 * tersimpan. Dinormalkan di sini supaya satu partial dapat merender ketiganya —
 * tanpa cabang `@if` yang berbeda-beda di tiap halaman dan tanpa risiko
 * "daftar item" di satu halaman lambat laun berbeda dari halaman lain.
 *
 * Sengaja **tidak** memuat gambar: unit pesanan hanya menyimpan nama papan dan
 * warnanya, dan mengambil gambar produk untuk setiap baris berarti kueri
 * tambahan per item. Penanda nomor + contoh warna sudah cukup untuk mengenali
 * barisnya.
 */
final class OrderedItems
{
    /**
     * @param  Collection<int, RentalOrderUnit>|array<int, RentalOrderUnit>  $units
     * @param  callable(RentalOrderUnit): ?string|null  $urlFor
     * @return array<int, array<string, mixed>>
     */
    public static function fromUnits(Collection|array $units, ?callable $urlFor = null): array
    {
        $items = [];
        $nomor = 0;

        foreach ($units as $unit) {
            $nomor += 1;
            $items[] = [
                'no' => $nomor,
                'name' => (string) $unit->product_name,
                'variant_name' => $unit->variant_name,
                'variant_color' => $unit->variant_color,
                'meta' => $unit->package_name
                    ? 'Paket '.$unit->package_name.' · '.$unit->package_days.' hari'
                    : null,
                'event_on' => $unit->event_on,
                'ends_on' => $unit->ends_on,
                'dates_known' => true,
                'addons' => $unit->addons->map(fn ($addon) => [
                    'label' => $addon->label(),
                    'amount' => (int) $addon->total,
                ])->all(),
                'price_amount' => (int) $unit->unit_price,
                'price_unit' => (string) $unit->price_unit,
                'status_label' => RentalLabels::unitStatus($unit->status),
                'status_key' => (string) $unit->status,
                'url' => $urlFor ? $urlFor($unit) : null,
            ];
        }

        return $items;
    }

    /**
     * Isi keranjang sesi: satu papan = satu entri, warna diambil dari entri.
     *
     * @param  array<string, array<string, mixed>>  $cart
     * @param  Collection<int, \App\Models\RentalProduct>  $products
     * @return array<int, array<string, mixed>>
     */
    public static function fromCart(array $cart, Collection $products): array
    {
        $items = [];
        $nomor = 0;

        foreach (array_values($cart) as $entry) {
            $product = $products->get($entry['product_id'] ?? null);
            if (! $product) {
                continue;
            }

            $nomor += 1;
            $variant = $product->findVariant((int) ($entry['variant_id'] ?? 0));
            $items[] = [
                'no' => $nomor,
                'name' => (string) $product->name,
                'variant_name' => $variant?->name,
                'variant_color' => $variant?->color,
                'meta' => $product->category?->name,
                'event_on' => self::toDate($entry['starts_on'] ?? null),
                'ends_on' => self::toDate($entry['ends_on'] ?? null),
                // Di checkout tanggal masih diisi di kartu tiap papan, jadi baris
                // ringkasan tidak menulis "tanggal belum dipilih" yang bisa
                // bertentangan dengan isian yang sudah diketik pelanggan.
                'dates_known' => false,
                // Item tambahan dipilih di dalam tab unit, jadi belum diketahui
                // di sini; daftar di checkout memperkirakan harga dasar saja.
                'addons' => [],
                'price_amount' => (int) $product->base_price,
                'price_unit' => (string) $product->price_unit,
                'status_label' => null,
                'status_key' => null,
                'url' => null,
            ];
        }

        return $items;
    }

    private static function toDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}
