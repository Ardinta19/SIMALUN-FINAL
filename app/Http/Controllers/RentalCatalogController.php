<?php
namespace App\Http\Controllers;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use App\Models\RentalDurationPackage;
use App\Services\RentalAvailability;
use App\Support\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class RentalCatalogController extends Controller
{
    public function index(Request $request, RentalAvailability $availability)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'], 'category' => ['nullable', 'integer', 'min:1'],
            // `after_or_equal` tidak dipakai: bila salah satu tanggal dikirim
            // sebagai array, Laravel melempar TypeError saat membandingkan.
            // Urutan tanggal diperiksa di validateRentalPeriod().
            'starts_on' => ['nullable', 'required_with:ends_on', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'required_with:starts_on', 'date_format:Y-m-d'],
        ]);
        $dates = ! empty($filters['starts_on']) ? ['starts_on' => $filters['starts_on'], 'ends_on' => $filters['ends_on']] : null;
        if ($dates) $this->validateRentalPeriod($dates);
        $categories = RentalCategory::where('is_active', true)->orderBy('name')->get();
        $query = RentalProduct::with('category')->withCount(['likes', 'reviews'])->withAvg('reviews', 'rating')
            ->when($request->user()?->role === 'customer', fn ($q) => $q->withExists(['likes as liked_by_customer' => fn ($likes) => $likes->where('user_id', $request->user()->id)]))
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->when(! empty($filters['q']), fn ($q) => $q->where('name', 'like', '%'.$filters['q'].'%'))
            ->when(! empty($filters['category']), fn ($q) => $q->where('rental_category_id', $filters['category']))
            ->latest()->orderByDesc('id');
        if (! $dates) {
            $products = $query->paginate(12)->withQueryString();
        } else {
            // Filter before pagination so full pages never hide available items.
            $candidates = $query->where('stock', '>', 0)->get();
            $available = $availability->minimumAvailableForProducts($candidates, $dates['starts_on'], $dates['ends_on']);
            $matching = $candidates->filter(function ($product) use ($available) {
                $product->available_for_period = $available[$product->id];
                return $product->available_for_period > 0;
            })->values();
            $page = max(1, (int) LengthAwarePaginator::resolveCurrentPage());
            $products = new LengthAwarePaginator($matching->forPage($page, 12)->values(), $matching->count(), 12, $page, ['path' => $request->url(), 'query' => $request->query()]);
        }
        // Kalender filter hanya memblokir tanggal yang penuh untuk SEMUA papan,
        // bukan untuk satu produk: filter ini mencari papan mana saja yang kosong.
        $blockedDates = $availability->datesWithNoAvailableProduct(
            RentalProduct::where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true))->get(['id', 'stock'])
        );
        return view('catalog.index', compact('products', 'categories', 'dates', 'blockedDates'));
    }

    private function validateRentalPeriod(array $data): void
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['starts_on'], 'Asia/Jakarta');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $data['ends_on'], 'Asia/Jakarta');
        // Urutan tanggal menempel pada kolom pengembalian; sisa aturannya
        // (masa lalu, jendela satu tahun, durasi) dipusatkan di service.
        if ($end->lt($start)) {
            throw ValidationException::withMessages(['ends_on' => 'Tanggal pengembalian tidak boleh sebelum tanggal acara dan penerimaan.']);
        }
        RentalAvailability::assertBookablePeriod($data['starts_on'], $data['ends_on']);
    }

    public function show(Request $request, RentalProduct $product, RentalAvailability $availability)
    {
        abort_unless($product->is_active && $product->category->is_active, 404);
        $product->loadCount(['likes', 'reviews'])->loadAvg('reviews', 'rating');
        if ($request->user()?->role === 'customer') $product->loadExists(['likes as liked_by_customer' => fn ($likes) => $likes->where('user_id', $request->user()->id)]);
        $product->load(['packages' => fn ($query) => $query->where('is_active', true)]);
        $available = null;
        if ($request->has('starts_on') || $request->has('ends_on')) {
            $data = $request->validate(['starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d']]);
            $this->validateRentalPeriod($data);
            $available = $availability->minimumAvailable($product, $data['starts_on'], $data['ends_on']);
        }
        // Tanggal yang sudah dibooking tidak boleh dipilih lagi di kalender.
        $bookedDates = $availability->fullyBookedDates($product);
        return view('catalog.show', compact('product', 'available', 'bookedDates'));
    }

    /**
     * Ketersediaan terbaru untuk kalender pemesanan. Dipanggil klien saat
     * kalender dibuka, supaya tanggal yang baru dibooking orang lain ikut
     * terkunci tanpa memuat ulang halaman.
     */
    public function availability(RentalProduct $product, RentalAvailability $availability)
    {
        abort_unless($product->is_active && $product->category?->is_active, 404);

        return response()->json([
            'product_id' => $product->id,
            'stock' => (int) $product->stock,
            'blocked' => $availability->fullyBookedDates($product),
            'fetched_at' => now('Asia/Jakarta')->toIso8601String(),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    public function admin()
    {
        return view('catalog.admin', ['categories' => RentalCategory::orderBy('name')->get(), 'products' => RentalProduct::with(['category', 'packages'])->latest()->paginate(20)]);
    }

    public function categoryStore(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:rental_categories,name']]);
        DB::transaction(function () use ($data) {
            $category = RentalCategory::create($data);
            Audit::log('rental.catalog.category.create', $category, after: $category->refresh()->only(['name', 'is_active']));
        });
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function categoryUpdate(Request $request, RentalCategory $category)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('rental_categories', 'name')->ignore($category->id)]]);
        DB::transaction(function () use ($category, $data) {
            $locked = RentalCategory::lockForUpdate()->findOrFail($category->id);
            $before = $locked->only(['name', 'is_active']);
            $locked->update($data);
            Audit::log('rental.catalog.category.update', $locked, before: $before, after: $locked->only(['name', 'is_active']));
        });
        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function categoryToggle(RentalCategory $category)
    {
        DB::transaction(function () use ($category) {
            $locked = RentalCategory::lockForUpdate()->findOrFail($category->id);
            $before = $locked->only(['name', 'is_active']);
            $locked->update(['is_active' => ! $locked->is_active]);
            Audit::log('rental.catalog.category.toggle', $locked, before: $before, after: $locked->only(['name', 'is_active']));
        });
        return back()->with('success', 'Status kategori diperbarui.');
    }

    public function packageStore(Request $request, RentalProduct $product)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'days' => ['required', 'integer', 'min:1', 'max:366'], 'price' => ['required', 'integer', 'min:0', 'max:100000000']]);
        DB::transaction(function () use ($product, $data) {
            $package = $product->packages()->create($data);
            Audit::log('rental.catalog.package.create', $package, after: $package->refresh()->only(['rental_product_id', 'name', 'days', 'price', 'is_active']));
        });
        return back()->with('success', 'Paket durasi ditambahkan.');
    }

    public function packageUpdate(Request $request, RentalDurationPackage $package)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'days' => ['required', 'integer', 'min:1', 'max:366'], 'price' => ['required', 'integer', 'min:0', 'max:100000000']]);
        DB::transaction(function () use ($package, $data) {
            $locked = RentalDurationPackage::lockForUpdate()->findOrFail($package->id);
            $before = $locked->only(['rental_product_id', 'name', 'days', 'price', 'is_active']);
            $locked->update($data);
            Audit::log('rental.catalog.package.update', $locked, before: $before, after: $locked->only(['rental_product_id', 'name', 'days', 'price', 'is_active']));
        });
        return back()->with('success', 'Paket durasi diperbarui; harga pesanan lama tidak berubah.');
    }

    public function packageToggle(RentalDurationPackage $package)
    {
        DB::transaction(function () use ($package) {
            $locked = RentalDurationPackage::lockForUpdate()->findOrFail($package->id);
            $before = $locked->only(['rental_product_id', 'name', 'days', 'price', 'is_active']);
            $locked->update(['is_active' => ! $locked->is_active]);
            Audit::log('rental.catalog.package.toggle', $locked, before: $before, after: $locked->only(['rental_product_id', 'name', 'days', 'price', 'is_active']));
        });
        return back()->with('success', 'Status paket diperbarui.');
    }

    public function productStore(Request $request)
    {
        $data = $this->validateProduct($request);
        $path = $this->storeProductImage($request);
        unset($data['image']);
        if ($path) $data['image_path'] = $path;
        try {
            DB::transaction(function () use ($data) {
                $product = RentalProduct::create($data);
                Audit::log('rental.catalog.product.create', $product, after: $this->productSnapshot($product->refresh()));
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            throw $e;
        }
        return back()->with('success', 'Papan berhasil ditambahkan.');
    }

    public function productUpdate(Request $request, RentalProduct $product)
    {
        $data = $this->validateProduct($request);
        $path = $this->storeProductImage($request);
        unset($data['image']);
        if ($path) $data['image_path'] = $path;
        try {
            $oldPath = DB::transaction(function () use ($product, $data) {
                $locked = RentalProduct::lockForUpdate()->findOrFail($product->id);
                if ((int) $data['stock'] < $locked->stock && \App\Models\RentalOrderUnit::where('rental_product_id', $locked->id)->whereIn('status', \App\Models\RentalOrderUnit::RESERVING_STATUSES)->exists()) {
                    throw ValidationException::withMessages(['stock' => 'Stok tidak dapat dikurangi selama masih ada pesanan yang menahan tanggal (menunggu konfirmasi atau terkonfirmasi).']);
                }
                $before = $this->productSnapshot($locked);
                $oldPath = $locked->image_path;
                $locked->update($data);
                Audit::log('rental.catalog.product.update', $locked, before: $before, after: $this->productSnapshot($locked));
                return $oldPath;
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            throw $e;
        }
        // Hapus hanya foto katalog yang tidak lagi direferensikan produk mana pun.
        if ($path && $oldPath && $oldPath !== $path && preg_match('~^catalog/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$~i', $oldPath)
            && ! RentalProduct::where('image_path', $oldPath)->exists()) {
            Storage::disk('public')->delete($oldPath);
        }
        return back()->with('success', 'Papan berhasil diperbarui.');
    }

    public function toggle(RentalProduct $product)
    {
        DB::transaction(function () use ($product) {
            $locked = RentalProduct::lockForUpdate()->findOrFail($product->id);
            $before = $this->productSnapshot($locked);
            $locked->update(['is_active' => ! $locked->is_active]);
            Audit::log('rental.catalog.product.toggle', $locked, before: $before, after: $this->productSnapshot($locked));
        });
        return back()->with('success', 'Status papan diperbarui.');
    }

    private function storeProductImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) return null;
        $path = $request->file('image')->store('catalog', 'public');
        if (! $path) throw ValidationException::withMessages(['image' => 'Foto tidak dapat disimpan. Silakan coba lagi.']);
        return $path;
    }

    private function productSnapshot(RentalProduct $product): array
    {
        return $product->only(['rental_category_id', 'name', 'size', 'description', 'base_price', 'price_unit', 'stock', 'image_path', 'is_active']);
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'rental_category_id' => ['required', Rule::exists('rental_categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:160'], 'size' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:3000'], 'base_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'price_unit' => ['required', Rule::in(['day', 'event'])], 'stock' => ['required', 'integer', 'min:0', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }
}
