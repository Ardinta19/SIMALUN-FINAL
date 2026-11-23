<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    /** Potongan hanya untuk harga sewa papan. */
    public const SCOPE_RENTAL = 'rental';

    /** Potongan ikut menutup ongkos antar/jemput yang disetujui pelanggan. */
    public const SCOPE_RENTAL_TRANSPORT = 'rental_transport';

    protected $fillable = [
        'code',
        'description',
        'type',
        'scope',
        'value',
        'min_order',
        'max_discount',
        'usage_limit',
        'per_customer_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'per_customer_limit' => 'integer',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    /** Pemakaian voucher pada pesanan sewa (tabel terpisah dari pesanan laundry). */
    public function rentalUsages(): HasMany
    {
        return $this->hasMany(RentalVoucherUsage::class);
    }

    public function coversTransport(): bool
    {
        return $this->scope === self::SCOPE_RENTAL_TRANSPORT;
    }

    public function scopeLabel(): string
    {
        return $this->coversTransport() ? 'Harga sewa + ongkos' : 'Hanya harga sewa';
    }

    /** Jumlah pemakaian oleh satu pelanggan pada alur sewa. */
    public function usageCountFor(int $customerId): int
    {
        return (int) $this->rentalUsages()->where('customer_id', $customerId)->count();
    }

    /** Sisa kuota total; null berarti tanpa batas. */
    public function remainingUses(): ?int
    {
        if ($this->usage_limit === null) {
            return null;
        }

        return max(0, (int) $this->usage_limit - (int) $this->used_count);
    }

    /** Sisa kuota per pelanggan; null berarti tanpa batas. */
    public function remainingUsesFor(int $customerId): ?int
    {
        if ($this->per_customer_limit === null) {
            return null;
        }

        return max(0, (int) $this->per_customer_limit - $this->usageCountFor($customerId));
    }

    /**
     * Cek apakah voucher masih bisa dipakai sekarang (tanggal aktif &
     * usage_limit belum penuh). Tidak mengecek min_order — itu domain
     * lain (tergantung total order).
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();
        if ($this->valid_from && $this->valid_from->toDateString() > $today) {
            return false;
        }
        if ($this->valid_until && $this->valid_until->toDateString() < $today) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Hitung diskon untuk subtotal tertentu (service_cost + item_total + pickup_cost).
     * Mengembalikan int rupiah, sudah memperhitungkan max_discount cap untuk
     * type percent. Asumsinya caller sudah memastikan voucher valid &
     * subtotal >= min_order.
     */
    public function calculateDiscount(int $subtotal): int
    {
        if ($subtotal <= 0) {
            return 0;
        }

        if ($this->type === 'percent') {
            $raw = (int) floor($subtotal * $this->value / 100);
            if ($this->max_discount !== null) {
                $raw = min($raw, $this->max_discount);
            }
        } else {
            // type=fixed
            $raw = (int) $this->value;
        }

        // Diskon tidak boleh melebihi subtotal (mencegah total negatif)
        // dan tidak boleh negatif.
        return max(0, min($raw, $subtotal));
    }

    /**
     * Format ringkas untuk ditampilkan ke customer (mis. "10% (maks Rp 5.000)").
     */
    public function getDisplayValueAttribute(): string
    {
        if ($this->type === 'percent') {
            $base = $this->value.'%';
            if ($this->max_discount) {
                $base .= ' (maks Rp '.number_format($this->max_discount, 0, ',', '.').')';
            }

            return $base;
        }

        return 'Rp '.number_format($this->value, 0, ',', '.');
    }
}
