<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalUnitReminder extends Model
{
    protected $fillable = ['rental_order_unit_id', 'kind', 'recipient', 'sent_on'];
    protected $casts = ['sent_on' => 'date'];
    public function unit(): BelongsTo { return $this->belongsTo(RentalOrderUnit::class, 'rental_order_unit_id'); }
}
