<?php

namespace App\Http\Controllers;

use App\Models\RentalOrderUnit;
use App\Services\RentalNotifications;
use App\Support\RentalLabels;
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
            if ($next === 'production' && ! $unit->designApprovedForProduction()) {
                throw ValidationException::withMessages(['progress' => 'Pratinjau terbaru harus disetujui pelanggan, atau admin melanjutkan dengan catatan setelah tenggat respons desain.']);
            }
            if ($next === 'handed_over') {
                if (! $unit->designApprovedForProduction()) {
                    throw ValidationException::withMessages(['progress' => 'Desain belum disetujui atau belum diputuskan admin; serah terima ditahan.']);
                }
                $total = $unit->payableAmount();
                if ($total === null || $unit->verifiedNet() < $total) {
                    throw ValidationException::withMessages(['progress' => 'Pembayaran harus lunas dan terverifikasi sebelum serah terima.']);
                }
            }
            $unit->update(['progress' => $next]);
            $unit->progressEvents()->create(['progress' => $next, 'recorded_by' => $request->user()->id, 'note' => $data['note'] ?? null]);
            // Notifikasi menyebut langkah yang baru dicapai + apa yang perlu
            // dilakukan pelanggan. Sebelumnya isinya "perkembangan telah
            // diperbarui", yang tetap memaksa pelanggan membuka WhatsApp untuk
            // bertanya "sudah sampai mana?".
            $hint = RentalLabels::progressHint($next);
            $note = trim((string) ($data['note'] ?? ''));
            app(RentalNotifications::class)->customer(
                $unit->order,
                'Progres: '.RentalLabels::progress($next),
                'Unit '.$unit->product_name.$unit->variantLabel().': '.$hint.($note !== '' ? ' Catatan admin: '.rtrim($note, ' .').'.' : '')
            );
        }, 3);
        return back()->with('success', 'Perkembangan unit diperbarui.');
    }
}
