<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\Voucher;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
use App\Models\RentalProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return match ($user->role) {
            'admin' => redirect()->route('dashboard.admin'),
            default => redirect()->route('customer.dashboard'),
        };
    }

    public function customer()
    {
        $user = Auth::user();
        $orders = RentalOrder::where('customer_id', $user->id)->withCount('units')->latest()->take(5)->get();
        $totalOrders = RentalOrder::where('customer_id', $user->id)->count();
        $activeUnits = RentalOrderUnit::whereHas('order', fn ($query) => $query->where('customer_id', $user->id))
            ->whereIn('status', ['pending', 'confirmed'])->where('progress', '!=', 'returned')->count();
        // Putaran 35: beranda pelanggan ikut menampilkan progres unit yang
        // sedang berjalan. Sebelumnya pelanggan harus membuka "Pesanan saya"
        // lalu satu per satu unit hanya untuk tahu papannya sudah dikerjakan
        // atau belum — persis pertanyaan yang biasanya dikirim ke WhatsApp.
        $trackedUnits = RentalOrderUnit::with(['order', 'previews', 'payments'])
            ->whereHas('order', fn ($query) => $query->where('customer_id', $user->id))
            ->where('status', 'confirmed')->where('progress', '!=', 'returned')
            ->latest('id')->take(4)->get();
        $unreadNotif = $user->unreadNotifications()->count();
        return view('roles.customer.dashboard', compact('user', 'orders', 'totalOrders', 'activeUnits', 'trackedUnits', 'unreadNotif'));
    }

    public function admin()
    {
        // Dasbor admin hanya menampilkan pekerjaan modul sewa. Angka laundry
        // (pesanan, kurir, layanan, rating) sudah dihapus bersama modulnya.
        $pemasukanHari = FinanceEntry::where('entry_type', 'income')
            ->where('entry_date', today()->toDateString())
            ->sum('amount');

        $pemasukanBulan = FinanceEntry::where('entry_type', 'income')
            ->where('period_key', now()->format('Y-m'))
            ->sum('amount');

        // Pengeluaran adalah pasangan yang selama ini tidak ada di dasbor:
        // admin bisa melihat uang masuk tanpa pernah tahu uang keluar.
        $pengeluaranHari = FinanceEntry::where('entry_type', 'expense')
            ->where('entry_date', today()->toDateString())
            ->sum('amount');

        $pengeluaranBulan = FinanceEntry::where('entry_type', 'expense')
            ->where('period_key', now()->format('Y-m'))
            ->sum('amount');

        /*
         * Arus kas tujuh hari untuk grafik batang.
         *
         * Satu kueri agregat, bukan tujuh kueri: `DB::table` dipakai (bukan
         * model) supaya kolom `entry_date` tidak di-cast menjadi objek tanggal
         * dan tetap bisa dijadikan kunci array apa adanya (`Y-m-d`).
         */
        $awalArus = today()->subDays(6)->toDateString();
        $petaArus = [];
        foreach (DB::table('finance_entries')
            ->whereBetween('entry_date', [$awalArus, today()->toDateString()])
            ->whereIn('entry_type', ['income', 'expense'])
            ->selectRaw('entry_date, entry_type, SUM(amount) as total')
            ->groupBy('entry_date', 'entry_type')
            ->get() as $baris) {
            $petaArus[substr((string) $baris->entry_date, 0, 10)][$baris->entry_type] = (int) $baris->total;
        }

        $arusHarian = [];
        for ($mundur = 6; $mundur >= 0; $mundur--) {
            $tanggal = today()->subDays($mundur);
            $kunci = $tanggal->toDateString();
            $arusHarian[] = [
                'tanggal' => $tanggal->format('d/m'),
                'label' => $tanggal->locale('id')->isoFormat('dd'),
                'masuk' => $petaArus[$kunci]['income'] ?? 0,
                'keluar' => $petaArus[$kunci]['expense'] ?? 0,
            ];
        }
        // Pembagi grafik: hari terbesar. Minimal 1 supaya tidak ada pembagian nol
        // ketika seminggu penuh belum ada catatan.
        $arusPuncak = max(1, max(array_map(fn (array $hari) => max($hari['masuk'], $hari['keluar']), $arusHarian)));
        $arusMasukTujuhHari = array_sum(array_column($arusHarian, 'masuk'));
        $arusKeluarTujuhHari = array_sum(array_column($arusHarian, 'keluar'));
        $arusKemarin = $arusHarian[5]['masuk'];

        /*
         * Jam pemakaian papan: berapa unit sedang di luar dibanding stok yang
         * benar-benar bisa disewakan. Stok dihitung per warna bila papan punya
         * warna aktif — sama seperti aturan ketersediaan di katalog.
         */
        $stokTotal = RentalProduct::with('variants')->where('is_active', true)->get(['id', 'stock'])
            ->sum(function (RentalProduct $produk) {
                $warnaAktif = $produk->variants->where('is_active', true);

                return $produk->variants->isNotEmpty() ? (int) $warnaAktif->sum('stock') : (int) $produk->stock;
            });

        $papanKeluar = RentalOrderUnit::where('status', 'confirmed')
            ->where('starts_on', '<=', today()->toDateString())
            ->where('ends_on', '>=', today()->toDateString())
            ->where('progress', '!=', 'returned')
            ->count();

        $pemakaianPersen = $stokTotal > 0 ? min(100, (int) round($papanKeluar / $stokTotal * 100)) : 0;

        $voucherAktif = Voucher::where('is_active', true)->count();

        // Kueri hitung, bukan memuat seluruh notifikasi ke memori.
        $adminUnread = Auth::user()->unreadNotifications()->count();

        $rentalPending = RentalOrderUnit::where('status', 'pending')->count();
        $rentalActive = RentalOrderUnit::where('status', 'confirmed')->where('progress', '!=', 'returned')->count();
        $rentalPaymentPending = RentalPayment::where('status', 'pending')->count();
        $rentalReview = RentalOrderUnit::needsPaymentReview()->count();
        $rentalQuoteNeeded = RentalOrderUnit::whereIn('status', ['pending', 'confirmed'])
            ->where(fn ($q) => $q->where('delivery_method', 'delivery')->orWhere('return_method', 'collection'))
            ->whereNull('transport_quote')->whereNull('transport_approved_at')->count();
        $rentalDesignNeeded = RentalOrderUnit::where('status', 'confirmed')->where('progress', 'waiting')->whereDoesntHave('previews')->count();
        $rentalSellerCancelled = RentalOrderUnit::where('status', 'cancelled')->where('cancel_responsibility', 'seller')->count();
        // Antrean dua arah: unit yang menunggu tindakan pelanggan, bukan admin.
        $rentalWaitingDesign = RentalOrderUnit::where('status', 'confirmed')->where('progress', 'waiting')
            ->whereHas('previews', fn ($query) => $query->where('status', 'pending'))->count();
        $rentalWaitingTransport = RentalOrderUnit::where('status', 'confirmed')->whereNotNull('transport_quote')->whereNull('transport_approved_at')->count();
        $rentalWaitingGrace = RentalOrderUnit::where('status', 'cancelled')->where('cancel_code', 'payment_timeout')->whereNull('deposit_forfeited_at')->count();
        $rentalReturnOverdue = RentalOrderUnit::where('status', 'confirmed')->where('progress', 'handed_over')
            ->where('ends_on', '<', now('Asia/Jakarta')->toDateString())->count();
        // Umur tunggu: agar admin tahu antrean mana yang paling lama menganggur.
        $rentalWaitingDesignSince = \App\Models\RentalDesignPreview::where('status', 'pending')
            ->whereHas('unit', fn ($query) => $query->where('status', 'confirmed')->where('progress', 'waiting'))
            ->min('created_at');
        $rentalWaitingTransportSince = \App\Models\RentalTransportQuoteEvent::where('action', 'quote')
            ->whereHas('unit', fn ($query) => $query->where('status', 'confirmed')->whereNotNull('transport_quote')->whereNull('transport_approved_at'))
            ->min('created_at');
        $graceUnitNearest = RentalOrderUnit::where('status', 'cancelled')->where('cancel_code', 'payment_timeout')
            ->whereNull('deposit_forfeited_at')->orderBy('starts_on')->first();
        $rentalWaitingGraceNearest = $graceUnitNearest ? app(\App\Services\RentalPaymentDeadline::class)->graceDeadline($graceUnitNearest) : null;
        // Tenggat terdekat membuat antrean "perlu tindakan" bisa diurutkan tanpa
        // membuka daftar: konfirmasi yang hampir habis didahulukan.
        // Tenggat konfirmasi menempel pada PESANAN, bukan unit. Dulu kueri ini
        // memanggil `RentalOrderUnit::min('confirmation_deadline')` — kolom yang
        // tidak ada di tabel unit. SQLite memperlakukan pengenal berkutip ganda
        // yang tidak dikenal sebagai STRING, sehingga nilai baliknya menjadi
        // teks "confirmation_deadline" dan `Carbon::parse()` meledak (dasbor 500
        // begitu ada unit berstatus menunggu). Kueri sekarang lewat relasi yang
        // benar.
        $rentalPendingNearest = RentalOrder::whereHas('units', fn ($query) => $query->where('status', 'pending'))
            ->min('confirmation_deadline');
        $paymentPendingOldest = RentalPayment::where('status', 'pending')->min('created_at');

        // Nilai agregat masih berupa teks dari basis data. Diubah ke waktu
        // Jakarta SEKALI di sini supaya view tidak memanggil `Carbon::parse()`
        // berulang kali — dulu satu nilai aneh saja (mis. nama kolom yang salah)
        // membuat seluruh dasbor gagal dirender.
        $jamJakarta = static fn ($nilai) => $nilai === null
            ? null
            : \Illuminate\Support\Carbon::parse($nilai)->timezone('Asia/Jakarta');
        $rentalPendingNearest = $jamJakarta($rentalPendingNearest);
        $paymentPendingOldest = $jamJakarta($paymentPendingOldest);
        $rentalWaitingDesignSince = $jamJakarta($rentalWaitingDesignSince);
        $rentalWaitingTransportSince = $jamJakarta($rentalWaitingTransportSince);
        $recentRentalOrders = RentalOrder::with('customer')->withCount('units')->latest()->take(5)->get();

        // "Hari ini" di dasbor: tiga angka yang paling dicari saat panel dibuka —
        // berapa papan harus diserahkan, dipakai untuk acara, dan kembali hari ini.
        // Hanya unit terkonfirmasi, sama dengan isi lembar serah terima.
        //
        // Dulu sel ketiga berisi "unit aktif", angka yang sama sudah muncul di
        // grid statistik tepat di bawahnya; sekarang isinya acara hari ini, satu-
        // satunya kegiatan hari ini yang belum terwakili.
        $hariIni = now('Asia/Jakarta');
        $hariIniTerima = RentalOrderUnit::where('status', 'confirmed')->where('starts_on', $hariIni->toDateString())->count();
        $hariIniKembali = RentalOrderUnit::where('status', 'confirmed')->where('ends_on', $hariIni->toDateString())->count();
        $hariIniAcara = RentalOrderUnit::where('status', 'confirmed')->where('event_on', $hariIni->toDateString())->count();
        $hariIniLabel = $hariIni->locale('id')->translatedFormat('l, d F Y');

        return view('roles.admin.dashboard', compact(
            'rentalPending', 'rentalActive', 'rentalPaymentPending', 'rentalReview', 'rentalQuoteNeeded', 'rentalDesignNeeded', 'rentalSellerCancelled',
            'rentalWaitingDesign', 'rentalWaitingTransport', 'rentalWaitingGrace', 'rentalReturnOverdue',
            'rentalWaitingDesignSince', 'rentalWaitingTransportSince', 'rentalWaitingGraceNearest', 'recentRentalOrders',
            'rentalPendingNearest', 'paymentPendingOldest',
            'hariIni', 'hariIniTerima', 'hariIniKembali', 'hariIniAcara', 'hariIniLabel',
            'pemasukanHari', 'pengeluaranHari', 'pemasukanBulan', 'pengeluaranBulan',
            'arusHarian', 'arusPuncak', 'arusMasukTujuhHari', 'arusKeluarTujuhHari', 'arusKemarin',
            'stokTotal', 'papanKeluar', 'pemakaianPersen',
            'voucherAktif',
            'adminUnread',
        ));
    }
}
