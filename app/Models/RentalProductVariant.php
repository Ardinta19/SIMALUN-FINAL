<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu warna/varian dari sebuah papan.
 *
 * Harga TIDAK berubah antarvarians (keputusan pemilik usaha): memilih warna
 * bukan pilihan komersial, hanya tampilan. Yang berbeda adalah stok, foto
 * contoh, dan namanya.
 */
class RentalProductVariant extends Model
{
    protected $fillable = ['rental_product_id', 'name', 'color', 'image_path', 'stock', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'stock' => 'integer', 'sort_order' => 'integer'];

    public function product(): BelongsTo { return $this->belongsTo(RentalProduct::class, 'rental_product_id'); }
    public function units(): HasMany { return $this->hasMany(RentalOrderUnit::class, 'rental_product_variant_id'); }

}
