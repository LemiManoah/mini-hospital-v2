<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LabRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LabRequestCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private LabRequest $labRequest) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $itemCount = $this->labRequest->items()->count();

        return [
            'type' => 'lab_request_created',
            'title' => 'New lab request',
            'message' => sprintf('A lab request with %d test(s) is ready for processing.', $itemCount),
            'action_url' => '/laboratory/incoming-investigations',
            'resource_id' => $this->labRequest->id,
            'resource_type' => 'lab_request',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
