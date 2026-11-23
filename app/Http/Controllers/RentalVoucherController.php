<?php

namespace App\Http\Controllers;

use App\Models\RentalProduct;
use App\Models\Voucher;
use App\Services\RentalVouchers;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Voucher alur sewa: admin membuat dan mengaktifkan kode, pelanggan memakainya
 * di halaman checkout.
 */
class RentalVoucherController extends Controller
{
    public function index()
    {
        return view('rental.vouchers', [
            'vouchers' => Voucher::withCount('rentalUsages')
                ->orderByDesc('is_active')
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $voucher = Voucher::create($data);
            Audit::log('rental.voucher.create', $voucher, after: $this->snapshot($voucher->refresh()));
        });

        return back()->with('success', 'Voucher '.$data['code'].' dibuat dan siap dipakai pelanggan.');
    }

    public function toggle(Voucher $voucher)
    {
        DB::transaction(function () use ($voucher) {
            $voucher = Voucher::lockForUpdate()->findOrFail($voucher->id);
            $before = $this->snapshot($voucher);
            $voucher->update(['is_active' => ! $voucher->is_active]);
            Audit::log('rental.voucher.toggle', $voucher, before: $before, after: $this->snapshot($voucher));
        });

        return back()->with('success', 'Status voucher diperbarui. Pesanan yang sudah memakai voucher tidak berubah.');
    }

    /**
     * Pemeriksaan kode di halaman checkout (dipanggil JavaScript).
     * Hanya menghitung perkiraan; keputusan final tetap dihitung saat pesanan
     * dikirim, memakai harga dan paket yang benar-benar dipilih.
     */
    public function check(Request $request, RentalVouchers $vouchers)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            // Perkiraan harga sewa dari tanggal dan paket yang sedang diisi di
            // formulir checkout. Hanya dipakai untuk menampilkan perkiraan;
            // angka final selalu dihitung server saat pesanan dikirim.
            'base' => ['nullable', 'integer', 'min:1', 'max:400000000'],
        ]);

        $cart = $request->session()->get('rental_cart', []);
        if (! $cart) {
            return response()->json(['ok' => false, 'message' => 'Keranjang masih kosong. Tambahkan papan terlebih dahulu.'], 422);
        }

        $products = RentalProduct::whereIn('id', array_column($cart, 'product_id'))->get()->keyBy('id');
        $fromForm = ! empty($data['base']);
        $base = $fromForm ? (int) $data['base'] : $vouchers->estimateRentalBase($cart, $products);
        $result = $vouchers->resolve($data['code'], $request->user(), $base);

        if ($result['error'] !== null || $result['voucher'] === null) {
            return response()->json(['ok' => false, 'message' => $result['error'] ?? 'Voucher tidak dapat dipakai.'], 422);
        }

        $voucher = $result['voucher'];

        return response()->json([
            'ok' => true,
            'code' => $voucher->code,
            'discount' => $result['discount'],
            'discount_text' => 'Rp '.number_format($result['discount'], 0, ',', '.'),
            'message' => 'Voucher '.$voucher->code.' berlaku: potongan '.$voucher->display_value.' ('.$voucher->scopeLabel().').',
            'note' => $fromForm
                ? 'Perkiraan dari tanggal dan paket yang sedang diisi. Potongan final dihitung ulang saat pesanan dikirim.'
                : 'Perkiraan dari harga dasar keranjang. Potongan final dihitung dari tanggal dan paket yang dipilih saat pesanan dikirim.',
        ]);
    }

    private function validated(Request $request): array
    {
        // Kode disimpan UPPERCASE, jadi pemeriksaan unik memakai bentuk yang
        // sama dengan yang akan ditulis ke basis data.
        $request->merge(['code' => mb_strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('vouchers', 'code')],
            'description' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'scope' => ['required', Rule::in([Voucher::SCOPE_RENTAL, Voucher::SCOPE_RENTAL_TRANSPORT])],
            'value' => ['required', 'integer', 'min:1', 'max:100000000'],
            'min_order' => ['nullable', 'integer', 'min:0', 'max:400000000'],
            'max_discount' => ['nullable', 'integer', 'min:1', 'max:400000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'valid_from' => ['nullable', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, garis bawah, atau tanda hubung tanpa spasi.',
            'code.unique' => 'Kode ini sudah dipakai voucher lain.',
            'valid_until.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        if ($data['type'] === 'percent' && (int) $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Potongan persen maksimal 100%.']);
        }

        $data['min_order'] = (int) ($data['min_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function snapshot(Voucher $voucher): array
    {
        return $voucher->only(['code', 'type', 'scope', 'value', 'min_order', 'max_discount', 'usage_limit', 'per_customer_limit', 'valid_from', 'valid_until', 'is_active']);
    }
}
