<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
use App\Models\RentalPaymentRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RentalPaymentController extends Controller
{
    public function store(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:100000000'], 'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $path = $request->file('proof')->store('rental-payment-proofs', 'local');
        if (! $path) throw ValidationException::withMessages(['proof' => 'Bukti tidak dapat disimpan.']);
        try {
            DB::transaction(function () use ($unit, $data, $path) {
                $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
                if ($unit->status !== 'confirmed') throw ValidationException::withMessages(['payment' => 'Unit harus dikonfirmasi terlebih dahulu.']);
                $total = $unit->payableAmount();
                if ($total === null) throw ValidationException::withMessages(['payment' => 'Setujui penawaran ongkos terlebih dahulu.']);
                $allocated = $unit->allocatedNet();
                if ($allocated + $data['amount'] > $total) throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa tagihan, termasuk bukti yang menunggu verifikasi.']);
                $unit->payments()->create(['amount' => $data['amount'], 'proof_path' => $path, 'submitted_at' => now()]);
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
            if ($payment->status !== 'pending' || $unit->status !== 'confirmed') throw ValidationException::withMessages(['payment' => 'Pembayaran tidak lagi dapat diverifikasi.']);
            $verified = $unit->verifiedNet();
            if ($unit->payableAmount() === null || $verified + $payment->amount > $unit->payableAmount()) throw ValidationException::withMessages(['payment' => 'Nominal melebihi sisa tagihan.']);
            $time = now('Asia/Jakarta');
            $entry = FinanceEntry::create(['entry_date' => $time->toDateString(), 'period_key' => $time->format('Y-m'), 'entry_type' => 'income', 'category' => 'income', 'amount' => $payment->amount, 'source_type' => 'rental_payment', 'source_id' => $payment->id, 'notes' => 'Pembayaran unit sewa #'.$unit->id, 'created_by' => $request->user()->id]);
            $payment->update(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $request->user()->id, 'finance_entry_id' => $entry->id]);
        });
        return back()->with('success', 'Pembayaran diverifikasi dan dicatat dalam buku kas.');
    }

    public function refund(Request $request, RentalPayment $payment)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:100000000'], 'reason' => ['required', 'string', 'max:500'], 'request_key' => ['required', 'uuid'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $payment, $data) {
            RentalOrderUnit::lockForUpdate()->findOrFail($payment->rental_order_unit_id);
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
        }, 3);
        return back()->with('success', 'Pengembalian dicatat dalam buku kas.');
    }

    public function reject(Request $request, RentalPayment $payment)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $payment, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($payment->rental_order_unit_id);
            $payment = RentalPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'pending') throw ValidationException::withMessages(['payment' => 'Bukti tidak lagi menunggu verifikasi.']);
            $payment->update(['status' => 'rejected', 'verified_at' => now(), 'verified_by' => $request->user()->id, 'admin_note' => $data['reason']]);
        });
        return back()->with('success', 'Bukti pembayaran ditolak dengan alasan.');
    }
}
