<?php

namespace App\Notifications;

use App\Models\TableReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WaiterAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly TableReservation $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'waiter_assigned',
            'booking_id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'table_number' => $this->booking->table?->table_number,
            'area_name' => $this->booking->table?->area?->name,
            'customer_name' => $this->booking->customer?->profile?->name
                ?? $this->booking->customer?->customerUser?->name
                ?? $this->booking->customer?->name
                ?? '-',
            'reservation_date' => $this->booking->reservation_date,
            'reservation_time' => $this->booking->reservation_time,
        ];
    }
}
