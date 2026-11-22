<?php
namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\RentalDurationPackage;
use App\Models\RentalUniversity;
use App\Models\RentalPaymentSetting;
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
        $products = RentalProduct::with(['category', 'packages' => fn ($query) => $query->where('is_active', true)])->whereIn('id', array_column($cart, 'product_id'))->get()->keyBy('id');
        $universities = RentalUniversity::where('is_active', true)->orderBy('name')->get();
        return view('rental.checkout', compact('cart', 'products', 'universities'));
    }

    public function store(Request $request)
    {
        $cart = $request->session()->get('rental_cart', []);
        if (! $cart) throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
        $data = $request->validate([
            'units' => ['required', 'array'], 'terms' => ['accepted'],
            'units.*.package_id' => ['nullable', 'integer', 'min:1'],
            'units.*.starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'units.*.event_on' => ['required', 'date_format:Y-m-d'],
            'units.*.ends_on' => ['required', 'date_format:Y-m-d'],
            'units.*.recipient_name' => ['required', 'string', 'max:160'],
            'units.*.greeting' => ['required', 'string', 'max:1000'],
            'units.*.design_notes' => ['nullable', 'string', 'max:2000'],
            'units.*.delivery_university_id' => ['nullable', 'integer', 'min:1'],
            'units.*.return_university_id' => ['nullable', 'integer', 'min:1'],
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
                $package = null;
                if (! empty($unit['package_id'])) {
                    $package = RentalDurationPackage::where('rental_product_id', $product->id)->where('is_active', true)->lockForUpdate()->find($unit['package_id']);
                    if (! $package || (int) $package->days !== $days) throw ValidationException::withMessages(['units' => 'Paket tidak tersedia atau durasi sewa tidak sesuai. Periksa kembali paket dan tanggal.']);
                }
                $amount = $package ? (int) $package->price : $product->base_price * ($product->price_unit === 'day' ? $days : 1);
                if ($amount > 4294967295) throw ValidationException::withMessages(['units' => 'Jumlah biaya sewa melebihi batas sistem.']);
                $delivery = null; $collection = null;
                if ($unit['delivery_method'] === 'delivery' && ! empty($unit['delivery_university_id'])) {
                    $delivery = RentalUniversity::where('is_active', true)->lockForUpdate()->find($unit['delivery_university_id']);
                    if (! $delivery) throw ValidationException::withMessages(['units' => 'Universitas pengantaran tidak lagi tersedia.']);
                }
                if ($unit['return_method'] === 'collection' && ! empty($unit['return_university_id'])) {
                    $collection = RentalUniversity::where('is_active', true)->lockForUpdate()->find($unit['return_university_id']);
                    if (! $collection) throw ValidationException::withMessages(['units' => 'Universitas pengambilan kembali tidak lagi tersedia.']);
                }
                $allFixed = ($unit['delivery_method'] !== 'delivery' || $delivery) && ($unit['return_method'] !== 'collection' || $collection);
                $hasTransport = $unit['delivery_method'] === 'delivery' || $unit['return_method'] === 'collection';
                $transport = ['delivery_university' => $delivery?->name, 'delivery_university_fee' => $delivery?->delivery_fee, 'return_university' => $collection?->name, 'return_university_fee' => $collection?->collection_fee];
                if ($hasTransport && $allFixed) $transport += ['transport_quote' => (int) ($delivery?->delivery_fee ?? 0) + (int) ($collection?->collection_fee ?? 0), 'transport_quote_version' => 1, 'transport_quote_note' => 'Tarif universitas sesuai pilihan saat checkout.'];
                $order->units()->create(array_merge($unit, $transport, ['rental_product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->base_price, 'price_unit' => $product->price_unit, 'rental_amount' => $amount, 'rental_duration_package_id' => $package?->id, 'package_name' => $package?->name, 'package_days' => $package?->days]));
            }
            return $order;
        }, 3);
        $request->session()->forget('rental_cart');
        return redirect()->route('rental.orders.show', $order)->with('success', 'Pesanan dikirim dan menunggu konfirmasi admin. Stok belum dicadangkan.');
    }

    public function show(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $order->load('units.previews', 'units.payments.refunds', 'units.progressEvents');
        $paymentInstructions = RentalPaymentSetting::find(1)?->instructions;
        return view('rental.show', compact('order', 'paymentInstructions'));
    }

    public function index(Request $request)
    {
        $orders = RentalOrder::where('customer_id', $request->user()->id)->withCount('units')->latest()->paginate(20);
        return view('rental.index', compact('orders'));
    }

    public function adminIndex()
    {
        $orders = RentalOrder::with(['customer', 'units.previews', 'units.payments.refunds', 'units.progressEvents.recorder'])->latest()->paginate(20);
        return view('rental.admin', compact('orders'));
    }

    public function confirm(Request $request, RentalOrderUnit $unit)
    {
        DB::transaction(function () use ($unit, $request) {
            // Semua konfirmasi untuk produk yang sama diserialisasi pada baris produk.
            $product = RentalProduct::lockForUpdate()->findOrFail($unit->rental_product_id);
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'pending') throw ValidationException::withMessages(['unit' => 'Unit tidak lagi menunggu konfirmasi.']);
            if (! $unit->order->confirmation_deadline->isFuture()) throw ValidationException::withMessages(['unit' => 'Batas konfirmasi telah terlewati.']);
            $special = $unit->needsSpecialConfirmation();
            if ($special && ! $request->boolean('special_confirmation')) throw ValidationException::withMessages(['unit' => 'Jadwal dekat memerlukan persetujuan khusus admin.']);
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
            $unit->update(['status' => 'confirmed', 'confirmed_at' => now(), 'special_confirmation' => $special]);
        });
        return back()->with('success', 'Unit dikonfirmasi dan stok dicadangkan.');
    }

    public function quoteTransport(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:0', 'max:200000000'], 'note' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($unit, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if (! $unit->transportRequired() || $unit->status === 'cancelled' || $unit->transport_approved_at || $unit->payments()->exists()) {
                throw ValidationException::withMessages(['transport' => 'Ongkos tidak dapat diubah untuk unit ini.']);
            }
            $fixed = (int) ($unit->delivery_university_fee ?? 0) + (int) ($unit->return_university_fee ?? 0);
            $allFixed = ($unit->delivery_method !== 'delivery' || $unit->delivery_university_fee !== null) && ($unit->return_method !== 'collection' || $unit->return_university_fee !== null);
            if (($allFixed && (int) $data['amount'] !== $fixed) || (! $allFixed && (int) $data['amount'] < $fixed)) {
                throw ValidationException::withMessages(['transport' => 'Penawaran harus mencakup tarif tetap universitas sesuai pesanan.']);
            }
            $unit->update(['transport_quote' => $data['amount'], 'transport_quote_version' => $unit->transport_quote_version + 1, 'transport_quote_note' => $data['note'] ?? null]);
        });
        return back()->with('success', 'Penawaran ongkos dikirim kepada pelanggan.');
    }

    public function approveTransport(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($unit, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if (! $unit->transportRequired() || $unit->status === 'cancelled' || $unit->transport_approved_at || $unit->transport_quote === null || (int) $unit->transport_quote_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['transport' => 'Penawaran berubah atau tidak lagi tersedia. Periksa kembali.']);
            }
            $unit->update(['transport_approved_at' => now()]);
        });
        return back()->with('success', 'Ongkos disetujui.');
    }

    public function cancel(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'responsibility' => ['required', Rule::in(['seller', 'customer', 'other'])]]);
        DB::transaction(function () use ($unit, $data, $request) {
            RentalProduct::lockForUpdate()->findOrFail($unit->rental_product_id);
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status === 'cancelled') throw ValidationException::withMessages(['unit' => 'Unit sudah dibatalkan.']);
            $unit->update(['status' => 'cancelled', 'cancel_reason' => $data['reason'], 'cancel_responsibility' => $data['responsibility'], 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id]);
        });
        return back()->with('success', 'Unit dibatalkan dan reservasi dilepas. Tindak lanjuti pembayaran dan kewajiban pengembalian jika ada.');
    }
}
