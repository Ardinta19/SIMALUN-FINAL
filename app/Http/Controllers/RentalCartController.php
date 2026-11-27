<?php

namespace App\Http\Controllers;

use App\Models\RentalProduct;
use App\Models\RentalProductVariant;
use App\Services\RentalAvailability;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RentalCartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('rental_cart', []);
        $products = RentalProduct::with(['category', 'variants'])->whereIn('id', array_column($cart, 'product_id'))->get()->keyBy('id');

        $unavailableCount = collect($cart)->filter(function ($item) use ($products) {
            $product = $products->get($item['product_id']);

            return ! $product || ! $product->is_active || ! $product->category?->is_active || ! $this->unitAvailable($product, $item);
        })->count();

        // Satu baris keranjang = satu kelompok unit dengan papan, varian, dan
        // tanggal yang sama. Tanpa pengelompokan ini, menambah lima unit
        // menampilkan lima kartu hampir identik dan pelanggan harus menekan
        // "Hapus" lima kali untuk membatalkannya.
        $groups = [];
        $variants = [];

        foreach ($cart as $id => $item) {
            $kunci = $this->groupKey($item);

            if (! isset($groups[$kunci])) {
                $groups[$kunci] = [
                    'key' => $kunci,
                    'product_id' => (int) $item['product_id'],
                    'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                    'starts_on' => $item['starts_on'] ?? null,
                    'ends_on' => $item['ends_on'] ?? null,
                    'ids' => [],
                ];
            }

            $groups[$kunci]['ids'][] = (string) $id;
        }

        foreach ($groups as $kunci => $group) {
            if ($group['variant_id']) {
                $variants[$kunci] = $products->get($group['product_id'])?->activeVariantList()->firstWhere('id', $group['variant_id']);
            }
        }

        $groups = array_values($groups);
        $cartTotal = collect($cart)->sum(fn (array $item) => (int) ($products->get($item['product_id'])?->base_price ?? 0));

        return view('catalog.cart', compact('cart', 'products', 'unavailableCount', 'groups', 'variants', 'cartTotal'));
    }

    public function store(Request $request, RentalProduct $product)
    {
        abort_unless($product->is_active && $product->category->is_active && $product->stock > 0, 404);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'], 'next' => ['nullable', 'in:cart,checkout'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'starts_on' => ['nullable', 'required_with:ends_on', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'required_with:starts_on', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ]);

        $variant = null;
        if ($product->hasVariants()) {
            // Papan bervarian wajib disertai warna yang dipilih; tanpa itu
            // stok per warna tidak bisa dihitung dan pesanan tidak bisa
            // dikonfirmasi dengan benar.
            if (empty($data['variant_id'])) {
                throw ValidationException::withMessages(['variant_id' => 'Pilih warna papan lebih dulu.']);
            }
            $variant = $product->findVariant($data['variant_id']);
            if (! $variant) {
                throw ValidationException::withMessages(['variant_id' => 'Warna yang dipilih sudah tidak tersedia. Muat ulang halaman katalog.']);
            }
            if ($variant->stock < 1) {
                throw ValidationException::withMessages(['variant_id' => 'Warna '.$variant->name.' sedang kosong. Pilih warna lain.']);
            }
        }

        $cart = $request->session()->get('rental_cart', []);
        if (count($cart) + $data['quantity'] > 50) {
            return back()->withErrors(['quantity' => 'Keranjang maksimal 50 unit.']);
        }
        $entry = ['product_id' => $product->id];
        if ($variant) {
            $entry['variant_id'] = $variant->id;
        }
        if (! empty($data['starts_on'])) {
            // Masa lalu, jendela satu tahun, dan durasi memakai aturan yang sama
            // dengan filter katalog dan halaman detail.
            RentalAvailability::assertBookablePeriod($data['starts_on'], $data['ends_on']);
            $entry += ['starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on']];
            $this->checkDatedCart($product, $cart, $entry, (int) $data['quantity']);
        } elseif ($variant && (int) $data['quantity'] > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => 'Stok warna '.$variant->name.' hanya '.$variant->stock.' unit.']);
        }
        for ($i = 0; $i < $data['quantity']; $i++) {
            $cart[(string) Str::uuid()] = $entry;
        }
        $request->session()->put('rental_cart', $cart);

        return redirect()->route(($data['next'] ?? 'cart') === 'checkout' ? 'rental.checkout' : 'cart.index')->with('success', 'Papan ditambahkan ke keranjang.');
    }

    /**
     * Kurangi satu unit dari kelompok yang sama (papan + warna + tanggal identik).
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

        if ($request->wantsJson()) return $this->liveResponse($request, $target);

        return back(303)
            ->withFragment('cart-group-'.substr(md5($this->groupKey($target)), 0, 8))
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

        if ($request->wantsJson()) return $this->liveResponse($request, $target);

        return back(303)->with('success', $terhapus === 1
            ? 'Satu unit dihapus dari keranjang.'
            : $terhapus.' unit dihapus dari keranjang.');
    }

    private function sameGroup(array $a, array $b): bool
    {
        return (int) $a['product_id'] === (int) $b['product_id']
            && (int) ($a['variant_id'] ?? 0) === (int) ($b['variant_id'] ?? 0)
            && ($a['starts_on'] ?? null) === ($b['starts_on'] ?? null)
            && ($a['ends_on'] ?? null) === ($b['ends_on'] ?? null);
    }

    /** Kunci kelompok yang sama di server dan di markup (dipakai pembaruan langsung). */
    private function groupKey(array $item): string
    {
        return (int) $item['product_id'].'|'.(int) ($item['variant_id'] ?? 0).'|'.($item['starts_on'] ?? '').'|'.($item['ends_on'] ?? '');
    }

    public function duplicate(Request $request, string $item)
    {
        $cart = $request->session()->get('rental_cart', []);
        abort_unless(array_key_exists($item, $cart), 404);
        if (count($cart) >= 50) throw ValidationException::withMessages(['quantity' => 'Keranjang maksimal 50 unit.']);
        $entry = $cart[$item];
        $product = RentalProduct::with(['category', 'variants'])->find($entry['product_id']);
        if (! $product || ! $product->is_active || ! $product->category?->is_active) {
            throw ValidationException::withMessages(['quantity' => 'Papan ini tidak lagi tersedia.']);
        }
        $variant = ! empty($entry['variant_id']) ? $product->findVariant($entry['variant_id']) : null;
        if (! $this->unitAvailable($product, $entry)) {
            throw ValidationException::withMessages(['quantity' => 'Papan ini tidak lagi tersedia.']);
        }
        if (! empty($entry['starts_on']) && ! empty($entry['ends_on'])) {
            if ($entry['starts_on'] < now('Asia/Jakarta')->toDateString()) {
                throw ValidationException::withMessages(['quantity' => 'Tanggal unit ini sudah lewat. Pilih tanggal baru di katalog.']);
            }
            $this->checkDatedCart($product, $cart, $entry, 1);
        } elseif ($variant) {
            $used = collect($cart)->filter(fn (array $row) => $this->sameGroup($row, $entry))->count();
            if ($used + 1 > $variant->stock) {
                throw ValidationException::withMessages(['quantity' => 'Stok warna '.$variant->name.' hanya '.$variant->stock.' unit.']);
            }
        }
        $cart[(string) Str::uuid()] = $entry;
        $request->session()->put('rental_cart', $cart);
        if ($request->wantsJson()) return $this->liveResponse($request, $entry);

        return back()->with('success', 'Satu unit ditambahkan. Jadwal dan detail setiap unit tetap dapat diubah saat checkout.');
    }

    private function checkDatedCart(RentalProduct $product, array $cart, array $newEntry, int $quantity): void
    {
        $ranges = [];
        foreach ($cart as $entry) {
            if ((int) $entry['product_id'] === (int) $product->id && ! empty($entry['starts_on']) && ! empty($entry['ends_on'])) {
                $ranges[] = [
                    'starts_on' => $entry['starts_on'],
                    'ends_on' => $entry['ends_on'],
                    'variant_id' => $entry['variant_id'] ?? null,
                ];
            }
        }
        for ($i = 0; $i < $quantity; $i++) {
            $ranges[] = [
                'starts_on' => $newEntry['starts_on'],
                'ends_on' => $newEntry['ends_on'],
                'variant_id' => $newEntry['variant_id'] ?? null,
            ];
        }
        if (! app(RentalAvailability::class)->canFitRequested($product, $ranges)) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah papan tidak tersedia pada tanggal tersebut. Kurangi unit atau pilih tanggal lain.']);
        }
    }

    public function destroy(Request $request, string $item)
    {
        $cart = $request->session()->get('rental_cart', []);
        abort_unless(array_key_exists($item, $cart), 404);
        $target = $cart[$item];
        unset($cart[$item]);
        $request->session()->put('rental_cart', $cart);

        if ($request->wantsJson()) return $this->liveResponse($request, $target);

        return back()->with('success', 'Papan dihapus dari keranjang.');
    }

    /**
     * Apakah unit di keranjang masih bisa dipesan?
     *
     * Papan bervarian kehilangan satu warna tidak membuat baris lain ikut
     * "tidak tersedia"; yang diperiksa hanya warna yang benar-benar dipilih.
     */
    private function unitAvailable(RentalProduct $product, array $item): bool
    {
        if ($product->stock < 1) return false;

        if ($product->hasVariants()) {
            $variantId = (int) ($item['variant_id'] ?? 0);
            $variant = $variantId > 0 ? $product->findVariant($variantId) : null;

            return $variant !== null && $variant->stock > 0;
        }

        return true;
    }

    /**
     * Jawaban untuk pembaruan langsung (tanpa muat ulang halaman).
     *
     * Isinya sengaja kecil: jumlah unit per kelompok, subtotal kelompok yang
     * disentuh, dan total keranjang. Semua angka dihitung ulang dari sesi,
     * bukan dari angka yang dikirim peramban.
     */
    private function liveResponse(Request $request, array $target)
    {
        $cart = $request->session()->get('rental_cart', []);
        $products = RentalProduct::whereIn('id', array_column($cart, 'product_id'))->get(['id', 'base_price'])->keyBy('id');
        $groups = [];

        foreach ($cart as $entry) {
            $key = $this->groupKey($entry);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'count' => 0,
                    'subtotal' => 0,
                    'available' => isset($products[$entry['product_id']]),
                ];
            }
            $price = (int) ($products->get($entry['product_id'])?->base_price ?? 0);
            $groups[$key]['count']++;
            $groups[$key]['subtotal'] += $price;
        }

        $total = array_sum(array_map(fn (array $item) => (int) ($products->get($item['product_id'])?->base_price ?? 0), $cart));

        return response()->json([
            'ok' => true,
            'target' => $this->groupKey($target),
            'count' => count($cart),
            'total' => $total,
            'total_label' => 'Rp '.number_format($total, 0, ',', '.'),
            'groups' => array_values($groups),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }
}
