<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RentalUpdate extends Notification
{
    public function __construct(private int $orderId, private string $title, private string $message) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        return ['rental_order_id' => $this->orderId, 'title' => $this->title, 'message' => $this->message];
    }
}
