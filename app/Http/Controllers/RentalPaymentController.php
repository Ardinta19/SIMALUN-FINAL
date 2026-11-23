<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
use App\Models\RentalPaymentRefund;
use App\Services\RentalPaymentDeadline;
use App\Services\RentalNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RentalPaymentController extends Controller
{
    public function store(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $submittedAt = now();
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:100000000'], 'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $path = $request->file('proof')->store('rental-payment-proofs', 'local');
        if (! $path) throw ValidationException::withMessages(['proof' => 'Bukti tidak dapat disimpan.']);
        try {
            DB::transaction(function () use ($unit, $data, $path, $submittedAt) {
                $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
                $policy = app(RentalPaymentDeadline::class);
                $late = $policy->eligibleForLatePayment($unit);
                if ($unit->status !== 'confirmed' && ! $late) {
                    throw ValidationException::withMessages(['payment' => $unit->status === 'cancelled' && $unit->cancel_code === 'payment_timeout'
                        ? 'Masa toleransi pembayaran telah berakhir; bukti tidak dapat dikirim lagi.'
                        : 'Unit tidak dapat menerima bukti pembayaran saat ini.']);
                }
                if (! $late && $policy->isDue($unit) && ! $unit->payment_review_hold) throw ValidationException::withMessages(['payment' => 'Tenggat pelunasan telah lewat. Tunggu pembaruan status atau hubungi admin.']);
                $total = $unit->payableAmount();
                if ($total === null) throw ValidationException::withMessages(['payment' => 'Setujui penawaran ongkos terlebih dahulu.']);
                $allocated = $unit->allocatedNet();
                if ($allocated + $data['amount'] > $total) throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa tagihan, termasuk bukti yang menunggu verifikasi.']);
                $unit->payments()->create(['amount' => $data['amount'], 'proof_path' => $path, 'submitted_at' => $submittedAt]);
                app(RentalNotifications::class)->admins($unit->order, 'Bukti pembayaran baru', 'Bukti pembayaran unit '.$unit->product_name.' menunggu pemeriksaan.');
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        return back()->with('success', 'Bukti pembayaran dikirim dan menunggu verifikasi.');
    }

    public function proof(Request $request, RentalPayment $payment)
    {
        $payment->loadMissing('unit.order');
        $user = $request->user();
        abort_unless($user->role === 'admin' || ($user->role === 'customer' && $payment->unit->order->customer_id === $user->id), 403);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);
        return Storage::disk('local')->response($payment->proof_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function verify(Request $request, RentalPayment $payment)
    {
        DB::transaction(function () use ($request, $payment) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($payment->rental_order_unit_id);
            $payment = RentalPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'pending' || ! in_array($unit->status, ['confirmed', 'cancelled'], true)) throw ValidationException::withMessages(['payment' => 'Pembayaran tidak lagi dapat diverifikasi.']);
            $verified = $unit->verifiedNet();
            if ($unit->payableAmount() === null || $verified + $payment->amount > $unit->payableAmount()) throw ValidationException::withMessages(['payment' => 'Nominal melebihi sisa tagihan.']);
            $time = now('Asia/Jakarta');
            $entry = FinanceEntry::create(['entry_date' => $time->toDateString(), 'period_key' => $time->format('Y-m'), 'entry_type' => 'income', 'category' => 'income', 'amount' => $payment->amount, 'source_type' => 'rental_payment', 'source_id' => $payment->id, 'notes' => 'Pembayaran unit sewa #'.$unit->id, 'created_by' => $request->user()->id]);
            $payment->update(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $request->user()->id, 'finance_entry_id' => $entry->id]);
            // Peninjauan manual selesai dengan sendirinya begitu tagihan tertutup
            // pembayaran terverifikasi, supaya hold tidak menahan antrean admin
            // dan tidak menghalangi serah terima.
            if ($unit->payment_review_hold && $unit->payableAmount() !== null && $unit->verifiedNet() >= $unit->payableAmount()) {
                $unit->update(['payment_review_hold' => false, 'payment_review_resolved' => true]);
            }
            app(RentalNotifications::class)->customer($unit->order, 'Pembayaran diverifikasi', 'Pembayaran unit '.$unit->product_name.' telah diverifikasi.');
        });
        return back()->with('success', 'Pembayaran diverifikasi dan dicatat dalam buku kas.');
    }

    public function correct(Request $request, RentalPayment $payment)
    {
        $data = $request->validate(['expected_amount' => ['required', 'integer', 'min:1'], 'amount' => ['required', 'integer', 'min:1', 'max:100000000'], 'reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $payment, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($payment->rental_order_unit_id);
            $payment = RentalPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'verified' || ! $payment->finance_entry_id || (int) $payment->amount !== (int) $data['expected_amount']) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran berubah atau tidak dapat dikoreksi. Muat ulang halaman.']);
            }
            $entry = FinanceEntry::lockForUpdate()->findOrFail($payment->finance_entry_id);
            if ($entry->source_type !== 'rental_payment' || (int) $entry->source_id !== $payment->id || $entry->entry_type !== 'income' || (int) $entry->amount !== (int) $payment->amount) {
                throw ValidationException::withMessages(['payment' => 'Catatan kas tidak cocok. Perlu pemeriksaan manual.']);
            }
            $new = (int) $data['amount'];
            if ($new < $payment->refunds()->sum('amount')) throw ValidationException::withMessages(['payment' => 'Nominal tidak boleh kurang dari jumlah yang telah dikembalikan.']);
            $total = $unit->payableAmount();
            if ($total === null || $unit->allocatedNet() - $payment->amount + $new > $total) throw ValidationException::withMessages(['payment' => 'Koreksi melebihi tagihan dengan memperhitungkan bukti yang menunggu verifikasi.']);
            if ($new === (int) $payment->amount) throw ValidationException::withMessages(['payment' => 'Nominal koreksi sama dengan nominal sebelumnya.']);
            $payment->corrections()->create(['old_amount' => $payment->amount, 'new_amount' => $new, 'reason' => $data['reason'], 'recorded_by' => $request->user()->id]);
            $payment->update(['amount' => $new]);
            $entry->update(['amount' => $new]);
            app(RentalNotifications::class)->customer($unit->order, 'Nominal pembayaran dikoreksi', 'Admin mengoreksi nominal pembayaran unit '.$unit->product_name.'. Periksa detail dan alasan koreksi.');
        }, 3);
        return back()->with('success', 'Nominal pembayaran dan catatan kas terkait diperbaiki.');
    }

    public function refund(Request $request, RentalPayment $payment)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:100000000'], 'reason' => ['required', 'string', 'max:500'], 'request_key' => ['required', 'uuid'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $payment, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($payment->rental_order_unit_id);
            $payment = RentalPayment::lockForUpdate()->findOrFail($payment->id);
            $existing = RentalPaymentRefund::where('request_key', $data['request_key'])->first();
            if ($existing) {
                if ($existing->rental_payment_id !== $payment->id || (int) $existing->amount !== (int) $data['amount'] || $existing->reason !== $data['reason']) throw ValidationException::withMessages(['refund' => 'Kunci pencatatan sudah digunakan untuk pengembalian lain.']);
                return;
            }
            if ($payment->status !== 'verified') throw ValidationException::withMessages(['refund' => 'Hanya pembayaran terverifikasi yang dapat dikembalikan.']);
            $remaining = $payment->amount - $payment->refunds()->sum('amount');
            if ($data['amount'] > $remaining) throw ValidationException::withMessages(['refund' => 'Nominal pengembalian melebihi pembayaran yang belum dikembalikan.']);
            $time = now('Asia/Jakarta');
            $refund = $payment->refunds()->create(['amount' => $data['amount'], 'reason' => $data['reason'], 'request_key' => $data['request_key'], 'refunded_at' => $time, 'recorded_by' => $request->user()->id]);
            $entry = FinanceEntry::create(['entry_date' => $time->toDateString(), 'period_key' => $time->format('Y-m'), 'entry_type' => 'expense', 'category' => 'pengembalian', 'amount' => $refund->amount, 'source_type' => 'rental_refund', 'source_id' => $refund->id, 'notes' => 'Pengembalian pembayaran #'.$payment->id.': '.$refund->reason, 'created_by' => $request->user()->id]);
            $refund->update(['finance_entry_id' => $entry->id]);
            app(RentalNotifications::class)->customer($unit->order, 'Pengembalian dana dicatat', 'Admin mencatat pengembalian dana unit '.$unit->product_name.'. Periksa rincian pada pesanan.');
        }, 3);
        return back()->with('success', 'Pengembalian dicatat dalam buku kas.');
    }

    public function resolveReview(Request $request, RentalOrderUnit $unit)
    {
        $request->validate(['confirmed' => ['accepted']]);
        DB::transaction(function () use ($unit) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'confirmed' || ! $unit->payment_review_hold || $unit->payableAmount() === null) throw ValidationException::withMessages(['payment' => 'Peninjauan belum dapat diselesaikan atau ongkos belum disetujui.']);
            $unit->update(['payment_review_hold' => false, 'payment_review_resolved' => true]);
        }, 3);
        return back()->with('success', 'Peninjauan dilepas. Penjadwal akan memeriksa kembali bukti dan tenggat.');
    }

    public function reject(Request $request, RentalPayment $payment)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $payment, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($payment->rental_order_unit_id);
            $payment = RentalPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'pending') throw ValidationException::withMessages(['payment' => 'Bukti tidak lagi menunggu verifikasi.']);
            $policy = app(RentalPaymentDeadline::class);
            if ($unit->status === 'confirmed' && $policy->isDue($unit) && $unit->payableAmount() !== null && $policy->timelyCoverage($unit) >= $unit->payableAmount()) {
                $unit->update(['payment_review_hold' => true, 'payment_review_resolved' => false]);
            }
            $payment->update(['status' => 'rejected', 'verified_at' => now(), 'verified_by' => $request->user()->id, 'admin_note' => $data['reason']]);
            app(RentalNotifications::class)->customer($unit->order, 'Bukti perlu diperbaiki', 'Periksa alasan penolakan bukti pembayaran unit '.$unit->product_name.'.');
        });
        return back()->with('success', 'Bukti pembayaran ditolak dengan alasan.');
    }
}
