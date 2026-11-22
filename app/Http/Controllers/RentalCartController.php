<?php

namespace App\Http\Controllers;

use App\Models\RentalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RentalCartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('rental_cart', []);
        $products = RentalProduct::with('category')->whereIn('id', array_column($cart, 'product_id'))->get()->keyBy('id');

        $unavailableCount = collect($cart)->filter(function ($item) use ($products) {
            $product = $products->get($item['product_id']);
            return ! $product || ! $product->is_active || ! $product->category?->is_active || $product->stock < 1;
        })->count();

        return view('catalog.cart', compact('cart', 'products', 'unavailableCount'));
    }

    public function store(Request $request, RentalProduct $product)
    {
        abort_unless($product->is_active && $product->category->is_active && $product->stock > 0, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20'], 'next' => ['nullable', 'in:cart,checkout']]);
        $cart = $request->session()->get('rental_cart', []);
        if (count($cart) + $data['quantity'] > 50) {
            return back()->withErrors(['quantity' => 'Keranjang maksimal 50 unit.']);
        }
        // Setiap unit merupakan baris terpisah agar nantinya bisa memiliki jadwal dan desain sendiri.
        for ($i = 0; $i < $data['quantity']; $i++) {
            $cart[(string) Str::uuid()] = ['product_id' => $product->id];
        }
        $request->session()->put('rental_cart', $cart);

        return redirect()->route(($data['next'] ?? 'cart') === 'checkout' ? 'rental.checkout' : 'cart.index')->with('success', 'Papan ditambahkan ke keranjang.');
    }

    public function destroy(Request $request, string $item)
    {
        $cart = $request->session()->get('rental_cart', []);
        abort_unless(array_key_exists($item, $cart), 404);
        unset($cart[$item]);
        $request->session()->put('rental_cart', $cart);

        return back()->with('success', 'Papan dihapus dari keranjang.');
    }
}
