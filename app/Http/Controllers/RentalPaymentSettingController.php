<?php

namespace App\Http\Controllers;

use App\Models\RentalPaymentSetting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalPaymentSettingController extends Controller
{
    public function edit()
    {
        return view('rental.payment-settings', ['settings' => RentalPaymentSetting::find(1)]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['instructions' => ['nullable', 'string', 'max:3000']]);
        DB::transaction(function () use ($data) {
            $setting = RentalPaymentSetting::firstOrCreate(['id' => 1]);
            $setting = RentalPaymentSetting::lockForUpdate()->findOrFail($setting->id);
            $before = $setting->only('instructions');
            $setting->update(['instructions' => $data['instructions'] ?? null]);
            Audit::log('rental.payment_settings.update', $setting, before: $before, after: $setting->only('instructions'));
        }, 3);
        return back()->with('success', 'Petunjuk pembayaran diperbarui.');
    }
}
