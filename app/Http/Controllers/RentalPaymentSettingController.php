<?php

namespace App\Http\Controllers;

use App\Models\RentalPaymentSetting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RentalPaymentSettingController extends Controller
{
    public function edit()
    {
        return view('rental.payment-settings', ['settings' => RentalPaymentSetting::find(1)]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'instructions' => ['nullable', 'string', 'max:3000'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^62[0-9]{8,13}$/'],
            'qris' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_qris' => ['nullable', 'boolean'],
        ], ['whatsapp_number.regex' => 'Nomor WhatsApp harus diawali 62 dan hanya berisi angka (10–15 digit).']);
        if ($request->hasFile('qris') && $request->boolean('remove_qris')) {
            throw ValidationException::withMessages(['qris' => 'Pilih unggah QRIS baru atau hapus QRIS, bukan keduanya.']);
        }
        $path = null;
        if ($request->hasFile('qris')) {
            $path = $request->file('qris')->store('rental-qris', 'local');
            if (! $path) throw ValidationException::withMessages(['qris' => 'Gambar QRIS tidak dapat disimpan. Silakan coba lagi.']);
        }
        try {
            $oldPath = DB::transaction(function () use ($data, $path, $request) {
                $setting = RentalPaymentSetting::firstOrCreate(['id' => 1]);
                $setting = RentalPaymentSetting::lockForUpdate()->findOrFail($setting->id);
                $before = $setting->only('instructions', 'whatsapp_number', 'qris_path');
                $oldPath = $setting->qris_path;
                $newPath = $path ?: ($request->boolean('remove_qris') ? null : $oldPath);
                $setting->update(['instructions' => $data['instructions'] ?? null, 'whatsapp_number' => $data['whatsapp_number'] ?? null, 'qris_path' => $newPath]);
                Audit::log('rental.payment_settings.update', $setting, before: $before, after: $setting->only('instructions', 'whatsapp_number', 'qris_path'));
                return $oldPath !== $newPath ? $oldPath : null;
            }, 3);
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        if ($oldPath) Storage::disk('local')->delete($oldPath);
        return back()->with('success', 'Petunjuk pembayaran diperbarui.');
    }
    public function qris(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'customer'], true), 403);
        $path = RentalPaymentSetting::find(1)?->qris_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
