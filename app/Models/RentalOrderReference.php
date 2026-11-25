<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Foto referensi desain yang diunggah pelanggan saat checkout (per pesanan). */
class RentalOrderReference extends Model
{
    protected $fillable = ['rental_order_id', 'image_path', 'uploaded_by'];
    protected $casts = [];

    public function order(): BelongsTo { return $this->belongsTo(RentalOrder::class, 'rental_order_id'); }
}
