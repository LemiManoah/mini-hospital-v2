<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class PrescriptionQueueQuery
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public function paginate(string $search = '', string $status = ''): LengthAwarePaginator
    {
        $query = Prescription::query()
            ->whereHas('visit', fn (Builder $visitQuery): Builder => $this->activeBranchWorkspace->apply($visitQuery))
            ->whereIn('status', $this->queueStatuses($status))
            ->with([
                'visit:id,patient_id,facility_branch_id,visit_number,registered_at',
                'visit.patient:id,patient_number,first_name,last_name,gender,phone_number',
                'consultation:id,visit_id,primary_diagnosis',
                'prescribedBy:id,first_name,last_name',
                'items:id,prescription_id,inventory_item_id,dosage,frequency,route,duration_days,quantity,instructions,is_external_pharmacy,status,dispensed_at',
                'items.inventoryItem:id,name,generic_name,brand_name,strength,dosage_form',
            ])
            ->latest('prescription_date');

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('primary_diagnosis', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('pharmacy_notes', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('visit', static function (Builder $visitQuery) use ($search): void {
                        $visitQuery
                            ->where('visit_number', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search));
                            });
                    })
                    ->orWhereHas('items.inventoryItem', static function (Builder $itemQuery) use ($search): void {
                        $itemQuery
                            ->where('name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('generic_name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('brand_name', 'like', sprintf('%%%s%%', $search));
                    });
            });
        }

        return $query->paginate(10)->withQueryString();
    }

    public function findForPharmacy(string $prescriptionId): ?Prescription
    {
        return Prescription::query()
            ->whereKey($prescriptionId)
            ->whereHas('visit', fn (Builder $visitQuery): Builder => $this->activeBranchWorkspace->apply($visitQuery))
            ->with([
                'visit:id,patient_id,facility_branch_id,visit_number,registered_at',
                'visit.patient:id,patient_number,first_name,last_name,gender,phone_number',
                'consultation:id,visit_id,primary_diagnosis',
                'prescribedBy:id,first_name,last_name',
                'items:id,prescription_id,inventory_item_id,dosage,frequency,route,duration_days,quantity,instructions,is_external_pharmacy,status,dispensed_at',
                'items.inventoryItem:id,name,generic_name,brand_name,strength,dosage_form',
                'dispensingRecords:id,branch_id,visit_id,prescription_id,inventory_location_id,dispense_number,dispensed_by,dispensed_at,status',
                'dispensingRecords.inventoryLocation:id,name,location_code',
                'dispensingRecords.dispensedBy:id,staff_id,email',
                'dispensingRecords.dispensedBy.staff:id,first_name,last_name',
            ])
            ->first();
    }

    /**
     * @return list<string>
     */
    private function queueStatuses(string $status): array
    {
        $allowedStatuses = [
            PrescriptionStatus::PENDING->value,
            PrescriptionStatus::PARTIALLY_DISPENSED->value,
        ];

        return in_array($status, $allowedStatuses, true)
            ? [$status]
            : $allowedStatuses;
    }
}
