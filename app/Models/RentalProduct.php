<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalProduct extends Model
{
    protected $fillable = ['rental_category_id', 'name', 'size', 'description', 'base_price', 'price_unit', 'stock', 'image_path', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function packages(): HasMany { return $this->hasMany(RentalDurationPackage::class)->orderBy("days")->orderBy("id"); }
    public function likes(): HasMany { return $this->hasMany(RentalProductLike::class); }
    public function reviews(): HasMany { return $this->hasMany(RentalProductReview::class); }
    public function category(): BelongsTo { return $this->belongsTo(RentalCategory::class, 'rental_category_id'); }
}
