<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit log writes — memastikan tindakan admin tercatat di tabel audit_logs.
 *
 * Pattern: Audit::log(action, model, before, after, summary) — dipanggil
 * manual oleh controller setelah aksi sukses. Action yang diuji di sini
 * adalah aksi modul sewa; action laundry (order.assign-driver, order.status,
 * service.*) sudah ikut terhapus bersama modulnya.
 */
class AuditLogTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_catat_kas_manual_tertulis_di_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.rental.cashbook.store'), [
            'entry_date' => now()->toDateString(),
            'entry_type' => 'income',
            'category' => 'lainnya',
            'amount' => 75000,
            'notes' => 'Audit kas manual',
            'not_rental_payment' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cashbook.manual.create',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_audit_log_menyimpan_snapshot_perubahan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $entry = \App\Models\FinanceEntry::create([
            'entry_date' => now()->toDateString(),
            'period_key' => now()->format('Y-m'),
            'entry_type' => 'expense',
            'category' => 'operasional',
            'amount' => 20000,
            'notes' => 'Awal',
            'source_type' => 'manual',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->put(route('admin.rental.cashbook.update', $entry), [
            'entry_date' => now()->toDateString(),
            'entry_type' => 'expense',
            'category' => 'operasional',
            'amount' => 35000,
            'notes' => 'Setelah revisi',
        ]);

        $audit = \App\Models\AuditLog::where('action', 'cashbook.manual.update')->latest('id')->firstOrFail();

        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame(20000, $audit->before['amount'] ?? null);
        $this->assertSame(35000, $audit->after['amount'] ?? null);
    }

    public function test_halaman_audit_trail_render_untuk_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk();
    }
}
