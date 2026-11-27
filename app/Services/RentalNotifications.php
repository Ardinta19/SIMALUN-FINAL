<?php

namespace App\Services;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\User;
use App\Notifications\RentalUpdate;
use Illuminate\Support\Facades\Notification;

class RentalNotifications
{
    /**
     * Database notifications are written in the same transaction as the state change.
     * `$unit` opsional: bila notifikasi menyangkut satu papan, id unitnya ikut
     * disimpan supaya tautan notifikasi dapat membuka unit itu langsung, bukan
     * hanya halaman pesanan yang mungkin berisi beberapa papan.
     */
    public function customer(RentalOrder $order, string $title, string $message, ?RentalOrderUnit $unit = null): void
    {
        $customer = $order->customer;
        if ($customer && $customer->dapatMenerimaNotifikasi()) {
            $customer->notify(new RentalUpdate($order->id, $title, $message, $unit?->id));
        }
    }

    /** Notifikasi untuk pelanggan yang menyangkut satu unit tertentu. */
    public function customerForUnit(RentalOrderUnit $unit, string $title, string $message): void
    {
        $this->customer($unit->order, $title, $message, $unit);
    }

    /** @param RentalOrderUnit|null $unit lihat catatan pada `customer()`. */
    public function admins(RentalOrder $order, string $title, string $message, ?RentalOrderUnit $unit = null): void
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        Notification::send($admins, new RentalUpdate($order->id, $title, $message, $unit?->id));
    }

    /** Notifikasi admin yang menyangkut satu unit tertentu. */
    public function adminsForUnit(RentalOrderUnit $unit, string $title, string $message): void
    {
        $this->admins($unit->order, $title, $message, $unit);
    }
}
