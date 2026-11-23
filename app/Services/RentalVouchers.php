<?php

namespace App\Services;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalVoucherUsage;
use App\Models\User;
use App\Models\Voucher;

/**
 * Aturan voucher untuk alur sewa papan bunga.
 *
 * Tabel `vouchers` dipakai bersama modul laundry: jenis potongan (persen
 * dengan batas maksimum, atau nominal tetap), minimum belanja, kuota total,
 * dan masa berlaku sudah tersedia di sana. Yang ditambahkan di sini adalah
 * dua aturan yang diatur admin saat membuat voucher:
 *
 *  - `scope`             : potongan hanya harga sewa, atau ikut ongkos;
 *  - `per_customer_limit`: berapa kali satu pelanggan boleh memakai kode.
 *
 * Potongan disimpan per unit (`discount_amount`, `transport_discount_amount`)
 * supaya tagihan, pembayaran, pengembalian, dan pengingat yang sudah dihitung
 * per unit tidak perlu diubah.
 */
class RentalVouchers
{
    /**
     * Cari voucher berdasarkan kode yang diketik pelanggan.
     * Kode disimpan UPPERCASE supaya pencarian tidak peka huruf.
     */
    public function find(string $code): ?Voucher
    {
        $code = mb_strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return Voucher::where('code', $code)->first();
    }

    /**
     * Periksa apakah voucher boleh dipakai pelanggan ini untuk basis belanja
     * tertentu. Mengembalikan pesan galat, atau null bila layak.
     */
    public function problem(Voucher $voucher, User $customer, int $base): ?string
    {
        if (! $voucher->is_active) {
            return 'Voucher sedang tidak aktif.';
        }

        $today = now('Asia/Jakarta')->toDateString();
        if ($voucher->valid_from && $voucher->valid_from->toDateString() > $today) {
            return 'Voucher berlaku mulai '.$voucher->valid_from->format('d/m/Y').'.';
        }
        if ($voucher->valid_until && $voucher->valid_until->toDateString() < $today) {
            return 'Voucher sudah berakhir pada '.$voucher->valid_until->format('d/m/Y').'.';
        }

        if ($voucher->remainingUses() !== null && $voucher->remainingUses() < 1) {
            return 'Kuota voucher sudah habis.';
        }

        $perCustomer = $voucher->remainingUsesFor($customer->id);
        if ($perCustomer !== null && $perCustomer < 1) {
            return $voucher->per_customer_limit === 1
                ? 'Kamu sudah memakai voucher ini. Setiap pelanggan hanya dapat memakainya sekali.'
                : 'Kamu sudah memakai voucher ini '.$voucher->per_customer_limit.' kali.';
        }

        if ($base < (int) $voucher->min_order) {
            return 'Minimal belanja Rp '.number_format((int) $voucher->min_order, 0, ',', '.').' untuk memakai voucher ini.';
        }

        if ($this->discountFor($voucher, $base) < 1) {
            return 'Voucher ini tidak memberi potongan untuk pesananmu.';
        }

        return null;
    }

    /** Potongan rupiah untuk basis belanja tertentu. */
    public function discountFor(Voucher $voucher, int $base): int
    {
        return max(0, $voucher->calculateDiscount(max(0, $base)));
    }

    /**
     * Periksa kode lalu hitung potongan.
     *
     * @return array{voucher: ?Voucher, discount: int, error: ?string}
     */
    public function resolve(string $code, User $customer, int $base): array
    {
        $voucher = $this->find($code);
        if (! $voucher) {
            return ['voucher' => null, 'discount' => 0, 'error' => 'Kode voucher tidak dikenal.'];
        }

        $problem = $this->problem($voucher, $customer, $base);

        return [
            'voucher' => $problem === null ? $voucher : null,
            'discount' => $problem === null ? $this->discountFor($voucher, $base) : 0,
            'error' => $problem,
        ];
    }

    /**
     * Bagi potongan ke unit: harga sewa lebih dulu, sisanya ke ongkos.
     * Setiap unit tidak pernah mendapat potongan melebihi nilainya sendiri.
     *
     * @param  array<string|int, int>  $rentalWeights
     * @param  array<string|int, int>  $transportWeights
     * @return array{rental: array<string|int, int>, transport: array<string|int, int>}
     */
    public function allocate(int $total, array $rentalWeights, array $transportWeights = []): array
    {
        $total = max(0, $total);
        $rentalWeights = array_map(fn ($weight) => max(0, (int) $weight), $rentalWeights);
        $transportWeights = array_map(fn ($weight) => max(0, (int) $weight), $transportWeights);

        $rentalBudget = min($total, array_sum($rentalWeights));
        $rental = $this->spread($rentalBudget, $rentalWeights);
        $remaining = $total - array_sum($rental);
        $transport = $remaining > 0 ? $this->spread($remaining, $transportWeights) : array_map(fn () => 0, $transportWeights);

        return ['rental' => $rental, 'transport' => $transport];
    }

