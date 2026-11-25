<?php

namespace App\Http\Controllers;

use App\Models\RentalAddon;
use App\Models\RentalOrderUnitAddon;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Item tambahan yang bisa dipilih pelanggan saat checkout (lilin, kartu ucapan,
 * bunga ekstra, dan sejenisnya). Daftarnya global untuk semua produk, sesuai
 * keputusan pemilik: satu katalog tambahan untuk seluruh papan.
 */
class RentalAddonController extends Controller
{
    public function index()
    {
        return view('rental.addons', [
            'addons' => RentalAddon::orderBy('sort_order')->orderBy('name')->get(),
            // Item yang sudah terlanjur dipakai pesanan tidak boleh dihapus;
            // panel menandainya agar admin memilih "nonaktifkan".
            'usedIds' => RentalOrderUnitAddon::query()->whereNotNull('rental_addon_id')->distinct()->pluck('rental_addon_id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $path = $this->storeImage($request);

        try {
            DB::transaction(function () use ($data, $path) {
                $addon = RentalAddon::create($data + ['image_path' => $path]);
                Audit::log('rental.addon.create', $addon, after: $this->snapshot($addon->refresh()));
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Item tambahan ditambahkan. Pelanggan bisa memilihnya mulai checkout berikutnya.');
    }

    public function update(Request $request, RentalAddon $addon)
    {
        $data = $this->validated($request, $addon);
        $path = $this->storeImage($request);

        try {
            $oldPath = DB::transaction(function () use ($addon, $data, $path) {
                $locked = RentalAddon::lockForUpdate()->findOrFail($addon->id);
                $before = $this->snapshot($locked);
                $old = $locked->image_path;

                $locked->update($path ? $data + ['image_path' => $path] : $data);
                Audit::log('rental.addon.update', $locked, before: $before, after: $this->snapshot($locked->refresh()));

                return $path ? $old : null;
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            throw $e;
        }

        if ($oldPath) Storage::disk('public')->delete($oldPath);

        return back()->with('success', 'Item tambahan diperbarui; pesanan lama tetap memakai harga saat checkout.');
    }

    public function toggle(RentalAddon $addon)
    {
        DB::transaction(function () use ($addon) {
            $locked = RentalAddon::lockForUpdate()->findOrFail($addon->id);
            $before = $this->snapshot($locked);
            $locked->update(['is_active' => ! $locked->is_active]);
            Audit::log('rental.addon.toggle', $locked, before: $before, after: $this->snapshot($locked->refresh()));
        });

        return back()->with('success', 'Status item tambahan diperbarui.');
    }

    public function destroy(RentalAddon $addon)
    {
        if (RentalOrderUnitAddon::where('rental_addon_id', $addon->id)->exists()) {
            throw ValidationException::withMessages([
                'addon' => 'Item ini sudah dipakai pesanan, jadi tidak bisa dihapus. Nonaktifkan saja agar tidak muncul di checkout baru.',
            ]);
        }

        $path = DB::transaction(function () use ($addon) {
            $locked = RentalAddon::lockForUpdate()->findOrFail($addon->id);
            $before = $this->snapshot($locked);
            $locked->delete();
            Audit::log('rental.addon.delete', null, before: $before, summary: 'Item tambahan dihapus: '.$before['name']);

            return $before['image_path'];
        });

        if ($path) Storage::disk('public')->delete($path);

        return back()->with('success', 'Item tambahan dihapus.');
    }

    private function validated(Request $request, ?RentalAddon $addon = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // `image` bukan kolom; berkasnya disimpan terpisah ke image_path.
        unset($data['image']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) return null;

        $path = $request->file('image')->store('rental-addons', 'public');
        if (! $path) throw ValidationException::withMessages(['image' => 'Foto tidak dapat disimpan. Silakan coba lagi.']);

        return $path;
    }

    /** @return array<string, mixed> */
    private function snapshot(RentalAddon $addon): array
    {
        return $addon->only(['name', 'description', 'price', 'image_path', 'is_active', 'sort_order']);
    }
}
