<?php

namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function customerIndex()
    {
        $notifications = Auth::user()
            ->notifications()
            ->paginate(20);

        return view('roles.customer.notifications', compact('notifications'));
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }

    /**
     * Buka satu notifikasi: tandai dibaca lalu arahkan ke pesanan terkait.
     * Ini satu-satunya jalur "buka satu notifikasi"; tidak ada PATCH
     * terpisah karena tanda dibaca selalu menyertai tindakan membuka.
     */
    public function markAndRedirect(string $id)
    {
        $notif = Auth::user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        $rentalOrderId = $notif->data['rental_order_id'] ?? null;
        if ($rentalOrderId && Auth::user()->role === 'customer') {
            $rentalOrder = RentalOrder::where('customer_id', Auth::id())->findOrFail($rentalOrderId);
            return redirect()->route('rental.orders.show', $rentalOrder);
        }
        if ($rentalOrderId && Auth::user()->role === 'admin') {
            $rentalOrder = RentalOrder::find($rentalOrderId);
            return redirect()->route('admin.rental.orders', $rentalOrder ? ['q' => $rentalOrder->reference] : []);
        }

        // Notifikasi lama (modul laundry) hanya membawa order_id; arahkan ke
        // halaman notifikasi peran masing-masing tanpa menebak detail.
        return redirect()->route(Auth::user()->role === 'admin' ? 'admin.notifications' : 'customer.notifications');
    }

    public function adminIndex()
    {
        $notifications = Auth::user()
            ->notifications()
            ->paginate(20);

        $notifikasiOperasional = RentalOrderUnit::with('order.customer')
            ->where('status', 'pending')->latest()->take(10)->get();

        return view('roles.admin.notifications', compact('notifications', 'notifikasiOperasional'));
    }
}
