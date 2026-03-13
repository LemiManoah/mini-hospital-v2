<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PatientVisit;

final class AssessPatientVisitCompletion
{
    /**
     * @return array{
     *     can_complete: bool,
     *     has_pending_services: bool,
     *     pending_services_count: int,
     *     has_unpaid_balance: bool,
     *     unpaid_balance: float,
     *     blocking_reasons: array<int, string>,
     *     warning_messages: array<int, string>
     * }
     */
    public function handle(PatientVisit $visit): array
    {
        $pendingServicesCount = $this->pendingServicesCount($visit);
        $unpaidBalance = $this->unpaidBalance($visit);

        $blockingReasons = [];
        $warningMessages = [];

        if ($pendingServicesCount > 0) {
            $blockingReasons[] = sprintf(
                'This visit still has %d pending service%s.',
                $pendingServicesCount,
                $pendingServicesCount === 1 ? '' : 's',
            );
        }

        if ($unpaidBalance > 0) {
            $warningMessages[] = sprintf(
                'This patient still has an unpaid balance of %s for this visit.',
                number_format($unpaidBalance, 2),
            );
        }

        return [
            'can_complete' => $blockingReasons === [],
            'has_pending_services' => $pendingServicesCount > 0,
            'pending_services_count' => $pendingServicesCount,
            'has_unpaid_balance' => $unpaidBalance > 0,
            'unpaid_balance' => $unpaidBalance,
            'blocking_reasons' => $blockingReasons,
            'warning_messages' => $warningMessages,
        ];
    }

    private function pendingServicesCount(PatientVisit $visit): int
    {
        // Pending service tracking will plug into this method once visit service/order tables land.
        return 0;
    }

    private function unpaidBalance(PatientVisit $visit): float
    {
        // Visit-level charges and payments are not in the codebase yet, so default to no balance.
        return 0.0;
    }
}
