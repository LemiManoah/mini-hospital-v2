<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FacilityServiceOrderStatus;
use App\Enums\ImagingRequestStatus;
use App\Enums\LabRequestStatus;
use App\Enums\PrescriptionStatus;
use App\Models\PatientVisit;

final readonly class AssessPatientVisitCompletion
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
    ) {}

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
        $consultationBlockingReason = $this->consultationBlockingReason($visit);

        return $this->buildResult($pendingServicesCount, $unpaidBalance, $consultationBlockingReason);
    }

    /**
     * Evaluate completion from already-hydrated data without touching the database.
     *
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
    public function handleLoaded(PatientVisit $visit): array
    {
        $pendingServicesCount = $this->pendingServicesCountFromLoaded($visit);
        $unpaidBalance = $this->unpaidBalanceFromLoaded($visit);
        $consultationBlockingReason = $this->consultationBlockingReasonFromLoaded($visit);

        return $this->buildResult($pendingServicesCount, $unpaidBalance, $consultationBlockingReason);
    }

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
    private function buildResult(int $pendingServicesCount, float $unpaidBalance, ?string $consultationBlockingReason): array
    {
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

    private function pendingServicesCountFromLoaded(PatientVisit $visit): int
    {
        return (int) ($visit->pending_lab_requests_count ?? 0)
            + (int) ($visit->pending_imaging_requests_count ?? 0)
            + (int) ($visit->pending_prescriptions_count ?? 0)
            + (int) ($visit->pending_facility_service_orders_count ?? 0);
    }

    private function unpaidBalanceFromLoaded(PatientVisit $visit): float
    {
        if (! $visit->relationLoaded('billing') || $visit->billing === null) {
            return 0.0;
        }

        return (float) ($visit->billing->balance_amount ?? 0);
    }

    private function consultationBlockingReasonFromLoaded(PatientVisit $visit): ?string
    {
        if (! $visit->relationLoaded('triage')) {
            return null;
        }

        if ($visit->triage === null) {
            return null;
        }

        if (! $visit->relationLoaded('consultation') || $visit->consultation === null) {
            return 'This visit cannot be completed until the consultation has been started.';
        }

        if ($visit->consultation->completed_at === null) {
            return 'This visit cannot be completed until the consultation has been finalized.';
        }

        return null;
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

    private function unpaidBalance(PatientVisit $visit): float
    {
        $visit->loadMissing('billing');

        if ($visit->billing === null) {
            return 0.0;
        }

        return (float) $this->recalculateVisitBilling
            ->handle($visit->billing)
            ->balance_amount;
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
