<?php

namespace App\Http\Controllers;

use App\Models\RentalDesignPreview;
use App\Models\RentalOrderUnit;
use App\Services\RentalDesignDeadline;
use App\Services\RentalNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalDesignController extends Controller
{
    public function store(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $path = $request->file('image')->store('rental-designs', 'local');
        if (! $path) throw ValidationException::withMessages(['image' => 'Gambar tidak dapat disimpan.']);
        try {
            DB::transaction(function () use ($request, $unit, $data, $path) {
                $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
                if ($unit->status !== 'confirmed' || $unit->progress !== 'waiting') throw ValidationException::withMessages(['design' => 'Pratinjau hanya dapat diunggah sebelum pengerjaan dimulai pada unit yang dikonfirmasi.']);
                $unit->previews()->where('status', 'pending')->update(['status' => 'superseded']);
                $version = (int) $unit->previews()->max('version') + 1;
                $unit->previews()->create(['version' => $version, 'image_path' => $path, 'admin_note' => $data['admin_note'] ?? null, 'created_by' => $request->user()->id]);
                // Versi baru selalu memerlukan persetujuan sendiri.
                if ($unit->design_override_at !== null) $unit->update(['design_override_at' => null, 'design_override_by' => null, 'design_override_version' => null, 'design_override_note' => null]);
                app(RentalNotifications::class)->customer($unit->order, 'Pratinjau desain tersedia', 'Pratinjau desain unit '.$unit->product_name.' menunggu respons Anda.');
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        return back()->with('success', 'Pratinjau desain diunggah.');
    }

    public function image(Request $request, RentalDesignPreview $preview)
    {
        $preview->loadMissing('unit.order');
        $user = $request->user();
        abort_unless($user->role === 'admin' || ($user->role === 'customer' && $preview->unit->order->customer_id === $user->id), 403);
        abort_unless(Storage::disk('local')->exists($preview->image_path), 404);
        return Storage::disk('local')->response($preview->image_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function respond(Request $request, RentalDesignPreview $preview)
    {
        abort_unless($preview->unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'revision'])], 'note' => ['nullable', 'string', 'max:2000', 'required_if:decision,revision']]);
        DB::transaction(function () use ($preview, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($preview->rental_order_unit_id);
            $preview = RentalDesignPreview::lockForUpdate()->findOrFail($preview->id);
            if ($unit->status !== 'confirmed' || $preview->status !== 'pending') throw ValidationException::withMessages(['design' => 'Pratinjau ini tidak lagi menunggu respons.']);
            $preview->update(['status' => $data['decision'], 'customer_note' => $data['note'] ?? null, 'responded_at' => now()]);
            // Pelanggan sudah merespons: keputusan admin tanpa persetujuan tidak berlaku lagi.
            if ($unit->design_override_at !== null) $unit->update(['design_override_at' => null, 'design_override_by' => null, 'design_override_version' => null, 'design_override_note' => null]);
            app(RentalNotifications::class)->admins($unit->order, 'Respons desain', 'Pelanggan '.($data['decision'] === 'approved' ? 'menyetujui desain' : 'meminta revisi').' unit '.$unit->product_name.'.');
        });
        return back()->with('success', $data['decision'] === 'approved' ? 'Desain disetujui.' : 'Permintaan revisi dikirim.');
    }

    /**
     * Admin memulai pengerjaan tanpa persetujuan pelanggan setelah tenggat respons
     * desain lewat. Wajib beralasan dan dikonfirmasi, dicatat di riwayat progres,
     * dan hanya berlaku untuk versi preview yang sedang menunggu.
     */
    public function override(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:10', 'max:2000'],
            'confirmed' => ['accepted'],
        ]);
        $deadlines = app(RentalDesignDeadline::class);
        DB::transaction(function () use ($request, $unit, $data, $deadlines) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'confirmed' || $unit->progress !== 'waiting') throw ValidationException::withMessages(['design' => 'Keputusan desain hanya berlaku untuk unit yang belum dikerjakan.']);
            $latest = $unit->previews()->first();
            if (! $latest || $latest->status !== 'pending') throw ValidationException::withMessages(['design' => 'Tidak ada pratinjau yang menunggu respons pelanggan.']);
            if (! $deadlines->reached($unit)) throw ValidationException::withMessages(['design' => 'Tenggat respons desain belum tercapai. Tunggu sampai batas atau hubungi pelanggan.']);
            $unit->update(['design_override_at' => now(), 'design_override_by' => $request->user()->id, 'design_override_version' => $latest->version, 'design_override_note' => $data['note']]);
            $unit->progressEvents()->create(['progress' => $unit->progress, 'recorded_by' => $request->user()->id, 'note' => 'Desain versi '.$latest->version.' dilanjutkan tanpa persetujuan pelanggan. Alasan: '.$data['note']]);
            app(RentalNotifications::class)->customer($unit->order, 'Pengerjaan dimulai', 'Admin memulai pengerjaan unit '.$unit->product_name.' dengan pratinjau versi '.$latest->version.' karena batas respons terlewati. Hubungi admin bila perlu perubahan.');
        }, 3);
        return back()->with('success', 'Pengerjaan dilanjutkan tanpa persetujuan pelanggan. Keputusan tercatat di riwayat unit.');
    }
}
