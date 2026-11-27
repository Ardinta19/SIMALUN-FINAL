<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    /**
     * `last_used_at` sengaja tidak fillable: kolom warisan modul lama yang
     * tidak pernah diisi, sehingga urutan daftar alamat memakai `updated_at`.
     */
    protected $fillable = [
        'customer_id',
        'label',
        'recipient_name',
        'phone',
        'full_address',
        'latitude',
        'longitude',
        'distance_km',
        'zone',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'is_primary' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
