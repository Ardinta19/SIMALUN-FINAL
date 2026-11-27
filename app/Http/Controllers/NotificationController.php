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
     *
     * Bila notifikasi membawa id unit, tujuannya unit itu langsung — satu
     * pesanan bisa berisi beberapa papan dan pelanggan/admin tetap harus
     * menggulir untuk menemukan papan yang berubah.
     */
    public function markAndRedirect(string $id)
    {
        $notif = Auth::user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        $rentalOrderId = $notif->data['rental_order_id'] ?? null;
        $unitId = $notif->data['rental_order_unit_id'] ?? null;

        if ($rentalOrderId && Auth::user()->role === 'customer') {
            $rentalOrder = RentalOrder::where('customer_id', Auth::id())->findOrFail($rentalOrderId);
            $unit = $unitId ? $rentalOrder->units()->find($unitId) : null;
            if ($unit) return redirect()->route('rental.orders.unit', [$rentalOrder, $unit]);

            return redirect()->route('rental.orders.show', $rentalOrder);
        }
        if ($rentalOrderId && Auth::user()->role === 'admin') {
            $rentalOrder = RentalOrder::find($rentalOrderId);
            // Halaman detail admin memakai jangkar `#unit-<id>` per papan.
            if ($rentalOrder && $unitId && $rentalOrder->units()->whereKey($unitId)->exists()) {
                return redirect()->to(route('admin.rental.orders.show', $rentalOrder).'#unit-'.$unitId);
            }

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
