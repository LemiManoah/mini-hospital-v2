<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class PrescriptionCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private Prescription $prescription) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $itemCount = $this->prescription->items()->count();
        $visitId = $this->prescription->visit_id;

        return [
            'type' => 'prescription_created',
            'title' => 'New prescription ready',
            'message' => sprintf(
                'A prescription with %d item(s) is ready for dispensing.',
                $itemCount,
            ),
            'action_url' => $visitId ? '/visits/'.$visitId : null,
            'resource_id' => $this->prescription->id,
            'resource_type' => 'prescription',
            'occurred_at' => now()->toISOString(),
        ];
    }
}
