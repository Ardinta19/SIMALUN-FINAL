<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalUnitProgressEvent extends Model
{
    protected $fillable = ['rental_order_unit_id', 'progress', 'recorded_by', 'note'];
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
