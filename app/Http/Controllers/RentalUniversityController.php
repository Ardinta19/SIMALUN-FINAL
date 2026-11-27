<?php

namespace App\Http\Controllers;

use App\Models\RentalUniversity;
use App\Support\Audit;
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
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $university = RentalUniversity::create($data);
            Audit::log('rental.university.create', $university, after: $this->snapshot($university->refresh()));
        });
        return back()->with('success', 'Universitas dan tarif ditambahkan.');
    }

    public function update(Request $request, RentalUniversity $university)
    {
        $data = $this->validated($request, $university);
        DB::transaction(function () use ($university, $data) {
            $locked = RentalUniversity::lockForUpdate()->findOrFail($university->id);
            $before = $this->snapshot($locked);
            $locked->update($data);
            Audit::log('rental.university.update', $locked, before: $before, after: $this->snapshot($locked));
        });
        return back()->with('success', 'Tarif diperbarui; pesanan lama tidak berubah.');
    }

    public function toggle(RentalUniversity $university)
    {
        DB::transaction(function () use ($university) {
            $university = RentalUniversity::lockForUpdate()->findOrFail($university->id);
            $before = $this->snapshot($university);
            $university->update(['is_active' => ! $university->is_active]);
            Audit::log('rental.university.toggle', $university, before: $before, after: $this->snapshot($university));
        });
        return back()->with('success', 'Status universitas diperbarui.');
    }

    /**
     * Hapus universitas dan tarifnya.
     *
     * Setiap unit pesanan menyimpan salinan nama dan tarifnya sendiri
     * (`delivery_university`, `delivery_university_fee`), jadi menghapus baris
     * ini tidak mengubah riwayat pesanan sama sekali.
     */
    public function destroy(RentalUniversity $university)
    {
        $nama = $university->name;
        DB::transaction(function () use ($university) {
            $locked = RentalUniversity::lockForUpdate()->findOrFail($university->id);
            $before = $this->snapshot($locked);
            $locked->delete();
            Audit::log('rental.university.delete', null, before: $before, summary: 'Universitas dihapus: '.$nama);
        });

        return back()->with('success', 'Universitas '.$nama.' dihapus. Pesanan lama tetap memakai tarif yang tersimpan di pesanannya.');
    }

    private function snapshot(RentalUniversity $university): array
    {
        return $university->only(['name', 'delivery_fee', 'collection_fee', 'is_active']);
    }

    private function validated(Request $request, ?RentalUniversity $university = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:160', Rule::unique('rental_universities', 'name')->ignore($university?->id)], 'delivery_fee' => ['required', 'integer', 'min:0', 'max:100000000'], 'collection_fee' => ['required', 'integer', 'min:0', 'max:100000000']]);
    }
}
