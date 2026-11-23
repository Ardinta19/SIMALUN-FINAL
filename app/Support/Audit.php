<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Helper rekam jejak admin actions. Dipanggil manual di controller setelah
 * aksi sukses — bukan via observer biar admin punya kontrol penuh kapan
 * audit log dibuat (mis. skip log untuk auto-process internal).
 */
class Audit
{
    /**
     * Catat satu entry audit. Semua argumen kecuali $action bersifat opsional
     * supaya gampang dipanggil di banyak konteks.
     *
     * Contoh:
     *   Audit::log('rental.voucher.create', $voucher, after: $voucher->toArray());
     *   Audit::log('rental.catalog.product.toggle', $product, before: $before, after: $after);
     */
    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?string $summary = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => Auth::id(),
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'summary' => $summary,
            'before' => $before,
            'after' => $after,
            'ip' => Request::ip(),
        ]);
    }
}
