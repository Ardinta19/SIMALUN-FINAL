<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RentalUpdate extends Notification
{
    /**
     * `$unitId` ikut dikirim supaya penerima bisa langsung dibuka ke unit yang
     * berubah. Sebelumnya hanya id pesanan yang tersimpan, sehingga satu
     * notifikasi tentang satu papan berakhir di daftar semua pesanan.
     */
    public function __construct(
        private int $orderId,
        private string $title,
        private string $message,
        private ?int $unitId = null,
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toArray(object $notifiable): array
    {
        $payload = ['rental_order_id' => $this->orderId, 'title' => $this->title, 'message' => $this->message];
        if ($this->unitId !== null) $payload['rental_order_unit_id'] = $this->unitId;

        return $payload;
    }
}
