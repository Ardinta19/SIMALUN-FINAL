<?php
namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\RentalDurationPackage;
use App\Models\RentalUniversity;
use App\Models\RentalPaymentSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\RentalAvailability;
use App\Services\RentalVouchers;
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
    public function create(Request $request, RentalAvailability $availability)
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
        $savedAddresses = $request->user()->customerAddresses()->orderByDesc('is_primary')->orderBy('label')->get()
            ->filter(fn ($address) => mb_strlen($address->full_address) <= 1000);
        // Tanggal yang sudah penuh per produk, agar kalender unit menolaknya.
        $bookedDates = $products->mapWithKeys(fn ($product) => [$product->id => $availability->fullyBookedDates($product)])->all();
        // Isian terakhir dipulihkan bila pelanggan sempat kembali ke keranjang,
        // selama keranjangnya masih sama dan tidak ada isian yang baru gagal.
        $draft = $request->session()->get('rental_checkout_draft');
        if ($draft && ($draft['cart_key'] ?? null) === $this->checkoutCartKey($cart) && ! $request->session()->hasOldInput()) {
            $request->session()->flashInput($draft['input']);
        }
        return view('rental.checkout', compact('cart', 'products', 'universities', 'savedAddresses', 'bookedDates'));
    }

    public function store(Request $request)
    {
        $cart = $request->session()->get('rental_cart', []);
        if (! $cart) throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
        // Draf bertahan sampai pesanan berhasil atau keranjang berubah.
        $request->session()->put('rental_checkout_draft', ['cart_key' => $this->checkoutCartKey($cart), 'input' => $request->except(['_token'])]);
        $data = $request->validate([
            'units' => ['required', 'array'], 'terms' => ['accepted'],
            'units.*.package_id' => ['nullable', 'integer', 'min:1'],
            'units.*.starts_on' => ['nullable', 'date_format:Y-m-d'],
            'units.*.event_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->toDateString()],
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
            'voucher_code' => ['nullable', 'string', 'max:40'],
        ]);
        if (count($cart) !== count($data['units']) || array_diff(array_keys($cart), array_keys($data['units']))) {
            throw ValidationException::withMessages(['cart' => 'Keranjang berubah. Periksa kembali sebelum mengirim.']);
        }
        foreach (array_keys($cart) as $index => $key) {
            $unit = $data['units'][$key];
            $label = 'Unit '.($index + 1).': ';
            if (! empty($unit['starts_on']) && $unit['starts_on'] !== $unit['event_on']) throw ValidationException::withMessages(['units' => $label.'tanggal acara dan penerimaan harus sama. Muat ulang halaman checkout.']);
            if ($unit['ends_on'] < $unit['event_on']) throw ValidationException::withMessages(['units' => $label.'tanggal pengembalian tidak boleh sebelum tanggal acara dan penerimaan.']);
            // Jendela pemesanan dan durasi memakai aturan yang sama dengan
            // katalog dan keranjang, supaya tanggal di luar kalender tidak
            // bisa lolos lewat permintaan yang disusun manual.
            RentalAvailability::assertBookablePeriod($unit['event_on'], $unit['ends_on'], 'units', $label);
            $data['units'][$key]['starts_on'] = $unit['event_on'];
            if ($unit['delivery_method'] !== 'delivery') $data['units'][$key]['delivery_address'] = null;
            if ($unit['return_method'] !== 'collection') $data['units'][$key]['return_address'] = null;
            if ($unit['delivery_method'] === 'delivery' && empty($unit['delivery_address'])) throw ValidationException::withMessages(['units' => $label.'alamat pengantaran wajib diisi.']);
            if ($unit['return_method'] === 'collection' && empty($unit['return_address'])) throw ValidationException::withMessages(['units' => $label.'alamat pengambilan kembali wajib diisi.']);
        }
        // Identitas unit keranjang tidak berubah saat permintaan diulang.
        // Data unit juga disimpan sebagai hash agar pengulangan yang berbeda
        // tidak diam-diam dianggap sebagai perubahan pesanan yang telah dibuat.
        $cartKeys = array_keys($cart);
        sort($cartKeys, SORT_STRING);
        $checkoutKey = hash('sha256', json_encode([$request->user()->id, $cartKeys], JSON_THROW_ON_ERROR));
        $units = $data['units'];
        ksort($units);
        foreach ($units as &$values) ksort($values);
        unset($values);
        // Kode voucher ikut menjadi bagian isi pesanan: pengiriman ulang dengan
        // kode berbeda harus dianggap sebagai pesanan berbeda, bukan duplikat.
        $voucherCode = mb_strtoupper(trim((string) ($data['voucher_code'] ?? '')));
        $payloadHash = hash('sha256', json_encode([$units, $voucherCode], JSON_THROW_ON_ERROR));
        [$order, $created] = DB::transaction(function () use ($request, $cart, $data, $checkoutKey, $payloadHash, $voucherCode) {
            // Serialize checkout pelanggan yang sama, termasuk jika dua request
            // membaca sesi sebelum request pertama mengosongkan keranjang.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing = RentalOrder::where('checkout_key', $checkoutKey)->first();
            if ($existing) {
                if (! hash_equals($existing->checkout_payload_hash ?? '', $payloadHash)) {
                    throw ValidationException::withMessages(['cart' => 'Keranjang ini sudah dikirim dengan detail berbeda. Periksa Pesanan saya sebelum membuat pesanan baru.']);
                }
                return [$existing, false];
            }
            // Mengunci produk dalam urutan konsisten dengan konfirmasi stok.
            // Unit pending ikut dihitung karena sejak pesanan dikirim tanggalnya
            // sudah ditahan sampai admin mengonfirmasi atau tenggatnya lewat.
            $products = RentalProduct::with('category')->whereIn('id', array_column($cart, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $rangesByProduct = [];
            foreach ($cart as $key => $entry) {
                $rangesByProduct[$entry['product_id']][] = ['starts_on' => $data['units'][$key]['starts_on'], 'ends_on' => $data['units'][$key]['ends_on']];
            }
            foreach ($rangesByProduct as $productId => $ranges) {
                $product = $products->get($productId);
                if (! $product || ! $product->is_active || ! $product->category?->is_active || ! app(RentalAvailability::class)->canFitRequested($product, $ranges, true)) {
                    throw ValidationException::withMessages(['cart' => 'Jumlah papan '.($product?->name ?? 'yang dipilih').' tidak tersedia untuk tanggal tersebut. Ubah tanggal atau kurangi unit, lalu coba lagi.']);
                }
            }
            $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $request->user()->id, 'confirmation_deadline' => now()->addHours(24), 'checkout_key' => $checkoutKey, 'checkout_payload_hash' => $payloadHash]);
            $plans = [];
            foreach ($cart as $key => $entry) {
                $product = $products->get($entry['product_id']);
                if (! $product || ! $product->is_active || ! $product->category->is_active || $product->stock < 1) throw ValidationException::withMessages(['cart' => 'Ada papan yang tidak tersedia. Perbarui keranjang.']);
                $unit = $data['units'][$key];
                // Durasi sudah divalidasi assertBookablePeriod() sebelum transaksi.
                $days = (int) Carbon::parse($unit['starts_on'])->diffInDays(Carbon::parse($unit['ends_on'])) + 1;
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
                // Unit dibuat setelah potongan voucher diketahui agar setiap
                // baris unit langsung menyimpan bagian potongannya.
                $plans[$key] = array_merge($unit, $transport, ['rental_product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->base_price, 'price_unit' => $product->price_unit, 'rental_amount' => $amount, 'rental_duration_package_id' => $package?->id, 'package_name' => $package?->name, 'package_days' => $package?->days]);
            }

            // Voucher: diperiksa dan dikunci di dalam transaksi supaya kuota
            // tidak bisa terpakai dua kali oleh pengiriman bersamaan.
            $vouchers = app(RentalVouchers::class);
            $rentalBase = array_sum(array_map(fn (array $plan) => (int) $plan['rental_amount'], $plans));
            $voucher = null;
            $discount = 0;
            if ($voucherCode !== '') {
                $voucher = Voucher::where('code', $voucherCode)->lockForUpdate()->first();
                if (! $voucher) throw ValidationException::withMessages(['voucher_code' => 'Kode voucher tidak dikenal.']);
                $problem = $vouchers->problem($voucher, $request->user(), $rentalBase);
                if ($problem !== null) throw ValidationException::withMessages(['voucher_code' => $problem]);
                $discount = $vouchers->discountFor($voucher, $rentalBase);
            }
            $weights = array_map(fn (array $plan) => (int) $plan['rental_amount'], $plans);
            $allocation = $vouchers->allocate($discount, $weights);
            $order->forceFill([
                'voucher_id' => $voucher?->id,
                'voucher_code' => $voucher?->code,
                'discount_amount' => $discount,
            ])->save();
            foreach ($plans as $planKey => $plan) {
                $order->units()->create($plan + ['discount_amount' => (int) ($allocation['rental'][$planKey] ?? 0)]);
            }
            if ($voucher && $discount > 0) {
                $vouchers->record($voucher, $order, $discount, $request->user()->id);
            }
            app(RentalNotifications::class)->admins($order, 'Pesanan sewa baru', 'Pesanan baru menunggu konfirmasi admin.');
            return [$order, true];
        }, 3);
        $request->session()->forget(['rental_cart', 'rental_checkout_draft']);
        return redirect()->route('rental.orders.show', $order)->with('success', $created ? 'Pesanan dikirim dan menunggu konfirmasi admin. Tanggal sudah ditahan sampai tenggat konfirmasi.' : 'Pesanan ini sudah dikirim sebelumnya. Periksa rinciannya di bawah.');
    }

    /** Kunci keranjang: draf hanya dipulihkan untuk isi keranjang yang sama. */
    private function checkoutCartKey(array $cart): string
    {
        $keys = array_keys($cart);
        sort($keys, SORT_STRING);
        return hash('sha256', json_encode($keys, JSON_THROW_ON_ERROR));
    }

    public function show(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $order->load(['units' => fn ($query) => $query->orderBy('id'), 'units.previews', 'units.payments']);
        $rating = DB::table('rental_order_reviews')->where('rental_order_id', $order->id)->value('rating');
        $likeShare = DB::table('rental_order_like_shares')->where('rental_order_id', $order->id)->first();
        $likeCount = DB::table('rental_order_likes')->where('rental_order_id', $order->id)->count();
        return view('rental.show', compact('order', 'rating', 'likeShare', 'likeCount'));
    }

    public function customerUnit(Request $request, RentalOrder $order, RentalOrderUnit $unit)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        abort_unless($unit->rental_order_id === $order->id, 404);
        $unit->load('previews', 'payments.refunds', 'payments.corrections', 'progressEvents', 'cancellationEvents', 'transportQuoteEvents.recorder');
        $paymentSettings = RentalPaymentSetting::find(1);
        $unitNumber = $order->units()->where('id', '<=', $unit->id)->count();
        $productRating = DB::table('rental_product_reviews')->where('rental_order_unit_id', $unit->id)->value('rating');
        return view('rental.unit', compact('order', 'unit', 'paymentSettings', 'unitNumber', 'productRating'));
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
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'cancelled', 'payment_pending', 'design_pending', 'review', 'transport_quote', 'transport_approval', 'design_initial', 'design_revision', 'seller_cancelled', 'grace', 'return_overdue'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (! empty($filters['from']) && ! empty($filters['to']) && $filters['to'] < $filters['from']) throw ValidationException::withMessages(['to' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.']);
        $orders = RentalOrder::with(['customer', 'units'])
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
                    if ($status === 'seller_cancelled') $unit->where('status', 'cancelled')->where('cancel_responsibility', 'seller');
                    if ($status === 'grace') $unit->where('status', 'cancelled')->where('cancel_code', 'payment_timeout')->whereNull('deposit_forfeited_at');
                    if ($status === 'return_overdue') $unit->where('status', 'confirmed')->where('progress', 'handed_over')->whereDate('ends_on', '<', now('Asia/Jakarta')->toDateString());
                    if ($status === 'review') $unit->needsPaymentReview();
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
            })->when(($filters['status'] ?? null) === 'pending', fn ($query) => $query->orderBy('confirmation_deadline')->orderBy('id'), fn ($query) => $query->latest()->orderByDesc('id'))->paginate(20)->withQueryString();
        return view('rental.admin', compact('orders', 'filters'));
    }

    public function adminShow(RentalOrder $order)
    {
        $order->load(['customer', 'units.previews', 'units.payments.refunds', 'units.payments.corrections', 'units.progressEvents.recorder', 'units.cancellationEvents', 'units.transportQuoteEvents.recorder']);
        return view('rental.admin-detail', ['order' => $order, 'filters' => []]);
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
            app(RentalNotifications::class)->customer($unit->order, 'Unit dikonfirmasi', 'Unit '.$unit->product_name.' telah dikonfirmasi; tanggalnya tetap ditahan.');
        });
        return back()->with('success', 'Unit dikonfirmasi dan tanggal tetap ditahan.');
    }

    public function quoteTransport(Request $request, RentalOrderUnit $unit)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:0', 'max:200000000'], 'note' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($unit, $data, $request) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            // Revisi boleh selama belum ada pembayaran sama sekali; persetujuan
            // lama otomatis gugur sehingga pelanggan wajib menyetujui versi baru.
            if (! $unit->transportRequired() || $unit->status === 'cancelled' || $unit->payments()->exists()) {
                throw ValidationException::withMessages(['transport' => 'Ongkos tidak dapat diubah setelah ada pembayaran. Periksa pembayaran atau batalkan unit bila perlu.']);
            }
            $fixed = (int) ($unit->delivery_university_fee ?? 0) + (int) ($unit->return_university_fee ?? 0);
            $allFixed = ($unit->delivery_method !== 'delivery' || $unit->delivery_university_fee !== null) && ($unit->return_method !== 'collection' || $unit->return_university_fee !== null);
            if (($allFixed && (int) $data['amount'] !== $fixed) || (! $allFixed && (int) $data['amount'] < $fixed)) {
                throw ValidationException::withMessages(['transport' => 'Penawaran harus mencakup tarif tetap universitas sesuai pesanan.']);
            }
            $unit->update(['transport_quote' => $data['amount'], 'transport_quote_version' => $unit->transport_quote_version + 1, 'transport_quote_note' => $data['note'] ?? null, 'transport_approved_at' => null]);
            $unit->transportQuoteEvents()->create(['action' => 'quote', 'version' => $unit->transport_quote_version, 'amount' => (int) $data['amount'], 'note' => $data['note'] ?? null, 'recorded_by' => $request->user()->id]);
            app(RentalNotifications::class)->customer($unit->order, 'Penawaran ongkos', 'Ongkos untuk unit '.$unit->product_name.' siap diperiksa. Setujui atau ajukan keberatan dengan alasan.');
        });
        return back()->with('success', 'Penawaran ongkos dikirim kepada pelanggan.');
    }

    public function approveTransport(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        // $request wajib di-use: pencatat riwayat memakai $request->user()->id.
        DB::transaction(function () use ($unit, $data, $request) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if (! $unit->transportRequired() || $unit->status === 'cancelled' || $unit->transport_approved_at || $unit->transport_quote === null || (int) $unit->transport_quote_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['transport' => 'Penawaran berubah atau tidak lagi tersedia. Periksa kembali.']);
            }
            $unit->update(['transport_approved_at' => now()]);
            $unit->transportQuoteEvents()->create(['action' => 'approve', 'version' => $unit->transport_quote_version, 'amount' => (int) $unit->transport_quote, 'note' => null, 'recorded_by' => $request->user()->id]);
            // Voucher yang mencakup ongkos dihitung ulang setelah ongkos
            // disetujui, supaya potongan mengikuti tagihan yang sebenarnya.
            app(RentalVouchers::class)->redistribute($unit->order()->first());
            app(RentalNotifications::class)->admins($unit->order, 'Ongkos disetujui', 'Pelanggan menyetujui ongkos unit '.$unit->product_name.'.');
        });
        return back()->with('success', 'Ongkos disetujui.');
    }

    /** Pelanggan menolak penawaran ongkos dan meminta versi baru. */
    public function rejectTransport(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'note' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $unit, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if (! $unit->transportRequired() || $unit->status === 'cancelled' || $unit->transport_approved_at || $unit->transport_quote === null || (int) $unit->transport_quote_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['transport' => 'Penawaran berubah atau tidak lagi tersedia. Muat ulang halaman.']);
            }
            $unit->transportQuoteEvents()->create(['action' => 'reject', 'version' => $unit->transport_quote_version, 'amount' => (int) $unit->transport_quote, 'note' => $data['note'], 'recorded_by' => $request->user()->id]);
            $unit->update(['transport_quote' => null, 'transport_quote_note' => null, 'transport_approved_at' => null]);
            app(RentalNotifications::class)->admins($unit->order, 'Ongkos ditolak pelanggan', 'Pelanggan menolak ongkos unit '.$unit->product_name.'. Alasan: '.$data['note']);
        }, 3);
        return back()->with('success', 'Penawaran ongkos ditolak. Admin akan mengirim penawaran baru.');
    }

    /** Pelanggan membatalkan unitnya sendiri sebelum pengerjaan dimulai. */
    public function cancelByCustomer(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $unit, $data) {
            RentalProduct::lockForUpdate()->findOrFail($unit->rental_product_id);
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if (! in_array($unit->status, ['pending', 'confirmed'], true) || $unit->progress !== 'waiting') {
                throw ValidationException::withMessages(['cancel' => 'Unit ini tidak dapat dibatalkan sendiri. Hubungi admin.']);
            }
            $unit->cancellationEvents()->create(['action' => 'cancel', 'previous_status' => $unit->status, 'new_status' => 'cancelled', 'note' => $data['reason'], 'responsibility' => 'customer', 'recorded_by' => $request->user()->id]);
            $unit->update(['status_before_cancel' => $unit->status, 'status' => 'cancelled', 'cancel_code' => 'customer_request', 'cancel_reason' => $data['reason'], 'cancel_responsibility' => 'customer', 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id]);
            app(RentalNotifications::class)->customer($unit->order, 'Unit dibatalkan', 'Unit '.$unit->product_name.' dibatalkan atas permintaan Anda. Admin akan menindaklanjuti pembayaran yang sudah masuk.');
            app(RentalNotifications::class)->admins($unit->order, 'Pembatalan oleh pelanggan', 'Pelanggan membatalkan unit '.$unit->product_name.'. Alasan: '.$data['reason'].'. Periksa pembayaran dan pengembalian dana.');
        }, 3);
        return back()->with('success', 'Unit dibatalkan. DP mengikuti ketentuan; hubungi admin untuk pengembalian dana bila sudah membayar.');
    }

    /** Pelanggan memperbaiki data unit sebelum pengerjaan dan sebelum ada pembayaran terverifikasi. */
    public function updateUnitDetails(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:160'],
            'greeting' => ['required', 'string', 'max:1000'],
            'design_notes' => ['nullable', 'string', 'max:2000'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'return_address' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $unit, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'confirmed' || $unit->progress !== 'waiting') throw ValidationException::withMessages(['unit' => 'Data unit hanya dapat diubah sebelum pengerjaan dimulai.']);
            if ($unit->verifiedNet() > 0) throw ValidationException::withMessages(['unit' => 'Data unit tidak dapat diubah setelah ada pembayaran terverifikasi. Hubungi admin.']);
            if ($unit->delivery_method === 'delivery' && empty($data['delivery_address'])) throw ValidationException::withMessages(['delivery_address' => 'Alamat pengantaran wajib diisi.']);
            if ($unit->return_method === 'collection' && empty($data['return_address'])) throw ValidationException::withMessages(['return_address' => 'Alamat pengambilan kembali wajib diisi.']);
            $changes = [];
            foreach (['recipient_name', 'greeting', 'design_notes', 'delivery_address', 'return_address'] as $field) {
                $new = $data[$field] ?? null;
                $new = $new === '' ? null : $new;
                if ($new !== $unit->{$field}) $changes[$field] = $new;
            }
            if (! $changes) return;
            $labels = ['recipient_name' => 'nama penerima', 'greeting' => 'ucapan', 'design_notes' => 'permintaan desain', 'delivery_address' => 'alamat pengantaran', 'return_address' => 'alamat pengambilan'];
            $designFields = array_intersect(array_keys($changes), ['recipient_name', 'greeting', 'design_notes']);
            if ($designFields && $unit->designApprovedForProduction()) {
                throw ValidationException::withMessages(['unit' => 'Desain sudah disetujui atau dikerjakan; perubahan nama, ucapan, atau permintaan desain harus melalui admin.']);
            }
            $unit->update($changes);
            $changed = implode(', ', array_map(fn ($field) => $labels[$field], array_keys($changes)));
            $unit->progressEvents()->create(['progress' => $unit->progress, 'recorded_by' => $request->user()->id, 'note' => 'Pelanggan memperbarui '.$changed.'.']);
            app(RentalNotifications::class)->admins($unit->order, 'Data unit diperbarui pelanggan', 'Pelanggan memperbarui '.$changed.' pada unit '.$unit->product_name.'.');
        }, 3);
        return back()->with('success', 'Data unit diperbarui dan dicatat di riwayat.');
    }

    /** Isi ulang keranjang dari unit yang dibatalkan, tanpa tanggal. */
    public function reorder(Request $request, RentalOrderUnit $unit)
    {
        abort_unless($unit->order->customer_id === $request->user()->id, 403);
        if ($unit->status !== 'cancelled') throw ValidationException::withMessages(['reorder' => 'Pesan ulang hanya tersedia untuk unit yang dibatalkan.']);
        $product = RentalProduct::with('category')->find($unit->rental_product_id);
        if (! $product || ! $product->is_active || ! $product->category?->is_active || $product->stock < 1) {
            throw ValidationException::withMessages(['reorder' => 'Papan ini tidak lagi tersedia di katalog.']);
        }
        $cart = $request->session()->get('rental_cart', []);
        if (count($cart) >= 50) throw ValidationException::withMessages(['reorder' => 'Keranjang maksimal 50 unit.']);
        $cart[(string) Str::uuid()] = [
            'product_id' => $product->id,
            'recipient_name' => $unit->recipient_name,
            'greeting' => $unit->greeting,
            'design_notes' => $unit->design_notes,
            'delivery_method' => $unit->delivery_method,
            'delivery_address' => $unit->delivery_address,
            'return_method' => $unit->return_method,
            'return_address' => $unit->return_address,
        ];
        $request->session()->put('rental_cart', $cart);
        return redirect()->route('rental.checkout')->with('success', 'Unit dimasukkan lagi ke keranjang. Pilih tanggal baru sebelum mengirim.');
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
            $policy = app(RentalPaymentDeadline::class);
            // Pembatalan karena tenggat pelunasan memakai toleransi H+1 pukul 12.00 WIB;
            // pembatalan lain hanya dapat dilanjutkan sampai hari penerimaan berakhir.
            $limit = $unit->cancel_code === 'payment_timeout'
                ? $policy->graceDeadline($unit)
                : \Carbon\CarbonImmutable::parse($unit->starts_on->toDateString(), 'Asia/Jakarta')->addDay()->subSecond();
            if ($unit->deposit_forfeited_at || now('Asia/Jakarta')->greaterThan($limit)) {
                throw ValidationException::withMessages(['unit' => $unit->cancel_code === 'payment_timeout'
                    ? 'Batas melanjutkan unit telah terlewati (toleransi sampai H+1 pukul 12.00 WIB).'
                    : 'Batas melanjutkan unit telah terlewati.']);
            }
            if ($unit->status_before_cancel === 'pending') {
                if (! $unit->order->confirmation_deadline->isFuture()) throw ValidationException::withMessages(['unit' => 'Tenggat konfirmasi telah terlewati.']);
                $next = 'pending';
            } else {
                if ($policy->isDue($unit) && ($unit->payableAmount() === null || $unit->verifiedNet() < $unit->payableAmount())) {
                    throw ValidationException::withMessages(['unit' => 'Pembayaran harus lunas dan terverifikasi sebelum unit dilanjutkan setelah tenggat pelunasan.']);
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
