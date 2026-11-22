<?php

namespace App\Http\Controllers;

use App\Models\RentalUniversity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RentalUniversityController extends Controller
{
    public function index()
    {
        return view('rental.universities', ['universities' => RentalUniversity::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        RentalUniversity::create($this->validated($request));
        return back()->with('success', 'Universitas dan tarif ditambahkan.');
    }

    public function update(Request $request, RentalUniversity $university)
    {
        $university->update($this->validated($request, $university));
        return back()->with('success', 'Tarif diperbarui; pesanan lama tidak berubah.');
    }

    public function toggle(RentalUniversity $university)
    {
        DB::transaction(function () use ($university) {
            $university = RentalUniversity::lockForUpdate()->findOrFail($university->id);
            $university->update(['is_active' => ! $university->is_active]);
        });
        return back()->with('success', 'Status universitas diperbarui.');
    }

    private function validated(Request $request, ?RentalUniversity $university = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:160', Rule::unique('rental_universities', 'name')->ignore($university?->id)], 'delivery_fee' => ['required', 'integer', 'min:0', 'max:100000000'], 'collection_fee' => ['required', 'integer', 'min:0', 'max:100000000']]);
    }
}
