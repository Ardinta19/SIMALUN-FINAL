<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\Voucher;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
use Illuminate\Support\Facades\Auth;

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
        $unreadNotif = $user->unreadNotifications()->count();
        return view('roles.customer.dashboard', compact('user', 'orders', 'totalOrders', 'activeUnits', 'unreadNotif'));
    }

    public function admin()
    {
        // Dasbor admin hanya menampilkan pekerjaan modul sewa. Angka laundry
        // (pesanan, kurir, layanan, rating) sudah dihapus bersama modulnya.
        $pemasukanHari = FinanceEntry::where('entry_type', 'income')
            ->whereDate('entry_date', today())
            ->sum('amount');

        $pemasukanBulan = FinanceEntry::where('entry_type', 'income')
            ->where('period_key', now()->format('Y-m'))
            ->sum('amount');

        $voucherAktif = Voucher::where('is_active', true)->count();

        $adminUnread = Auth::user()->unreadNotifications->count();

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
            ->whereDate('ends_on', '<', now('Asia/Jakarta')->toDateString())->count();
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
        $recentRentalOrders = RentalOrder::with('customer')->withCount('units')->latest()->take(5)->get();

        return view('roles.admin.dashboard', compact(
            'rentalPending', 'rentalActive', 'rentalPaymentPending', 'rentalReview', 'rentalQuoteNeeded', 'rentalDesignNeeded', 'rentalSellerCancelled',
            'rentalWaitingDesign', 'rentalWaitingTransport', 'rentalWaitingGrace', 'rentalReturnOverdue',
            'rentalWaitingDesignSince', 'rentalWaitingTransportSince', 'rentalWaitingGraceNearest', 'recentRentalOrders',
            'pemasukanHari',
            'pemasukanBulan',
            'voucherAktif',
            'adminUnread',
        ));
    }
}
