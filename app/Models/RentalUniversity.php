<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalUniversity extends Model
{
    protected $fillable = ['name', 'delivery_fee', 'collection_fee', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
