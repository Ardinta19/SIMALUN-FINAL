<?php
namespace App\Http\Controllers;

use App\Models\RentalCategory;
use App\Models\RentalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class RentalCatalogController extends Controller
{
    public function index(Request $request)
    {
        $categories = RentalCategory::where('is_active', true)->orderBy('name')->get();
        $products = RentalProduct::with('category')->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('rental_category_id', $request->input('category')))
            ->latest()->paginate(12)->withQueryString();
        return view('catalog.index', compact('products', 'categories'));
    }

    public function show(RentalProduct $product)
    {
        abort_unless($product->is_active && $product->category->is_active, 404);
        return view('catalog.show', compact('product'));
    }

    public function admin()
    {
        return view('catalog.admin', ['categories' => RentalCategory::orderBy('name')->get(), 'products' => RentalProduct::with('category')->latest()->paginate(20)]);
    }

    public function categoryStore(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:rental_categories,name']]);
        RentalCategory::create($data);
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function productStore(Request $request)
    {
        $data = $this->validateProduct($request);
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('catalog', 'public');
        RentalProduct::create($data);
        return back()->with('success', 'Papan berhasil ditambahkan.');
    }

    public function productUpdate(Request $request, RentalProduct $product)
    {
        $data = $this->validateProduct($request);
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('catalog', 'public');
        DB::transaction(function () use ($product, $data) {
            $locked = RentalProduct::lockForUpdate()->findOrFail($product->id);
            if ((int) $data['stock'] < $locked->stock && \App\Models\RentalOrderUnit::where('rental_product_id', $locked->id)->where('status', 'confirmed')->exists()) {
                throw ValidationException::withMessages(['stock' => 'Stok tidak dapat dikurangi selama masih ada reservasi terkonfirmasi.']);
            }
            $locked->update($data);
        });
        return back()->with('success', 'Papan berhasil diperbarui.');
    }

    public function toggle(RentalProduct $product)
    {
        $product->update(['is_active' => ! $product->is_active]);
        return back()->with('success', 'Status papan diperbarui.');
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
