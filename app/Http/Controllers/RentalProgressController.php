<?php

namespace App\Http\Controllers;

use App\Models\RentalOrderUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalProgressController extends Controller
{
    public function advance(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate(['expected_progress' => ['required', 'in:waiting,production,ready,handed_over'], 'note' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $unit, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'confirmed' || $unit->progress !== $data['expected_progress']) {
                throw ValidationException::withMessages(['progress' => 'Status unit berubah atau tidak lagi aktif. Muat ulang halaman.']);
            }
            $next = ['waiting' => 'production', 'production' => 'ready', 'ready' => 'handed_over', 'handed_over' => 'returned'][$unit->progress];
            if ($next === 'production' && $unit->previews()->first()?->status !== 'approved') {
                throw ValidationException::withMessages(['progress' => 'Preview terbaru harus disetujui pelanggan sebelum pengerjaan.']);
            }
            if ($next === 'handed_over') {
                $total = $unit->payableAmount();
                if ($total === null || $unit->payments()->where('status', 'verified')->sum('amount') < $total) {
                    throw ValidationException::withMessages(['progress' => 'Pembayaran harus lunas dan terverifikasi sebelum serah terima.']);
                }
            }
            $unit->update(['progress' => $next]);
            $unit->progressEvents()->create(['progress' => $next, 'recorded_by' => $request->user()->id, 'note' => $data['note'] ?? null]);
        }, 3);
        return back()->with('success', 'Perkembangan unit diperbarui.');
    }
}
