<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalDurationPackage extends Model
{
    protected $fillable = ['rental_product_id', 'name', 'days', 'price', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function product(): BelongsTo { return $this->belongsTo(RentalProduct::class, 'rental_product_id'); }

    /** Unit pesanan yang memakai paket ini (FK `restrict` saat menghapus). */
    public function orderUnits(): HasMany { return $this->hasMany(RentalOrderUnit::class, 'rental_duration_package_id'); }
}
