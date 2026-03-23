<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FacilityServiceOrderStatus;
use App\Models\Consultation;
use App\Models\FacilityServiceOrder;
use Illuminate\Validation\ValidationException;

final readonly class CreateFacilityServiceOrder
{
    public function __construct(
        private SyncFacilityServiceOrderCharge $syncFacilityServiceOrderCharge,
    ) {}

    public function handle(Consultation $consultation, array $data, string $staffId): FacilityServiceOrder
    {
        $hasPendingDuplicate = FacilityServiceOrder::query()
            ->where('visit_id', $consultation->visit_id)
            ->where('facility_service_id', $data['facility_service_id'])
            ->where('status', FacilityServiceOrderStatus::PENDING->value)
            ->exists();

        if ($hasPendingDuplicate) {
            throw ValidationException::withMessages([
                'facility_service_id' => 'This facility service already has a pending order for this visit. Remove or complete the existing order first.',
            ]);
        }

        $order = FacilityServiceOrder::query()->create([
            'tenant_id' => $consultation->tenant_id,
            'facility_branch_id' => $consultation->facility_branch_id,
            'visit_id' => $consultation->visit_id,
            'consultation_id' => $consultation->id,
            'facility_service_id' => $data['facility_service_id'],
            'ordered_by' => $staffId,
            'status' => FacilityServiceOrderStatus::PENDING,
            'ordered_at' => now(),
        ])->loadMissing([
            'service:id,name,service_code,category,is_billable,selling_price',
            'orderedBy:id,first_name,last_name',
        ]);

        $this->syncFacilityServiceOrderCharge->handle($order);

        return $order;
    }
}
