<?php
namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalOrderController extends Controller
{
    public function create(Request $request)
    {
        $cart = $request->session()->get('rental_cart', []);
        if (! $cart) return redirect()->route('cart.index')->withErrors(['cart' => 'Keranjang masih kosong.']);
        $products = RentalProduct::with('category')->whereIn('id', array_column($cart, 'product_id'))->get()->keyBy('id');
        return view('rental.checkout', compact('cart', 'products'));
    }

    public function store(Request $request)
    {
        $cart = $request->session()->get('rental_cart', []);
        if (! $cart) throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
        $data = $request->validate([
            'units' => ['required', 'array'], 'terms' => ['accepted'],
            'units.*.starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'units.*.event_on' => ['required', 'date_format:Y-m-d'],
            'units.*.ends_on' => ['required', 'date_format:Y-m-d'],
            'units.*.recipient_name' => ['required', 'string', 'max:160'],
            'units.*.greeting' => ['required', 'string', 'max:1000'],
            'units.*.design_notes' => ['nullable', 'string', 'max:2000'],
            'units.*.delivery_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'units.*.delivery_address' => ['nullable', 'string', 'max:1000'],
            'units.*.return_method' => ['required', Rule::in(['collection', 'dropoff'])],
            'units.*.return_address' => ['nullable', 'string', 'max:1000'],
        ]);
        if (count($cart) !== count($data['units']) || array_diff(array_keys($cart), array_keys($data['units']))) {
            throw ValidationException::withMessages(['cart' => 'Keranjang berubah. Periksa kembali sebelum mengirim.']);
        }
        foreach ($data['units'] as $unit) {
            if ($unit['event_on'] < $unit['starts_on'] || $unit['ends_on'] < $unit['event_on']) throw ValidationException::withMessages(['units' => 'Tanggal acara harus berada dalam periode sewa.']);
            if ($unit['delivery_method'] === 'delivery' && empty($unit['delivery_address'])) throw ValidationException::withMessages(['units' => 'Alamat pengantaran wajib diisi.']);
            if ($unit['return_method'] === 'collection' && empty($unit['return_address'])) throw ValidationException::withMessages(['units' => 'Alamat pengambilan kembali wajib diisi.']);
        }
        $order = DB::transaction(function () use ($request, $cart, $data) {
            $products = RentalProduct::with('category')->whereIn('id', array_column($cart, 'product_id'))->get()->keyBy('id');
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $request->user()->id, 'confirmation_deadline' => now()->addHours(24)]);
            foreach ($cart as $key => $entry) {
                $product = $products->get($entry['product_id']);
                if (! $product || ! $product->is_active || ! $product->category->is_active || $product->stock < 1) throw ValidationException::withMessages(['cart' => 'Ada papan yang tidak tersedia. Perbarui keranjang.']);
                $unit = $data['units'][$key];
                $days = (int) Carbon::parse($unit['starts_on'])->diffInDays(Carbon::parse($unit['ends_on'])) + 1;
                if ($days > 366) throw ValidationException::withMessages(['units' => 'Periode sewa maksimal 366 hari.']);
                $order->units()->create(array_merge($unit, ['rental_product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->base_price, 'price_unit' => $product->price_unit, 'rental_amount' => $product->base_price * ($product->price_unit === 'day' ? $days : 1)]));
            }
            return $order;
        });
        $request->session()->forget('rental_cart');
        return redirect()->route('rental.orders.show', $order)->with('success', 'Pesanan dikirim dan menunggu konfirmasi admin. Stok belum dicadangkan.');
    }

    public function show(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $order->load('units');
        return view('rental.show', compact('order'));
    }

    public function index(Request $request)
    {
        $orders = RentalOrder::where('customer_id', $request->user()->id)->withCount('units')->latest()->paginate(20);
        return view('rental.index', compact('orders'));
    }

    public function adminIndex()
    {
        $orders = RentalOrder::with(['customer', 'units'])->latest()->paginate(20);
        return view('rental.admin', compact('orders'));
    }

    public function confirm(RentalOrderUnit $unit)
    {
        DB::transaction(function () use ($unit) {
            // Semua konfirmasi untuk produk yang sama diserialisasi pada baris produk.
            $product = RentalProduct::lockForUpdate()->findOrFail($unit->rental_product_id);
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'pending') throw ValidationException::withMessages(['unit' => 'Unit tidak lagi menunggu konfirmasi.']);
            if ($unit->order->confirmation_deadline->isPast()) throw ValidationException::withMessages(['unit' => 'Batas konfirmasi telah terlewati.']);
            if (! $product->is_active || ! $product->category->is_active) throw ValidationException::withMessages(['unit' => 'Papan tidak aktif.']);
            $overlaps = RentalOrderUnit::where('rental_product_id', $product->id)->where('status', 'confirmed')
                ->whereDate('starts_on', '<=', $unit->ends_on->toDateString())->whereDate('ends_on', '>=', $unit->starts_on->toDateString())->get(['starts_on', 'ends_on']);
            $events = [];
            foreach ($overlaps as $existing) {
                $start = max($existing->starts_on->toDateString(), $unit->starts_on->toDateString());
                $end = min($existing->ends_on->toDateString(), $unit->ends_on->toDateString());
                $events[$start] = ($events[$start] ?? 0) + 1;
                $after = Carbon::parse($end)->addDay()->toDateString();
                $events[$after] = ($events[$after] ?? 0) - 1;
            }
            ksort($events);
            $reserved = 0;
            foreach ($events as $change) {
                $reserved += $change;
                if ($reserved >= $product->stock) throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
            }
            if ($product->stock < 1) throw ValidationException::withMessages(['unit' => 'Stok tidak cukup pada periode tersebut.']);
            $unit->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        });
        return back()->with('success', 'Unit dikonfirmasi dan stok dicadangkan.');
    }

    public function cancel(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($unit, $data) {
            RentalProduct::lockForUpdate()->findOrFail($unit->rental_product_id);
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status === 'cancelled') throw ValidationException::withMessages(['unit' => 'Unit sudah dibatalkan.']);
            $unit->update(['status' => 'cancelled', 'cancel_reason' => $data['reason']]);
        });
        return back()->with('success', 'Unit dibatalkan dan reservasi dilepas.');
    }
}
