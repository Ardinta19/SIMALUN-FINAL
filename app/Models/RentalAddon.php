<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Item tambahan yang dapat dipilih pelanggan saat checkout. */
class RentalAddon extends Model
{
    protected $fillable = ['name', 'description', 'price', 'image_path', 'is_active', 'sort_order'];
    protected $casts = ['price' => 'integer', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    /** Hanya item aktif, urut sesuai aturan admin. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
