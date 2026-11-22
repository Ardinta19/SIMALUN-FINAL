<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalOrderUnit extends Model
{
    protected $fillable = ['rental_order_id', 'rental_product_id', 'product_name', 'unit_price', 'price_unit', 'rental_amount', 'starts_on', 'event_on', 'ends_on', 'recipient_name', 'greeting', 'design_notes', 'delivery_method', 'delivery_address', 'return_method', 'return_address', 'status', 'cancel_reason', 'confirmed_at', 'special_confirmation', 'rental_duration_package_id', 'package_name', 'package_days', 'transport_quote', 'transport_quote_version', 'transport_quote_note', 'transport_approved_at'];
    protected $casts = ['starts_on' => 'date', 'event_on' => 'date', 'ends_on' => 'date', 'special_confirmation' => 'boolean', 'confirmed_at' => 'datetime', 'transport_approved_at' => 'datetime'];
    public function needsSpecialConfirmation(): bool
    {
        // Waktu serah-terima belum dipilih: perlakukan seluruh hari sebagai dekat jadwal.
        return $this->starts_on->toDateString() <= $this->order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->toDateString();
    }
    public function transportRequired(): bool { return $this->delivery_method === 'delivery' || $this->return_method === 'collection'; }
    public function payableAmount(): ?int
    {
        if ($this->transportRequired() && ($this->transport_quote === null || ! $this->transport_approved_at)) return null;
        return (int) $this->rental_amount + ($this->transportRequired() ? (int) $this->transport_quote : 0);
    }
    public function order(): BelongsTo { return $this->belongsTo(RentalOrder::class, 'rental_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(RentalProduct::class, 'rental_product_id'); }
    public function payments(): HasMany { return $this->hasMany(RentalPayment::class)->latest('id'); }
    public function previews(): HasMany { return $this->hasMany(RentalDesignPreview::class)->orderByDesc('version'); }
}
