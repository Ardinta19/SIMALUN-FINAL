<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Salinan pilihan item tambahan pada satu unit (nama + harga saat checkout). */
class RentalOrderUnitAddon extends Model
{
    protected $fillable = ['rental_order_unit_id', 'rental_addon_id', 'name', 'price', 'quantity', 'total'];
    protected $casts = ['price' => 'integer', 'quantity' => 'integer', 'total' => 'integer'];

    /** Tulisan baku di semua halaman: "Lilin" atau "Lilin × 2" (jumlah 1 tidak ditulis). */
    public function label(): string
    {
        return $this->quantity > 1 ? $this->name.' × '.$this->quantity : (string) $this->name;
    }

    public function unit(): BelongsTo { return $this->belongsTo(RentalOrderUnit::class, 'rental_order_unit_id'); }
    public function addon(): BelongsTo { return $this->belongsTo(RentalAddon::class, 'rental_addon_id'); }
}
