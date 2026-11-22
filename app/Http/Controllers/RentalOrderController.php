<?php
namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\RentalDurationPackage;
use App\Models\RentalUniversity;
use App\Models\RentalPaymentSetting;
use App\Services\RentalAvailability;
use App\Services\RentalPaymentDeadline;
use App\Services\RentalNotifications;
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
        foreach ($cart as $entry) {
            $product = $products->get($entry['product_id']);
            if (! $product || ! $product->is_active || ! $product->category?->is_active || $product->stock < 1) {
                return redirect()->route('cart.index')->withErrors(['cart' => 'Ada papan yang tidak lagi tersedia. Hapus unit tersebut dari keranjang sebelum checkout.']);
            }
        }
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
        foreach (array_keys($cart) as $index => $key) {
            $unit = $data['units'][$key];
            $label = 'Unit '.($index + 1).': ';
            if ($unit['event_on'] < $unit['starts_on'] || $unit['ends_on'] < $unit['event_on']) throw ValidationException::withMessages(['units' => $label.'tanggal acara harus berada dalam periode sewa.']);
            if ($unit['delivery_method'] === 'delivery' && empty($unit['delivery_address'])) throw ValidationException::withMessages(['units' => $label.'alamat pengantaran wajib diisi.']);
            if ($unit['return_method'] === 'collection' && empty($unit['return_address'])) throw ValidationException::withMessages(['units' => $label.'alamat pengambilan kembali wajib diisi.']);
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
            app(RentalNotifications::class)->admins($order, 'Pesanan sewa baru', 'Pesanan baru menunggu konfirmasi admin.');
            return $order;
        }, 3);
        $request->session()->forget('rental_cart');
        return redirect()->route('rental.orders.show', $order)->with('success', 'Pesanan dikirim dan menunggu konfirmasi admin. Stok belum dicadangkan.');
    }

    public function show(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $order->load('units.previews', 'units.payments.refunds', 'units.payments.corrections', 'units.progressEvents', 'units.cancellationEvents');
        $paymentSettings = RentalPaymentSetting::find(1);
        return view('rental.show', compact('order', 'paymentSettings'));
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'cancelled'])],
        ]);
        $orders = RentalOrder::where('customer_id', $request->user()->id)
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim($filters['q']);
                $query->where(function ($query) use ($term) {
                    $query->where('reference', 'like', '%'.$term.'%')
                        ->orWhereHas('units', fn ($units) => $units->where('product_name', 'like', '%'.$term.'%')->orWhere('recipient_name', 'like', '%'.$term.'%'));
                });
            })
            ->when(! empty($filters['status']), fn ($query) => $query->whereHas('units', fn ($units) => $units->where('status', $filters['status'])))
            ->withCount(['units', 'units as pending_units_count' => fn ($q) => $q->where('status', 'pending'), 'units as confirmed_units_count' => fn ($q) => $q->where('status', 'confirmed'), 'units as cancelled_units_count' => fn ($q) => $q->where('status', 'cancelled')])
            ->latest()->orderByDesc('id')->paginate(20)->withQueryString();
        return view('rental.index', compact('orders', 'filters'));
    }

    public function adminIndex(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'cancelled', 'payment_pending', 'design_pending', 'review', 'transport_quote', 'transport_approval', 'design_initial', 'design_revision'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (! empty($filters['from']) && ! empty($filters['to']) && $filters['to'] < $filters['from']) throw ValidationException::withMessages(['to' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.']);
        $orders = RentalOrder::with(['customer', 'units.previews', 'units.payments.refunds', 'units.payments.corrections', 'units.progressEvents.recorder', 'units.cancellationEvents'])
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $query->where(fn ($q) => $q->where('reference', 'like', $term)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                    ->orWhereHas('units', fn ($unit) => $unit->where('product_name', 'like', $term)->orWhere('recipient_name', 'like', $term)));
            })
            ->when(! empty($filters['status']) || ! empty($filters['from']) || ! empty($filters['to']), function ($query) use ($filters) {
                $query->whereHas('units', function ($unit) use ($filters) {
                    $status = $filters['status'] ?? null;
                    if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) $unit->where('status', $status);
                    if ($status === 'review') $unit->where('status', 'confirmed')->where('payment_review_hold', true);
                    if ($status === 'payment_pending') $unit->whereHas('payments', fn ($payment) => $payment->where('status', 'pending'));
                    if ($status === 'design_pending') $unit->where('status', 'confirmed')->whereHas('previews', fn ($preview) => $preview->where('status', 'pending'));
                    if (in_array($status, ['transport_quote', 'transport_approval'], true)) {
                        $unit->whereIn('status', ['pending', 'confirmed'])
                            ->where(fn ($q) => $q->where('delivery_method', 'delivery')->orWhere('return_method', 'collection'))
                            ->whereNull('transport_approved_at');
                        if ($status === 'transport_quote') $unit->whereNull('transport_quote');
                        else $unit->whereNotNull('transport_quote');
                    }
                    if ($status === 'design_initial') $unit->where('status', 'confirmed')->where('progress', 'waiting')->whereDoesntHave('previews');
                    if ($status === 'design_revision') $unit->where('status', 'confirmed')->where('progress', 'waiting')
                        ->whereHas('previews', fn ($preview) => $preview->where('status', 'revision')->whereRaw('rental_design_previews.version = (select max(latest_preview.version) from rental_design_previews as latest_preview where latest_preview.rental_order_unit_id = rental_order_units.id)'));
                    if (! empty($filters['from'])) $unit->whereDate('event_on', '>=', $filters['from']);
                    if (! empty($filters['to'])) $unit->whereDate('event_on', '<=', $filters['to']);
                });
            })->latest()->paginate(20)->withQueryString();
        return view('rental.admin', compact('orders', 'filters'));
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
            app(RentalAvailability::class)->assertAvailable($product, $unit);
            $needsReview = $special && app(RentalPaymentDeadline::class)->isDue($unit);
            $unit->update(['status' => 'confirmed', 'confirmed_at' => now(), 'special_confirmation' => $special, 'payment_review_hold' => $needsReview]);
            app(RentalNotifications::class)->customer($unit->order, 'Unit dikonfirmasi', 'Unit '.$unit->product_name.' telah dikonfirmasi dan stok dicadangkan.');
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
            app(RentalNotifications::class)->customer($unit->order, 'Penawaran ongkos', 'Ongkos untuk unit '.$unit->product_name.' siap diperiksa.');
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
            app(RentalNotifications::class)->admins($unit->order, 'Ongkos disetujui', 'Pelanggan menyetujui ongkos unit '.$unit->product_name.'.');
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
            $unit->cancellationEvents()->create(['action' => 'cancel', 'previous_status' => $unit->status, 'new_status' => 'cancelled', 'note' => $data['reason'], 'responsibility' => $data['responsibility'], 'recorded_by' => $request->user()->id]);
            $unit->update(['status_before_cancel' => $unit->status, 'cancel_code' => 'manual', 'status' => 'cancelled', 'cancel_reason' => $data['reason'], 'cancel_responsibility' => $data['responsibility'], 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id]);
            app(RentalNotifications::class)->customer($unit->order, 'Unit dibatalkan', 'Unit '.$unit->product_name.' dibatalkan. Lihat detail pesanan.');
        });
        return back()->with('success', 'Unit dibatalkan dan reservasi dilepas. Tindak lanjuti pembayaran dan kewajiban pengembalian jika ada.');
    }
    public function resume(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($unit, $request, $data) {
            $product = RentalProduct::lockForUpdate()->findOrFail($unit->rental_product_id);
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'cancelled' || ! in_array($unit->status_before_cancel, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['unit' => 'Unit ini tidak dapat dilanjutkan otomatis.']);
            }
            $today = now('Asia/Jakarta')->toDateString();
            $starts = $unit->starts_on->toDateString();
            if ($unit->deposit_forfeited_at || $today > $starts) throw ValidationException::withMessages(['unit' => 'Batas melanjutkan unit telah terlewati.']);
            if ($unit->status_before_cancel === 'pending') {
                if (! $unit->order->confirmation_deadline->isFuture()) throw ValidationException::withMessages(['unit' => 'Tenggat konfirmasi telah terlewati.']);
                $next = 'pending';
            } else {
                if ($today === $starts && ($unit->payableAmount() === null || $unit->verifiedNet() < $unit->payableAmount())) {
                    throw ValidationException::withMessages(['unit' => 'Pada hari penerimaan pembayaran harus lunas terverifikasi untuk melanjutkan.']);
                }
                app(RentalAvailability::class)->assertAvailable($product, $unit);
                $next = 'confirmed';
            }
            $lateResumedAt = $unit->cancel_code === 'payment_timeout' ? now() : $unit->payment_late_resumed_at;
            $unit->cancellationEvents()->create(['action' => 'resume', 'previous_status' => 'cancelled', 'new_status' => $next, 'note' => $data['reason'], 'recorded_by' => $request->user()->id]);
            $unit->update(['status' => $next, 'payment_late_resumed_at' => $lateResumedAt, 'status_before_cancel' => null, 'cancel_code' => null, 'cancel_reason' => null, 'cancel_responsibility' => null, 'cancelled_at' => null, 'cancelled_by' => null]);
            app(RentalNotifications::class)->customer($unit->order, 'Unit dilanjutkan', 'Unit '.$unit->product_name.' telah dilanjutkan. Lihat detail pesanan.');
        }, 3);
        return back()->with('success', 'Unit dilanjutkan. Untuk unit terkonfirmasi, stok telah diperiksa kembali.');
    }
}
