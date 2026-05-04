<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ImagingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ImagingRequestCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private ImagingRequest $imagingRequest) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $modality = $this->imagingRequest->modality->value;
        $bodyPart = $this->imagingRequest->body_part ?: 'requested area';

        return [
            'type' => 'imaging_request_created',
            'title' => 'New imaging request',
            'message' => sprintf('A %s request for %s is ready for processing.', $modality, $bodyPart),
            'action_url' => $this->imagingRequest->visit_id ? '/visits/'.$this->imagingRequest->visit_id : null,
            'resource_id' => $this->imagingRequest->id,
            'resource_type' => 'imaging_request',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
