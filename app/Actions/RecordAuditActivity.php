<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class RecordAuditActivity
{
    public function __construct(
        private Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function handle(
        string $logName,
        string $event,
        Model $subject,
        string $description,
        ?Model $actor = null,
        ?string $tenantId = null,
        ?string $branchId = null,
        ?string $staffId = null,
        ?string $reason = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): void {
        $logger = activity($logName)->performedOn($subject)->event($event);
        $resolvedActor = $actor ?? Auth::user();

        if ($resolvedActor instanceof Model) {
            $logger->causedBy($resolvedActor);
        }

        $logger->withProperties([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'staff_id' => $staffId,
            'reason' => $reason,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);

        $logger->tap(function (Model $activity) use ($tenantId, $branchId, $staffId): void {
            $activity->forceFill([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'staff_id' => $staffId,
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ]);
        });

        $logger->log($description);
    }
}
