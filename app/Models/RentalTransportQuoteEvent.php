<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalTransportQuoteEvent extends Model
{
    protected $fillable = ['rental_order_unit_id', 'action', 'version', 'amount', 'note', 'recorded_by'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(RentalOrderUnit::class, 'rental_order_unit_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
