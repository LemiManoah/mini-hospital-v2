<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LabOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LabOrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private LabOrder $labOrder) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $itemCount = $this->labOrder->items()->count();

        return [
            'type' => 'lab_order_created',
            'title' => 'New lab order',
            'message' => sprintf('A lab order with %d test(s) is ready for processing.', $itemCount),
            'action_url' => '/laboratory/incoming-investigations',
            'resource_id' => $this->labOrder->id,
            'resource_type' => 'lab_order',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
