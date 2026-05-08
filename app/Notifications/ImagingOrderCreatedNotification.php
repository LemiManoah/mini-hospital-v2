<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ImagingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ImagingOrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private ImagingOrder $imagingOrder) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $modality = $this->imagingOrder->modality->value;
        $bodyPart = $this->imagingOrder->body_part ?: 'requested area';

        return [
            'type' => 'imaging_order_created',
            'title' => 'New imaging order',
            'message' => sprintf('A %s request for %s is ready for processing.', $modality, $bodyPart),
            'action_url' => $this->imagingOrder->visit_id ? '/visits/'.$this->imagingOrder->visit_id : null,
            'resource_id' => $this->imagingOrder->id,
            'resource_type' => 'imaging_order',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
