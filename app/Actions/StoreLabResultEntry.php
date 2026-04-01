<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Models\LabRequestItem;
use App\Models\LabTestResultParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StoreLabResultEntry
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(LabRequestItem $labRequestItem, array $payload, string $staffId): LabRequestItem
    {
        if ($labRequestItem->approved_at !== null || $labRequestItem->status === LabRequestItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'result' => 'Approved results cannot be changed from this workspace.',
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
                'result_notes' => $this->nullableText($payload['result_notes'] ?? null),
                'review_notes' => null,
                'approval_notes' => null,
            ])->save();

            $resultEntry->values()->delete();

            $resultType = $labRequestItem->test()->with(['resultParameters', 'resultOptions'])->firstOrFail()->result_capture_type;

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

                    $resultEntry->values()->create([
                        'lab_test_result_parameter_id' => $parameter?->id,
                        'label' => $parameter?->label ?? (string) ($parameterValue['label'] ?? 'Result'),
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

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
