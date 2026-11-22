<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalOrderUnit extends Model
{
    protected $fillable = ['rental_order_id', 'rental_product_id', 'product_name', 'unit_price', 'price_unit', 'rental_amount', 'starts_on', 'event_on', 'ends_on', 'recipient_name', 'greeting', 'design_notes', 'delivery_method', 'delivery_address', 'return_method', 'return_address', 'status', 'cancel_reason', 'confirmed_at'];
    protected $casts = ['starts_on' => 'date', 'event_on' => 'date', 'ends_on' => 'date', 'confirmed_at' => 'datetime'];
    public function order(): BelongsTo { return $this->belongsTo(RentalOrder::class, 'rental_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(RentalProduct::class, 'rental_product_id'); }
    public function previews(): HasMany { return $this->hasMany(RentalDesignPreview::class)->orderByDesc('version'); }
}
