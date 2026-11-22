<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalPaymentCorrection extends Model
{
    protected $fillable = ['rental_payment_id', 'old_amount', 'new_amount', 'reason', 'recorded_by'];
}
