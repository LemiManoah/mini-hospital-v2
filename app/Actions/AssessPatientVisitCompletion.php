<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FacilityServiceOrderStatus;
use App\Enums\ImagingRequestStatus;
use App\Enums\LabRequestStatus;
use App\Enums\PrescriptionStatus;
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
        $unpaidBalance = $this->unpaidBalance();
        $consultationBlockingReason = $this->consultationBlockingReason($visit);

        $blockingReasons = [];
        $warningMessages = [];

        if ($consultationBlockingReason !== null) {
            $blockingReasons[] = $consultationBlockingReason;
        }

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
        $pendingLabRequests = $visit->labRequests()
            ->whereNotIn('status', [
                LabRequestStatus::COMPLETED->value,
                LabRequestStatus::CANCELLED->value,
                LabRequestStatus::REJECTED->value,
            ])
            ->count();

        $pendingImagingRequests = $visit->imagingRequests()
            ->whereNotIn('status', [
                ImagingRequestStatus::COMPLETED->value,
                ImagingRequestStatus::CANCELLED->value,
            ])
            ->count();

        $pendingPrescriptions = $visit->prescriptions()
            ->whereNotIn('status', [
                PrescriptionStatus::FULLY_DISPENSED->value,
                PrescriptionStatus::CANCELLED->value,
            ])
            ->count();

        $pendingFacilityServiceOrders = $visit->facilityServiceOrders()
            ->whereNotIn('status', [
                FacilityServiceOrderStatus::COMPLETED->value,
                FacilityServiceOrderStatus::CANCELLED->value,
            ])
            ->count();

        return $pendingLabRequests + $pendingImagingRequests + $pendingPrescriptions + $pendingFacilityServiceOrders;
    }

    private function unpaidBalance(): float
    {
        // Visit-level charges and payments are not in the codebase yet, so default to no balance.
        return 0.0;
    }

    private function consultationBlockingReason(PatientVisit $visit): ?string
    {
        if (! $visit->relationLoaded('triage')) {
            $visit->loadMissing(['triage:id,visit_id', 'consultation:id,visit_id,completed_at']);
        } else {
            $visit->loadMissing('consultation:id,visit_id,completed_at');
        }

        if ($visit->triage === null) {
            return null;
        }

        if ($visit->consultation === null) {
            return 'This visit cannot be completed until the consultation has been started.';
        }

        if ($visit->consultation->completed_at === null) {
            return 'This visit cannot be completed until the consultation has been finalized.';
        }

        return null;
    }
}
