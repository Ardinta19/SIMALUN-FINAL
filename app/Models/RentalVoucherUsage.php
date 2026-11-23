<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan pemakaian voucher pada satu pesanan sewa.
 * Terpisah dari `voucher_usages` milik modul laundry karena kolom order-nya
 * menunjuk tabel yang berbeda.
 */
class RentalVoucherUsage extends Model
{
    protected $fillable = ['voucher_id', 'rental_order_id', 'customer_id', 'discount_amount'];

    protected $casts = ['discount_amount' => 'integer'];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class, 'rental_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
