<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabOrderItemStatus;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResultEntry;
use App\Models\LabResultValue;
use App\Models\LabTestCatalog;
use App\Models\LabTestResultParameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CorrectLabResultEntry
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
        if ($labOrderItem->approved_at === null || $labOrderItem->status !== LabOrderItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'correction' => 'Only released results can be corrected from this workflow.',
            ]);
        }

        /** @var LabResultEntry|null $resultEntry */
        $resultEntry = $labOrderItem->resultEntry()->first();

        if ($resultEntry === null) {
            throw ValidationException::withMessages([
                'correction' => 'There is no released result available to correct.',
            ]);
        }

        return DB::transaction(function () use ($labOrderItem, $resultEntry, $payload, $staffId): LabOrderItem {
            $timestamp = now();
            $oldValues = [
                'result_notes' => $resultEntry->result_notes,
                'review_notes' => $resultEntry->review_notes,
                'approval_notes' => $resultEntry->approval_notes,
                'released_at' => $resultEntry->released_at?->toISOString(),
                'approved_at' => $resultEntry->approved_at?->toISOString(),
                'values' => $resultEntry->values()->orderBy('sort_order')->get()->map(
                    static fn (LabResultValue $value): array => [
                        'id' => $value->id,
                        'parameter_id' => $value->lab_test_result_parameter_id,
                        'label' => $value->label,
                        'value_numeric' => $value->value_numeric,
                        'value_text' => $value->value_text,
                    ],
                )->all(),
            ];

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
            $labTest = $labOrderItem->test()->with(['resultParameters', 'resultOptions'])->firstOrFail();
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

            $labOrderItem->forceFill([
                'status' => LabOrderItemStatus::IN_PROGRESS,
                'result_entered_by' => $staffId,
                'result_entered_at' => $timestamp,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'completed_at' => null,
            ])->save();

            /** @var LabOrder $labOrder */
            $labOrder = $labOrderItem->order()->firstOrFail();

            $this->syncLabOrderProgress->handle($labOrder);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_result.corrected',
                subject: $resultEntry,
                description: 'Lab result corrected and reopened for review.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $staffId,
                reason: $this->nullableText($payload['correction_reason'] ?? null),
                oldValues: $oldValues,
                newValues: [
                    'lab_order_id' => $labOrder->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'lab_result_entry_id' => $resultEntry->id,
                    'status' => $labOrderItem->status->value,
                    'entered_by' => $staffId,
                    'entered_at' => $timestamp->toISOString(),
                    'corrected_by' => $staffId,
                    'corrected_at' => $timestamp->toISOString(),
                    'correction_reason' => $this->nullableText($payload['correction_reason'] ?? null),
                ],
                metadata: [
                    'causer_user_id' => Auth::id(),
                ],
            );

            return $labOrderItem->refresh();
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
