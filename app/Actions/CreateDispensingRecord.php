<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DispensingRecordStatus;
use App\Enums\PrescriptionItemStatus;
use App\Models\DispensingRecord;
use App\Models\InventoryLocation;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\InventoryLocationAccess;
use App\Support\PrescriptionDispenseProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateDispensingRecord
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
        private PrescriptionDispenseProgress $prescriptionDispenseProgress,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Prescription $prescription, array $attributes, array $items): DispensingRecord
    {
        return DB::transaction(function () use ($prescription, $attributes, $items): DispensingRecord {
            /** @var PatientVisit $visit */
            $visit = $prescription->visit()->firstOrFail();
            $locationId = is_string($attributes['inventory_location_id'] ?? null)
                ? $attributes['inventory_location_id']
                : null;
            $visitBranchId = $visit->facility_branch_id;
            $visitTenantId = $visit->tenant_id;
            $dispensedAt = $attributes['dispensed_at'] ?? null;
            $recordNotes = $this->nullableText($attributes['notes'] ?? null);

            $canAccessLocation = $this->inventoryLocationAccess->canAccessLocationForTypes(
                Auth::user(),
                $locationId,
                ['pharmacy'],
                $visitBranchId,
            );

            if (! $canAccessLocation) {
                throw ValidationException::withMessages([
                    'inventory_location_id' => 'You can only create dispensing records for pharmacy locations you manage.',
                ]);
            }

            /** @var InventoryLocation $inventoryLocation */
            $inventoryLocation = InventoryLocation::query()->findOrFail($locationId);

            if (! $inventoryLocation->is_dispensing_point) {
                throw ValidationException::withMessages([
                    'inventory_location_id' => 'Select a pharmacy dispensing point to prepare this dispense record.',
                ]);
            }

            $record = DispensingRecord::query()->create([
                'tenant_id' => $visitTenantId,
                'branch_id' => $visitBranchId,
                'visit_id' => $visit->id,
                'prescription_id' => $prescription->id,
                'inventory_location_id' => $inventoryLocation->id,
                'dispense_number' => $this->generateDispenseNumber($visitTenantId),
                'dispensed_by' => Auth::id(),
                'dispensed_at' => $dispensedAt,
                'notes' => $recordNotes,
                'status' => DispensingRecordStatus::DRAFT,
            ]);

            $prescriptionItems = $prescription->items()
                ->with('inventoryItem:id,name,generic_name,brand_name,strength,dosage_form')
                ->get()
                ->keyBy('id');
            $postedLineSummaries = $this->prescriptionDispenseProgress->postedLineSummaries($prescription->id);

            foreach ($items as $item) {
                $prescriptionItemId = (string) ($item['prescription_item_id'] ?? '');
                /** @var PrescriptionItem|null $prescriptionItem */
                $prescriptionItem = $prescriptionItems->get($prescriptionItemId);

                if (! $prescriptionItem instanceof PrescriptionItem) {
                    continue;
                }

                $dispensedQuantity = round($this->floatValue($item['dispensed_quantity'] ?? 0), 3);
                $alreadyCoveredQuantity = (float) ($postedLineSummaries->get($prescriptionItem->id)['covered_quantity'] ?? 0.0);
                $prescribedQuantity = max(
                    0,
                    round($this->floatValue($prescriptionItem->quantity) - $alreadyCoveredQuantity, 3),
                );
                $externalPharmacy = (bool) ($item['external_pharmacy'] ?? false);
                $balanceQuantity = max(0, $prescribedQuantity - $dispensedQuantity);
                $externalReason = $this->nullableText($item['external_reason'] ?? null);
                $itemNotes = $this->nullableText($item['notes'] ?? null);

                $record->items()->create([
                    'prescription_item_id' => $prescriptionItem->id,
                    'inventory_item_id' => $prescriptionItem->inventory_item_id,
                    'prescribed_quantity' => $prescribedQuantity,
                    'dispensed_quantity' => $dispensedQuantity,
                    'balance_quantity' => $balanceQuantity,
                    'dispense_status' => $this->resolveItemStatus($dispensedQuantity, $prescribedQuantity, $externalPharmacy),
                    'substitution_inventory_item_id' => $item['substitution_inventory_item_id'] ?? null,
                    'external_pharmacy' => $externalPharmacy,
                    'external_reason' => $externalReason,
                    'notes' => $itemNotes,
                ]);
            }

            return $record->refresh()->load([
                'visit.patient',
                'prescription.prescribedBy',
                'inventoryLocation',
                'dispensedBy.staff',
                'items.prescriptionItem.inventoryItem',
                'items.inventoryItem',
                'items.substitutionInventoryItem',
            ]);
        });
    }

    private function resolveItemStatus(float $dispensedQuantity, float $prescribedQuantity, bool $externalPharmacy): PrescriptionItemStatus
    {
        if ($externalPharmacy) {
            return PrescriptionItemStatus::DISPENSED;
        }

        if ($dispensedQuantity <= 0) {
            return PrescriptionItemStatus::PENDING;
        }

        if ($dispensedQuantity >= $prescribedQuantity) {
            return PrescriptionItemStatus::DISPENSED;
        }

        return PrescriptionItemStatus::PARTIAL;
    }

    private function generateDispenseNumber(?string $tenantId): string
    {
        do {
            $dispenseNumber = 'DSP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && DispensingRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('dispense_number', $dispenseNumber)
                ->exists()
        );

        return $dispenseNumber;
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function floatValue(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }
}
