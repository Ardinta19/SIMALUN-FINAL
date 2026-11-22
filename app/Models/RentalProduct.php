<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalProduct extends Model
{
    protected $fillable = ['rental_category_id', 'name', 'size', 'description', 'base_price', 'price_unit', 'stock', 'image_path', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function category(): BelongsTo { return $this->belongsTo(RentalCategory::class, 'rental_category_id'); }
}