    /**
     * Bagi satu anggaran ke beberapa bobot secara proporsional, lalu
     * menaruh sisa pembulatan pada bobot terbesar.
     *
     * @param  array<string|int, int>  $weights
     * @return array<string|int, int>
     */
    private function spread(int $budget, array $weights): array
    {
        $out = array_map(fn () => 0, $weights);
        $sum = array_sum($weights);
        if ($budget <= 0 || $sum <= 0) {
            return $out;
        }
        if ($budget >= $sum) {
            return $weights;
        }

        $given = 0;
        foreach ($weights as $key => $weight) {
            if ($weight <= 0) {
                continue;
            }
            $part = (int) floor($budget * ($weight / $sum));
            $out[$key] = $part;
            $given += $part;
        }

        $left = $budget - $given;
        if ($left > 0) {
            arsort($weights);
            foreach (array_keys($weights) as $key) {
                if ($left <= 0) {
                    break;
                }
                $room = $weights[$key] - $out[$key];
                if ($room <= 0) {
                    continue;
                }
                $add = min($room, $left);
                $out[$key] += $add;
                $left -= $add;
            }
        }

        return $out;
    }

    /**
     * Catat pemakaian voucher pada pesanan yang baru dibuat.
     * Wajib dipanggil di dalam transaksi yang sudah mengunci baris voucher.
     */
    public function record(Voucher $voucher, RentalOrder $order, int $discount, int $customerId): void
    {
        if ($discount < 1) {
            return;
        }

        $voucher->increment('used_count');

        RentalVoucherUsage::create([
            'voucher_id' => $voucher->id,
            'rental_order_id' => $order->id,
            'customer_id' => $customerId,
            'discount_amount' => $discount,
        ]);
    }

    /**
     * Hitung ulang potongan pesanan setelah ongkos disetujui admin/pelanggan.
     * Hanya berlaku untuk voucher ber-scope "harga sewa + ongkos"; potongan
     * tidak pernah melebihi tagihan maupun pembayaran yang sudah terverifikasi.
     */
    public function redistribute(RentalOrder $order): void
    {
        $voucher = $order->voucher;
        if (! $voucher) {
            return;
        }

        $units = $order->units()->where('status', '!=', RentalOrderUnit::STATUS_CANCELLED)->orderBy('id')->get();
        if ($units->isEmpty()) {
            return;
        }

        if (! $voucher->coversTransport()) {
            // Voucher hanya memotong harga sewa: basisnya tidak berubah saat
            // ongkos disetujui, jadi potongan yang sudah tercatat tetap dipakai.
            return;
        }

        $rentalWeights = $units->mapWithKeys(fn (RentalOrderUnit $unit) => [$unit->id => (int) $unit->rental_amount])->all();
        $transportWeights = $units->mapWithKeys(fn (RentalOrderUnit $unit) => [$unit->id => $unit->transportApproved() ? (int) $unit->transport_quote : 0])->all();

        $base = array_sum($rentalWeights) + array_sum($transportWeights);
        $discount = $this->discountFor($voucher, $base);

        // Jangan menurunkan tagihan sampai di bawah uang yang sudah diverifikasi.
        $headroom = 0;
        foreach ($units as $unit) {
            $grossPayable = (int) $unit->rental_amount + ($unit->transportApproved() ? (int) $unit->transport_quote : 0);
            $headroom += max(0, $grossPayable - $unit->verifiedNet());
        }
        $discount = max(0, min($discount, $headroom));

        $allocation = $this->allocate($discount, $rentalWeights, $transportWeights);

        foreach ($units as $unit) {
            $unit->forceFill([
                'discount_amount' => (int) ($allocation['rental'][$unit->id] ?? 0),
                'transport_discount_amount' => (int) ($allocation['transport'][$unit->id] ?? 0),
            ])->save();
        }

        $order->forceFill(['discount_amount' => $discount])->save();
        RentalVoucherUsage::where('rental_order_id', $order->id)->where('voucher_id', $voucher->id)
            ->update(['discount_amount' => $discount]);
    }

    /**
     * Perkiraan harga sewa keranjang untuk pemeriksaan kode di halaman
     * checkout. Pilihan paket baru diketahui saat pesanan dikirim, jadi
     * angka ini hanya perkiraan yang ditampilkan sebelum mengirim.
     *
     * @param  array<string, array{product_id: int, starts_on?: string, ends_on?: string}>  $cart
     * @param  \Illuminate\Support\Collection<int, \App\Models\RentalProduct>  $products
     */
    public function estimateRentalBase(array $cart, $products): int
    {
        $base = 0;

        foreach ($cart as $entry) {
            $product = $products->get($entry['product_id'] ?? null);
            if (! $product) {
                continue;
            }

            $days = 1;
            if (! empty($entry['starts_on']) && ! empty($entry['ends_on'])) {
                $days = max(1, (int) \Carbon\Carbon::parse($entry['starts_on'])->diffInDays(\Carbon\Carbon::parse($entry['ends_on'])) + 1);
            }

            $base += $product->price_unit === 'day' ? (int) $product->base_price * $days : (int) $product->base_price;
        }

        return $base;
    }
}
