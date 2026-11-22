<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPaymentRefund extends Model
{
    protected $fillable = ['rental_payment_id', 'amount', 'reason', 'request_key', 'refunded_at', 'recorded_by', 'finance_entry_id'];
    protected $casts = ['refunded_at' => 'datetime'];
    public function payment(): BelongsTo { return $this->belongsTo(RentalPayment::class, 'rental_payment_id'); }
}
