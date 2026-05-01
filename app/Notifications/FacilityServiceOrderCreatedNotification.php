<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FacilityServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class FacilityServiceOrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private FacilityServiceOrder $facilityServiceOrder) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $serviceName = $this->facilityServiceOrder->service()->value('name');
        $serviceName = is_string($serviceName) ? $serviceName : 'Facility service';

        return [
            'type' => 'facility_service_order_created',
            'title' => 'New facility service order',
            'message' => sprintf('"%s" has been ordered and is ready for processing.', $serviceName),
            'action_url' => $this->facilityServiceOrder->visit_id ? '/visits/'.$this->facilityServiceOrder->visit_id : null,
            'resource_id' => $this->facilityServiceOrder->id,
            'resource_type' => 'facility_service_order',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
