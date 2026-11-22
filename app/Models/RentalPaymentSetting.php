<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalPaymentSetting extends Model
{
    public $incrementing = false;
    protected $fillable = ['id', 'instructions', 'qris_path', 'whatsapp_number'];
}
