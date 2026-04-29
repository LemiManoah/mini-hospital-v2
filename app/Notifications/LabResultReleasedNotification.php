<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LabRequest;
use App\Models\LabRequestItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LabResultReleasedNotification extends Notification
{
    use Queueable;

    public function __construct(private LabRequestItem $labRequestItem) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        /** @var LabRequest|null $labRequest */
        $labRequest = $this->labRequestItem->request()->first();

        $testName = $this->labRequestItem->test()->value('test_name');
        $testName = is_string($testName) ? $testName : 'Lab test';

        $visitId = $labRequest?->visit_id;

        return [
            'type' => 'lab_result_released',
            'title' => 'Lab result released',
            'message' => sprintf('"%s" result is approved and ready for review.', $testName),
            'action_url' => $visitId ? '/visits/'.$visitId : null,
            'resource_id' => $this->labRequestItem->id,
            'resource_type' => 'lab_request_item',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
