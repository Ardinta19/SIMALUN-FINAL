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

    /** Semua varian, urut sesuai pilihan admin. */
    public function variants(): HasMany { return $this->hasMany(RentalProductVariant::class)->orderBy('sort_order')->orderBy('id'); }

    /** Varian yang boleh dipilih pelanggan. */
    public function activeVariants(): HasMany { return $this->variants()->where('is_active', true); }

    /**
     * Unit pesanan yang memakai papan ini.
     *
     * Dipakai untuk menghitung `withCount` sebelum tombol hapus dirender:
     * papan yang sudah pernah dipesan tidak boleh dihapus (FK `restrict`),
     * jadi admin harus melihat alasannya lebih dulu, bukan setelah gagal.
     */
    public function orderUnits(): HasMany { return $this->hasMany(RentalOrderUnit::class); }

    /**
     * Papan ini punya varian aktif?
     *
     * Memakai relasi yang sudah dimuat bila ada, supaya daftar katalog tidak
     * menembakkan satu kueri per kartu.
     */
    public function hasVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->contains(fn (RentalProductVariant $variant) => $variant->is_active);
        }

        return $this->activeVariants()->exists();
    }

    /** Varian aktif, dimuat bila belum ada di memori. */
    public function activeVariantList()
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

        return $variants->where('is_active', true)->values();
    }

    public function findVariant($id): ?RentalProductVariant
    {
        $id = (int) $id;
        if ($id < 1) return null;

        return $this->activeVariantList()->firstWhere('id', $id);
    }

    /** Foto yang mewakili papan pada pemilihan awal (varian pertama bila ada). */
    public function previewImagePath(): ?string
    {
        if ($this->hasVariants()) {
            $variant = $this->activeVariantList()->first(fn (RentalProductVariant $v) => filled($v->image_path));
            if ($variant) return $variant->image_path;
        }

        return $this->image_path;
    }
    public function likes(): HasMany { return $this->hasMany(RentalProductLike::class); }
    public function reviews(): HasMany { return $this->hasMany(RentalProductReview::class); }
    public function category(): BelongsTo { return $this->belongsTo(RentalCategory::class, 'rental_category_id'); }
}
