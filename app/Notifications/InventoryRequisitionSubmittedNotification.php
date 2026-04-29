<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\InventoryRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class InventoryRequisitionSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private InventoryRequisition $requisition) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $locationName = $this->requisition->requestingLocation()->value('name');
        $locationName = is_string($locationName) ? $locationName : 'Unknown location';

        return [
            'type' => 'inventory_requisition_submitted',
            'title' => 'Inventory requisition submitted',
            'message' => sprintf('A new requisition from "%s" is awaiting review.', $locationName),
            'action_url' => '/inventory-requisitions/'.$this->requisition->id,
            'resource_id' => $this->requisition->id,
            'resource_type' => 'inventory_requisition',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
