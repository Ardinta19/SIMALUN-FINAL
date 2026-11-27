<?php

namespace App\Http\Controllers;

use App\Models\RentalProduct;
use App\Services\RentalAvailability;
use Illuminate\Validation\ValidationException;
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

        // Satu baris keranjang = satu kelompok unit dengan papan dan tanggal
        // yang sama. Tanpa pengelompokan ini, menambah lima unit menampilkan
        // lima kartu hampir identik dan pelanggan harus menekan "Hapus" lima
        // kali untuk membatalkannya.
        $groups = [];

        foreach ($cart as $id => $item) {
            $kunci = $item['product_id'].'|'.($item['starts_on'] ?? '').'|'.($item['ends_on'] ?? '');

            if (! isset($groups[$kunci])) {
                $groups[$kunci] = [
                    'product_id' => (int) $item['product_id'],
                    'starts_on' => $item['starts_on'] ?? null,
                    'ends_on' => $item['ends_on'] ?? null,
                    'ids' => [],
                ];
            }

            $groups[$kunci]['ids'][] = (string) $id;
        }

        $groups = array_values($groups);

        return view('catalog.cart', compact('cart', 'products', 'unavailableCount', 'groups'));
    }

    public function store(Request $request, RentalProduct $product)
    {
        abort_unless($product->is_active && $product->category->is_active && $product->stock > 0, 404);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'], 'next' => ['nullable', 'in:cart,checkout'],
            'starts_on' => ['nullable', 'required_with:ends_on', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'required_with:starts_on', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ]);
        $cart = $request->session()->get('rental_cart', []);
        if (count($cart) + $data['quantity'] > 50) {
            return back()->withErrors(['quantity' => 'Keranjang maksimal 50 unit.']);
        }
        $entry = ['product_id' => $product->id];
        if (! empty($data['starts_on'])) {
            // Masa lalu, jendela satu tahun, dan durasi memakai aturan yang sama
            // dengan filter katalog dan halaman detail.
            RentalAvailability::assertBookablePeriod($data['starts_on'], $data['ends_on']);
            $entry += ['starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on']];
            $this->checkDatedCart($product, $cart, $entry, (int) $data['quantity']);
        }
        for ($i = 0; $i < $data['quantity']; $i++) {
            $cart[(string) Str::uuid()] = $entry;
        }
        $request->session()->put('rental_cart', $cart);

        return redirect()->route(($data['next'] ?? 'cart') === 'checkout' ? 'rental.checkout' : 'cart.index')->with('success', 'Papan ditambahkan ke keranjang.');
    }

    /**
     * Kurangi satu unit dari kelompok yang sama (papan + tanggal identik).
     *
     * Unit terakhir dipilih supaya urutan entri lain tidak bergeser, dan
     * tombol − selalu mengurangi tepat satu unit dari baris yang ditekan.
     */
    public function decrement(Request $request, string $item)
    {
        $cart = $request->session()->get('rental_cart', []);
        abort_unless(array_key_exists($item, $cart), 404);

        $target = $cart[$item];
        $ids = array_keys(array_filter($cart, fn ($entry) => $this->sameGroup($entry, $target)));

        unset($cart[end($ids)]);
        $request->session()->put('rental_cart', $cart);

        return back(303)
            ->withFragment('cart-unit-'.$target['product_id'])
            ->with('success', 'Satu unit dikurangi dari keranjang.');
    }

    /** Hapus seluruh unit pada kelompok yang sama dalam satu tindakan. */
    public function clearGroup(Request $request, string $item)
    {
        $cart = $request->session()->get('rental_cart', []);
        abort_unless(array_key_exists($item, $cart), 404);

        $target = $cart[$item];
        $terhapus = 0;

        foreach ($cart as $id => $entry) {
            if ($this->sameGroup($entry, $target)) {
                unset($cart[$id]);
                $terhapus++;
            }
        }

        $request->session()->put('rental_cart', $cart);

        return back(303)->with('success', $terhapus === 1
            ? 'Satu unit dihapus dari keranjang.'
            : $terhapus.' unit dihapus dari keranjang.');
    }

    private function sameGroup(array $a, array $b): bool
    {
        return (int) $a['product_id'] === (int) $b['product_id']
            && ($a['starts_on'] ?? null) === ($b['starts_on'] ?? null)
            && ($a['ends_on'] ?? null) === ($b['ends_on'] ?? null);
    }

    public function duplicate(Request $request, string $item)
    {
        $cart = $request->session()->get('rental_cart', []);
        abort_unless(array_key_exists($item, $cart), 404);
        if (count($cart) >= 50) throw ValidationException::withMessages(['quantity' => 'Keranjang maksimal 50 unit.']);
        $entry = $cart[$item];
        $product = RentalProduct::with('category')->find($entry['product_id']);
        if (! $product || ! $product->is_active || ! $product->category?->is_active || $product->stock < 1) {
            throw ValidationException::withMessages(['quantity' => 'Papan ini tidak lagi tersedia.']);
        }
        if (! empty($entry['starts_on']) && ! empty($entry['ends_on'])) {
            if ($entry['starts_on'] < now('Asia/Jakarta')->toDateString()) {
                throw ValidationException::withMessages(['quantity' => 'Tanggal unit ini sudah lewat. Pilih tanggal baru di katalog.']);
            }
            $this->checkDatedCart($product, $cart, $entry, 1);
        }
        $cart[(string) Str::uuid()] = $entry;
        $request->session()->put('rental_cart', $cart);
        return back()->with('success', 'Satu unit ditambahkan. Jadwal dan detail setiap unit tetap dapat diubah saat checkout.');
    }

    private function checkDatedCart(RentalProduct $product, array $cart, array $newEntry, int $quantity): void
    {
        $ranges = [];
        foreach ($cart as $entry) {
            if ((int) $entry['product_id'] === (int) $product->id && ! empty($entry['starts_on']) && ! empty($entry['ends_on'])) {
                $ranges[] = ['starts_on' => $entry['starts_on'], 'ends_on' => $entry['ends_on']];
            }
        }
        for ($i = 0; $i < $quantity; $i++) $ranges[] = ['starts_on' => $newEntry['starts_on'], 'ends_on' => $newEntry['ends_on']];
        if (! app(RentalAvailability::class)->canFitRequested($product, $ranges)) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah papan tidak tersedia pada tanggal tersebut. Kurangi unit atau pilih tanggal lain.']);
        }
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
