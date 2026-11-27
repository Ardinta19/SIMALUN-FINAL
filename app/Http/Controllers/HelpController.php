<?php

namespace App\Http\Controllers;

use App\Models\RentalPaymentSetting;

/**
 * Halaman bantuan per peran.
 *
 * Sebelumnya dua halaman ini ditulis sebagai closure di `routes/web.php`.
 * Rute ber-closure membuat `php artisan route:cache` gagal seluruhnya
 * ("Unable to prepare route ... for serialization. Uses Closure."), sehingga
 * di server produksi setiap permintaan tetap harus memuat dan mencocokkan
 * seluruh daftar rute. Dengan dipindah ke controller, penembolokan rute bisa
 * dipakai.
 */
class HelpController extends Controller
{
    public function customer()
    {
        // Halaman bantuan pelanggan menampilkan nomor rekening/instruksi
        // pembayaran yang sedang aktif.
        return view('roles.customer.help', [
            'settings' => RentalPaymentSetting::find(1),
        ]);
    }

    public function admin()
    {
        return view('roles.admin.help');
    }
}
