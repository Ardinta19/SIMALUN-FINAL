<?php

namespace App\Support;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\User;

/**
 * Tautan WhatsApp siap kirim untuk alur sewa.
 *
 * Semua titik tunggu di alur sewa bergantung pada orang membuka aplikasi.
 * Tautan ini tidak mengirim pesan otomatis (tidak butuh kredensial gateway),
 * hanya menyiapkan chat dengan penerima yang benar dan pesan yang sudah mengisi
 * konteks unit, sehingga admin/pelanggan tinggal menekan kirim.
 */
class RentalWhatsApp
{
    /** Tautan pelanggan → admin toko (nomor dari konfigurasi laundry). */
    public static function adminLink(RentalOrder $order, RentalOrderUnit $unit): ?string
    {
        $brand = Laundry::name();
        $message = 'Halo admin '.$brand.', saya pelanggan pesanan '.$order->reference
            .' (unit '.$unit->product_name.'). Status unit: '.self::state($unit).'. Mohon dibantu. Terima kasih.';

        return WhatsApp::link(Laundry::whatsapp(), $message);
    }

    /** Tautan admin → pelanggan (nomor HP pelanggan). */
    public static function customerLink(RentalOrder $order, RentalOrderUnit $unit): ?string
    {
        $customer = $order->customer;
        if (! $customer instanceof User) {
            return null;
        }

        $message = 'Halo '.$customer->name.', ini admin '.Laundry::name().' terkait pesanan '.$order->reference
            .' (unit '.$unit->product_name.'). Status unit: '.self::state($unit).'. Mohon dibalas ya. Terima kasih.';

        return WhatsApp::link($customer->phone, $message);
    }

    /**
     * Ringkasan keadaan unit dalam bahasa sehari-hari, dipakai kedua arah chat.
     * Sengaja tidak memuat data sensitif (nominal pembayaran, alamat lengkap).
     */
    public static function state(RentalOrderUnit $unit): string
    {
        if ($unit->status === 'cancelled') {
            return 'dibatalkan';
        }

        $preview = $unit->relationLoaded('previews') ? $unit->previews->first() : $unit->previews()->first();

        if ($unit->transportRequired() && $unit->transport_quote === null) {
            return 'menunggu penawaran ongkos dari admin';
        }
        if ($unit->transportRequired() && ! $unit->transport_approved_at) {
            return 'menunggu persetujuan ongkos versi '.$unit->transport_quote_version;
        }
        if ($preview && $preview->status === 'pending') {
            return 'menunggu persetujuan desain versi '.$preview->version;
        }
        if ($unit->payments->contains(fn ($payment) => $payment->status === 'pending')) {
            return 'menunggu verifikasi bukti pembayaran';
        }

        return match ($unit->progress) {
            'waiting' => 'menunggu pengerjaan',
            'production' => 'sedang dikerjakan',
            'ready' => 'siap diserahkan',
            'handed_over' => 'sudah diserahkan, menunggu pengembalian',
            'returned' => 'sudah selesai',
            default => 'dalam proses',
        };
    }
}
