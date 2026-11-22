<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
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
                // Tarif pengantaran belum dibuat; hindari menyajikan total yang belum disepakati sebagai tagihan final.
                if ($unit->delivery_method !== 'pickup' || $unit->return_method !== 'dropoff') throw ValidationException::withMessages(['payment' => 'Pembayaran untuk pengantaran belum tersedia sampai ongkos disepakati.']);
                $allocated = $unit->payments()->whereIn('status', ['pending', 'verified'])->sum('amount');
                if ($allocated + $data['amount'] > $unit->rental_amount) throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa tagihan, termasuk bukti yang menunggu verifikasi.']);
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
            $verified = $unit->payments()->where('status', 'verified')->sum('amount');
            if ($verified + $payment->amount > $unit->rental_amount) throw ValidationException::withMessages(['payment' => 'Nominal melebihi sisa tagihan.']);
            $entry = FinanceEntry::create(['entry_date' => today(), 'period_key' => now()->format('Y-m'), 'entry_type' => 'income', 'category' => 'income', 'amount' => $payment->amount, 'source_type' => 'rental_payment', 'source_id' => $payment->id, 'notes' => 'Pembayaran unit sewa #'.$unit->id, 'created_by' => $request->user()->id]);
            $payment->update(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $request->user()->id, 'finance_entry_id' => $entry->id]);
        });
        return back()->with('success', 'Pembayaran diverifikasi dan dicatat dalam buku kas.');
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
