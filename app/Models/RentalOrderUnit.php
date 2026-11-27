<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class RentalOrderUnit extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Status yang menahan stok produk pada tanggal sewa.
     *
     * Pesanan yang baru dikirim (pending) sudah mengunci tanggalnya sampai
     * admin mengonfirmasi atau tenggat konfirmasi lewat — kalau tidak,
     * dua pelanggan bisa sama-sama melihat tanggal yang sama sebagai kosong.
     */
    public const RESERVING_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    /** Status yang sudah pasti memakai unit; dipakai saat admin mengonfirmasi. */
    public const COMMITTED_STATUSES = [self::STATUS_CONFIRMED];

    protected $fillable = ['rental_order_id', 'rental_product_id', 'product_name', 'unit_price', 'price_unit', 'rental_amount', 'addon_amount', 'starts_on', 'event_on', 'ends_on', 'recipient_name', 'greeting', 'design_notes', 'delivery_method', 'delivery_address', 'return_method', 'return_address', 'status', 'status_before_cancel', 'cancel_code', 'payment_review_hold', 'payment_review_resolved', 'deposit_forfeited_at', 'payment_late_resumed_at', 'progress', 'cancel_reason', 'cancel_responsibility', 'cancelled_at', 'cancelled_by', 'confirmed_at', 'special_confirmation', 'rental_duration_package_id', 'package_name', 'package_days', 'delivery_university', 'delivery_university_fee', 'return_university', 'return_university_fee', 'transport_quote', 'transport_quote_version', 'transport_quote_note', 'transport_approved_at', 'design_override_at', 'design_override_by', 'design_override_version', 'design_override_note', 'discount_amount', 'transport_discount_amount'];
    protected $casts = ['addon_amount' => 'integer', 'discount_amount' => 'integer', 'transport_discount_amount' => 'integer', 'starts_on' => \App\Casts\DateOnly::class, 'event_on' => \App\Casts\DateOnly::class, 'ends_on' => \App\Casts\DateOnly::class, 'payment_review_hold' => 'boolean', 'payment_review_resolved' => 'boolean', 'payment_late_resumed_at' => 'datetime', 'deposit_forfeited_at' => 'datetime', 'cancelled_at' => 'datetime', 'special_confirmation' => 'boolean', 'confirmed_at' => 'datetime', 'transport_approved_at' => 'datetime', 'design_override_at' => 'datetime'];
    public function needsSpecialConfirmation(): bool
    {
        // Waktu serah-terima belum dipilih: perlakukan seluruh hari sebagai dekat jadwal.
        return $this->starts_on->toDateString() <= $this->order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->toDateString();
    }
    public function plannedPaymentDeadline(): \Carbon\CarbonImmutable
    {
        // Tanggal penerimaan adalah tanggal pengantaran atau pengambilan sendiri; WIB tidak mengikuti zona browser.
        return \Carbon\CarbonImmutable::parse($this->starts_on->toDateString(), 'Asia/Jakarta')->startOfDay()->subSecond();
    }
    public function transportApproved(): bool
    {
        return $this->transportRequired() && $this->transport_quote !== null && $this->transport_approved_at !== null;
    }
    public function transportRequired(): bool { return $this->delivery_method === 'delivery' || $this->return_method === 'collection'; }
    public function payableAmount(): ?int
    {
        if ($this->transportRequired() && ($this->transport_quote === null || ! $this->transport_approved_at)) return null;
        return $this->rentalAfterDiscount()
            + (int) $this->addon_amount
            + ($this->transportRequired() ? $this->transportAfterDiscount() : 0);
    }

    /** Harga sewa setelah potongan voucher (tidak pernah negatif). */
    public function rentalAfterDiscount(): int
    {
        return max(0, (int) $this->rental_amount - (int) $this->discount_amount);
    }

    /** Ongkos setelah potongan voucher (tidak pernah negatif). */
    public function transportAfterDiscount(): int
    {
        return max(0, (int) $this->transport_quote - (int) $this->transport_discount_amount);
    }

    /** Total potongan voucher pada unit ini. */
    public function totalDiscount(): int
    {
        return (int) $this->discount_amount + (int) $this->transport_discount_amount;
    }
    public function order(): BelongsTo { return $this->belongsTo(RentalOrder::class, 'rental_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(RentalProduct::class, 'rental_product_id'); }
    public function cancellationEvents(): HasMany { return $this->hasMany(RentalUnitCancellationEvent::class)->orderBy('id'); }
    public function progressEvents(): HasMany { return $this->hasMany(RentalUnitProgressEvent::class)->orderBy('id'); }
    public function refunds(): HasManyThrough { return $this->hasManyThrough(RentalPaymentRefund::class, RentalPayment::class, 'rental_order_unit_id', 'rental_payment_id'); }
    public function verifiedNet(): int { return (int) $this->payments()->where('status', 'verified')->sum('amount') - (int) $this->refunds()->sum('rental_payment_refunds.amount'); }
    public function allocatedNet(): int { return (int) $this->payments()->whereIn('status', ['pending', 'verified'])->sum('amount') - (int) $this->refunds()->sum('rental_payment_refunds.amount'); }
    public function payments(): HasMany { return $this->hasMany(RentalPayment::class)->latest('id'); }
    public function reminders(): HasMany { return $this->hasMany(RentalUnitReminder::class)->orderByDesc('sent_on')->orderByDesc('id'); }

    /**
     * Antrean "perlu peninjauan pembayaran" untuk admin.
     *
     * Unit yang sudah dikembalikan tidak lagi memerlukan tindakan, sehingga
     * tidak boleh ikut dihitung walau baris lama masih menyimpan hold.
     */
    public function scopeNeedsPaymentReview($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED)
            ->where('payment_review_hold', true)
            ->where('progress', '!=', 'returned');
    }
    public function previews(): HasMany { return $this->hasMany(RentalDesignPreview::class)->orderByDesc('version'); }
    /** Item tambahan yang dipilih pelanggan untuk unit ini. */
    public function addons(): HasMany { return $this->hasMany(RentalOrderUnitAddon::class)->orderBy('id'); }

    public function transportQuoteEvents(): HasMany { return $this->hasMany(RentalTransportQuoteEvent::class)->orderBy('id'); }

    /**
     * Preview terbaru boleh dipakai untuk pengerjaan bila pelanggan menyetujui,
     * atau bila admin sudah memutuskan melanjutkan tanpa persetujuan pada versi
     * yang sama setelah tenggat respons lewat.
     */
    public function designApprovedForProduction(): bool
    {
        $latest = $this->previews()->first();
        if (! $latest) return false;
        if ($latest->status === 'approved') return true;

        return $this->design_override_at !== null && (int) $this->design_override_version === (int) $latest->version;
    }

    public function designOverrideActive(): bool
    {
        if ($this->design_override_at === null) return false;
        $latest = $this->previews()->first();

        return $latest !== null && (int) $this->design_override_version === (int) $latest->version;
    }
}
