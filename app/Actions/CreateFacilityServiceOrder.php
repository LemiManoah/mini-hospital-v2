<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FacilityServiceOrderStatus;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\FacilityServiceOrder;
use App\Models\PatientVisit;
use Illuminate\Validation\ValidationException;

final readonly class CreateFacilityServiceOrder
{
    public function __construct(
        private SyncFacilityServiceOrderCharge $syncFacilityServiceOrderCharge,
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    public function handle(Consultation|PatientVisit $context, array $data, string $staffId): FacilityServiceOrder
    {
        [$visit, $consultation] = $this->resolveContext($context);

        $hasPendingDuplicate = FacilityServiceOrder::query()
            ->where('visit_id', $visit->id)
            ->where('facility_service_id', $data['facility_service_id'])
            ->where('status', FacilityServiceOrderStatus::PENDING->value)
            ->exists();

        if ($hasPendingDuplicate) {
            throw ValidationException::withMessages([
                'facility_service_id' => 'This facility service already has a pending order for this visit. Remove or complete the existing order first.',
            ]);
        }

        $order = FacilityServiceOrder::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'consultation_id' => $consultation?->id,
            'facility_service_id' => $data['facility_service_id'],
            'ordered_by' => $staffId,
            'status' => FacilityServiceOrderStatus::PENDING,
            'ordered_at' => now(),
        ])->loadMissing([
            'service:id,name,service_code,category,is_billable,selling_price',
            'orderedBy:id,first_name,last_name',
        ]);

        $this->syncFacilityServiceOrderCharge->handle($order);
        $this->ensureVisitInProgress($visit);

        return $order;
    }

    /**
     * @return array{0: PatientVisit, 1: Consultation|null}
     */
    private function resolveContext(Consultation|PatientVisit $context): array
    {
        if ($context instanceof Consultation) {
            return [$context->visit()->firstOrFail(), $context];
        }

        return [$context, $context->consultation];
    }

    private function ensureVisitInProgress(PatientVisit $visit): void
    {
        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }
    }
}
