<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalOrder extends Model
{
    protected $fillable = ['reference', 'customer_id', 'confirmation_deadline', 'checkout_key', 'checkout_payload_hash'];
    protected $casts = ['confirmation_deadline' => 'datetime'];
    public function units(): HasMany { return $this->hasMany(RentalOrderUnit::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
}
