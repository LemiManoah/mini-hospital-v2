<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FacilityServiceOrderStatus;
use App\Models\Consultation;
use App\Models\FacilityServiceOrder;

final readonly class CreateFacilityServiceOrder
{
    public function __construct(
        private SyncFacilityServiceOrderCharge $syncFacilityServiceOrderCharge,
    ) {}

    public function handle(Consultation $consultation, array $data, string $staffId): FacilityServiceOrder
    {
        $order = FacilityServiceOrder::query()->create([
            'tenant_id' => $consultation->tenant_id,
            'facility_branch_id' => $consultation->facility_branch_id,
            'visit_id' => $consultation->visit_id,
            'consultation_id' => $consultation->id,
            'facility_service_id' => $data['facility_service_id'],
            'ordered_by' => $staffId,
            'status' => FacilityServiceOrderStatus::PENDING,
            'clinical_notes' => $this->nullableText($data['clinical_notes'] ?? null),
            'service_instructions' => $this->nullableText($data['service_instructions'] ?? null),
            'ordered_at' => now(),
        ])->loadMissing([
            'service:id,name,service_code,category,is_billable',
            'orderedBy:id,first_name,last_name',
        ]);

        $this->syncFacilityServiceOrderCharge->handle($order);

        return $order;
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
