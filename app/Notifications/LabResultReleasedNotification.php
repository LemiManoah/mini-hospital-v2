<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LabResultReleasedNotification extends Notification
{
    use Queueable;

    public function __construct(private LabOrderItem $labOrderItem) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        /** @var LabOrder|null $labOrder */
        $labOrder = $this->labOrderItem->order()->first();

        $testName = $this->labOrderItem->test()->value('test_name');
        $testName = is_string($testName) ? $testName : 'Lab test';

        $visitId = $labOrder?->visit_id;

        return [
            'type' => 'lab_result_released',
            'title' => 'Lab result released',
            'message' => sprintf('"%s" result is approved and ready for review.', $testName),
            'action_url' => $visitId ? '/visits/'.$visitId : null,
            'resource_id' => $this->labOrderItem->id,
            'resource_type' => 'lab_order_item',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
