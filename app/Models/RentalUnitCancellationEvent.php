<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalUnitCancellationEvent extends Model
{
    protected $fillable = ['rental_order_unit_id', 'action', 'previous_status', 'new_status', 'note', 'responsibility', 'recorded_by'];
}
