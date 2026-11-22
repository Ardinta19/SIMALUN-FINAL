<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalDesignPreview extends Model
{
    protected $fillable = ['rental_order_unit_id', 'version', 'image_path', 'admin_note', 'status', 'customer_note', 'created_by', 'responded_at'];
    protected $casts = ['responded_at' => 'datetime'];
    public function unit(): BelongsTo { return $this->belongsTo(RentalOrderUnit::class, 'rental_order_unit_id'); }
}
