<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabOrderItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabSpecimen;
use App\Models\LabTestCatalog;
use App\Models\SpecimenType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CollectLabSpecimen
{
    public function __construct(
        private SyncLabOrderProgress $syncLabOrderProgress,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(LabOrderItem $labOrderItem, array $payload, string $staffId): LabOrderItem
    {
        if ($labOrderItem->status === LabOrderItemStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Cancelled lab items cannot have samples picked.',
            ]);
        }

        if ($labOrderItem->approved_at !== null || $labOrderItem->status === LabOrderItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Released lab items cannot have their sample picking changed.',
            ]);
        }

        /** @var LabTestCatalog|null $labTest */
        $labTest = $labOrderItem->test()->first();

        /** @var SpecimenType|null $specimenType */
        $specimenType = $labTest instanceof LabTestCatalog
            ? $labTest->specimenTypes()
                ->whereKey($payload['specimen_type_id'] ?? null)
                ->first(['specimen_types.id', 'specimen_types.name'])
            : null;

        if (! $specimenType instanceof SpecimenType) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Choose one of the specimen types configured for this test.',
            ]);
        }

        return DB::transaction(function () use ($labOrderItem, $payload, $staffId, $specimenType): LabOrderItem {
            /** @var LabSpecimen|null $existingSpecimen */
            $existingSpecimen = $labOrderItem->specimen()->first();
            $collectedAt = $existingSpecimen instanceof LabSpecimen
                ? $existingSpecimen->collected_at ?? now()
                : now();
            $accessionNumber = $existingSpecimen instanceof LabSpecimen
                ? $existingSpecimen->accession_number ?? $this->generateAccessionNumber()
                : $this->generateAccessionNumber();

            $labOrderItem->specimen()->updateOrCreate(
                [],
                [
                    'accession_number' => $accessionNumber,
                    'specimen_type_id' => $specimenType->id,
                    'specimen_type_name' => $specimenType->name,
                    'status' => LabSpecimenStatus::COLLECTED,
                    'collected_by' => $staffId,
                    'collected_at' => $collectedAt,
                    'outside_sample' => (bool) ($payload['outside_sample'] ?? false),
                    'outside_sample_origin' => $this->nullableText($payload['outside_sample_origin'] ?? null),
                    'notes' => $this->nullableText($payload['notes'] ?? null),
                ],
            );

            $labOrderItem->forceFill([
                'received_by' => $staffId,
                'received_at' => $labOrderItem->received_at ?? $collectedAt,
            ])->save();

            /** @var LabOrder $labOrder */
            $labOrder = $labOrderItem->order()->firstOrFail();

            $this->syncLabOrderProgress->handle($labOrder);

            $specimen = $labOrderItem->specimen()->firstOrFail();

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_specimen.collected',
                subject: $labOrderItem,
                description: 'Lab specimen collected.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_order_id' => $labOrder->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'lab_specimen_id' => $specimen->id,
                    'specimen_type_id' => $specimen->specimen_type_id,
                    'collected_at' => $specimen->collected_at?->toISOString(),
                    'outside_sample' => $specimen->outside_sample,
                ],
                metadata: [
                    'specimen_type_name' => $specimen->specimen_type_name,
                    'outside_sample_origin' => $specimen->outside_sample_origin,
                    'notes' => $specimen->notes,
                    'causer_user_id' => Auth::id(),
                ],
            );

            return $labOrderItem->refresh()->loadMissing([
                'specimen',
                'specimen.specimenType',
                'specimen.collectedBy',
            ]);
        });
    }

    private function generateAccessionNumber(): string
    {
        do {
            $candidate = sprintf('LAB-%s-%s', now()->format('YmdHis'), Str::upper(Str::random(4)));
        } while (LabSpecimen::query()->where('accession_number', $candidate)->exists());

        return $candidate;
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
