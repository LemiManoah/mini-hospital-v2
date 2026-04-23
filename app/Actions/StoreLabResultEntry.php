<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\StoreLabResultEntryDTO;
use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequestItem;
use App\Models\LabTestResultParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StoreLabResultEntry
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
    ) {}

    public function handle(LabRequestItem $labRequestItem, StoreLabResultEntryDTO $payload, string $staffId): LabRequestItem
    {
        if ($labRequestItem->approved_at !== null || $labRequestItem->status === LabRequestItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'result' => 'Approved results cannot be changed from this workspace.',
            ]);
        }

        if ($labRequestItem->received_at === null && ! $labRequestItem->specimen()->exists()) {
            throw ValidationException::withMessages([
                'result' => 'Pick a sample before entering results.',
            ]);
        }

        if ($labRequestItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'result' => 'Rejected specimens must be recollected before entering results.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $payload, $staffId): LabRequestItem {
            $resultEntry = $labRequestItem->resultEntry()->firstOrCreate([]);
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

            $resultType = $labRequestItem->test()->with(['resultParameters', 'resultOptions'])->firstOrFail()->result_capture_type;

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

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::IN_PROGRESS,
                'received_by' => $labRequestItem->received_by ?? $staffId,
                'received_at' => $labRequestItem->received_at ?? now(),
                'result_entered_by' => $staffId,
                'result_entered_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'completed_at' => null,
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh();
        });
    }
}
