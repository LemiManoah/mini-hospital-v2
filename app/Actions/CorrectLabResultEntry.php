<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResultEntry;
use App\Models\LabTestCatalog;
use App\Models\LabTestResultParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CorrectLabResultEntry
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(LabRequestItem $labRequestItem, array $payload, string $staffId): LabRequestItem
    {
        if ($labRequestItem->approved_at === null || $labRequestItem->status !== LabRequestItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'correction' => 'Only released results can be corrected from this workflow.',
            ]);
        }

        /** @var LabResultEntry|null $resultEntry */
        $resultEntry = $labRequestItem->resultEntry()->first();

        if ($resultEntry === null) {
            throw ValidationException::withMessages([
                'correction' => 'There is no released result available to correct.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $resultEntry, $payload, $staffId): LabRequestItem {
            $timestamp = now();

            $resultEntry->forceFill([
                'entered_by' => $staffId,
                'entered_at' => $timestamp,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'released_by' => null,
                'released_at' => null,
                'corrected_by' => $staffId,
                'corrected_at' => $timestamp,
                'result_notes' => $this->nullableText($payload['result_notes'] ?? null),
                'review_notes' => null,
                'approval_notes' => null,
                'correction_reason' => $this->nullableText($payload['correction_reason'] ?? null),
            ])->save();

            $resultEntry->values()->delete();

            /** @var LabTestCatalog $labTest */
            $labTest = $labRequestItem->test()->with(['resultParameters', 'resultOptions'])->firstOrFail();
            $resultType = $this->resultCaptureType($labTest);

            if ($resultType === 'parameter_panel') {
                /** @var array<int, array<string, mixed>> $parameterValues */
                $parameterValues = is_array($payload['parameter_values'] ?? null) ? $payload['parameter_values'] : [];

                foreach ($parameterValues as $index => $parameterValue) {
                    $parameterId = $parameterValue['lab_test_result_parameter_id'] ?? null;
                    $parameter = is_string($parameterId)
                        ? LabTestResultParameter::query()->whereKey($parameterId)->first()
                        : null;

                    $rawValue = $this->nullableText($parameterValue['value'] ?? null);
                    $numericValue = $parameter?->value_type === 'numeric' && $rawValue !== null
                        ? (float) $rawValue
                        : null;
                    $label = $this->nullableText($parameterValue['label'] ?? null) ?? 'Result';

                    $resultEntry->values()->create([
                        'lab_test_result_parameter_id' => $parameter?->id,
                        'label' => $parameter !== null ? $parameter->label : $label,
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
                    ? $this->nullableText($payload['selected_option_label'] ?? null)
                    : $this->nullableText($payload['free_entry_value'] ?? null);

                $resultEntry->values()->create([
                    'label' => 'Result',
                    'value_text' => $value,
                    'sort_order' => 1,
                ]);
            }

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::IN_PROGRESS,
                'result_entered_by' => $staffId,
                'result_entered_at' => $timestamp,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'completed_at' => null,
            ])->save();

            /** @var LabRequest $labRequest */
            $labRequest = $labRequestItem->request()->firstOrFail();

            $this->syncLabRequestProgress->handle($labRequest);

            return $labRequestItem->refresh();
        });
    }

    private function resultCaptureType(LabTestCatalog $labTest): ?string
    {
        $code = $labTest->resultTypeDefinition()->value('code');

        return is_string($code) ? $code : null;
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
