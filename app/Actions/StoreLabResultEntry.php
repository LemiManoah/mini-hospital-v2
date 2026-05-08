<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\StoreLabResultEntryDTO;
use App\Enums\LabOrderItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabOrderItem;
use App\Models\LabTestResultParameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StoreLabResultEntry
{
    public function __construct(
        private SyncLabOrderProgress $syncLabOrderProgress,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabOrderItem $labOrderItem, StoreLabResultEntryDTO $payload, string $staffId): LabOrderItem
    {
        if ($labOrderItem->approved_at !== null || $labOrderItem->status === LabOrderItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'result' => 'Approved results cannot be changed from this workspace.',
            ]);
        }

        if ($labOrderItem->received_at === null && ! $labOrderItem->specimen()->exists()) {
            throw ValidationException::withMessages([
                'result' => 'Pick a sample before entering results.',
            ]);
        }

        if ($labOrderItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'result' => 'Rejected specimens must be recollected before entering results.',
            ]);
        }

        return DB::transaction(function () use ($labOrderItem, $payload, $staffId): LabOrderItem {
            $resultEntry = $labOrderItem->resultEntry()->firstOrCreate([]);
            $resultEntry->forceFill([
                'entered_by' => $staffId,
                'entered_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'released_by' => null,
                'released_at' => null,
                'result_notes' => $payload->resultNotes,
                'review_notes' => null,
                'approval_notes' => null,
            ])->save();

            $resultEntry->values()->delete();

            $resultType = $labOrderItem->test()->with(['resultParameters', 'resultOptions'])->firstOrFail()->result_capture_type;

            if ($resultType === 'parameter_panel') {
                foreach ($payload->parameterValues as $index => $parameterValue) {
                    $parameter = LabTestResultParameter::query()->whereKey($parameterValue->labTestResultParameterId)->first();

                    $rawValue = $parameterValue->value;
                    $numericValue = $parameter?->value_type === 'numeric' && $rawValue !== null
                        ? (float) $rawValue
                        : null;

                    $resultEntry->values()->create([
                        'lab_test_result_parameter_id' => $parameter?->id,
                        'label' => $parameter instanceof LabTestResultParameter ? $parameter->label : 'Result',
                        'value_numeric' => $numericValue,
                        'value_text' => $parameter?->value_type === 'numeric' ? null : $rawValue,
                        'unit' => $parameter?->unit,
                        'gender' => $parameter?->gender,
                        'age_min' => $parameter?->age_min,
                        'age_max' => $parameter?->age_max,
                        'reference_range' => $parameter?->reference_range,
                        'sort_order' => $index + 1,
                    ]);
                }
            } else {
                $value = $resultType === 'defined_option'
                    ? $payload->selectedOptionLabel
                    : $payload->freeEntryValue;

                $resultEntry->values()->create([
                    'label' => 'Result',
                    'value_text' => $value,
                    'sort_order' => 1,
                ]);
            }

            $labOrderItem->forceFill([
                'status' => LabOrderItemStatus::IN_PROGRESS,
                'received_by' => $labOrderItem->received_by ?? $staffId,
                'received_at' => $labOrderItem->received_at ?? now(),
                'result_entered_by' => $staffId,
                'result_entered_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'completed_at' => null,
            ])->save();

            $this->syncLabOrderProgress->handle($labOrderItem->order()->firstOrFail());

            $labOrder = $labOrderItem->order()->firstOrFail();

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_result.entered',
                subject: $resultEntry,
                description: 'Lab result entered.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_order_id' => $labOrder->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'lab_result_entry_id' => $resultEntry->id,
                    'entered_at' => $resultEntry->entered_at?->toISOString(),
                    'result_entered_at' => $labOrderItem->result_entered_at?->toISOString(),
                ],
                metadata: [
                    'result_notes' => $payload->resultNotes,
                    'causer_user_id' => Auth::id(),
                ],
            );

            return $labOrderItem->refresh();
        });
    }
}
