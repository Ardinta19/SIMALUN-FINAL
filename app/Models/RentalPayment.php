<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalPayment extends Model
{
    protected $fillable = ['rental_order_unit_id', 'amount', 'proof_path', 'status', 'submitted_at', 'verified_at', 'verified_by', 'admin_note', 'finance_entry_id'];
    protected $casts = ['submitted_at' => 'datetime', 'verified_at' => 'datetime'];
    public function corrections(): HasMany { return $this->hasMany(RentalPaymentCorrection::class)->orderBy('id'); }
    public function refunds(): HasMany { return $this->hasMany(RentalPaymentRefund::class)->orderBy('id'); }
    public function unit(): BelongsTo { return $this->belongsTo(RentalOrderUnit::class, 'rental_order_unit_id'); }
}
