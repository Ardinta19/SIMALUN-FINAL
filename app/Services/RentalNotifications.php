<?php

namespace App\Services;

use App\Models\RentalOrder;
use App\Models\User;
use App\Notifications\RentalUpdate;
use Illuminate\Support\Facades\Notification;

class RentalNotifications
{
    // Database notifications are written in the same transaction as the state change.
    public function customer(RentalOrder $order, string $title, string $message): void
    {
        $customer = $order->customer;
        if ($customer && $customer->is_active) $customer->notify(new RentalUpdate($order->id, $title, $message));
    }

    public function admins(RentalOrder $order, string $title, string $message): void
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        Notification::send($admins, new RentalUpdate($order->id, $title, $message));
    }
}
