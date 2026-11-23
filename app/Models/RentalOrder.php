<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalOrder extends Model
{
    protected $fillable = ['reference', 'customer_id', 'confirmation_deadline', 'checkout_key', 'checkout_payload_hash', 'voucher_id', 'voucher_code', 'discount_amount'];
    protected $casts = ['confirmation_deadline' => 'datetime', 'discount_amount' => 'integer'];
    public function units(): HasMany { return $this->hasMany(RentalOrderUnit::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
    public function voucherUsage(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(RentalVoucherUsage::class); }
}
