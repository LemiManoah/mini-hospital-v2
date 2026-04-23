<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\StoreLabResultEntryDTO;
use App\Models\LabRequestItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreLabResultEntryRequest extends FormRequest
{
    public function storeDto(): StoreLabResultEntryDTO
    {
        return StoreLabResultEntryDTO::fromRequest($this);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result_notes' => ['nullable', 'string'],
            'free_entry_value' => ['nullable', 'string'],
            'selected_option_label' => ['nullable', 'string', 'max:150'],
            'parameter_values' => ['nullable', 'array'],
            'parameter_values.*.lab_test_result_parameter_id' => ['required', 'uuid'],
            'parameter_values.*.value' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $labRequestItem = $this->labRequestItem();

            if (! $labRequestItem instanceof LabRequestItem) {
                return;
            }

            $labRequestItem->loadMissing([
                'test.resultOptions:id,lab_test_catalog_id,label',
                'test.resultParameters:id,lab_test_catalog_id,label,value_type',
            ]);

            $test = $labRequestItem->test;
            $resultType = $test?->result_capture_type;

            if ($test === null) {
                return;
            }

            if ($resultType === 'free_entry') {
                if ($this->trimmedInput('free_entry_value') === null) {
                    $validator->errors()->add('free_entry_value', 'Enter the lab result before saving.');
                }

                return;
            }

            if ($resultType === 'defined_option') {
                $selectedOption = $this->trimmedInput('selected_option_label');
                $allowedOptions = $test->resultOptions->pluck('label')->all();

                if ($selectedOption === null || ! in_array($selectedOption, $allowedOptions, true)) {
                    $validator->errors()->add('selected_option_label', 'Choose a valid result option for this test.');
                }

                return;
            }

            if ($resultType !== 'parameter_panel') {
                return;
            }

            $submittedValues = collect($this->parameterValuesInput())
                ->mapWithKeys(static fn (array $item): array => [
                    $item['lab_test_result_parameter_id'] => $item,
                ]);

            foreach ($test->resultParameters as $parameter) {
                $submitted = $submittedValues->get($parameter->id);
                $value = is_array($submitted) ? $submitted['value'] : null;

                if ($value === null) {
                    $validator->errors()->add(
                        'parameter_values',
                        sprintf('Enter a value for %s before saving the result.', $parameter->label),
                    );

                    continue;
                }

                if ($parameter->value_type === 'numeric' && ! is_numeric($value)) {
                    $validator->errors()->add(
                        'parameter_values',
                        sprintf('%s must be numeric.', $parameter->label),
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'result_notes' => $this->trimmedInput('result_notes'),
            'free_entry_value' => $this->trimmedInput('free_entry_value'),
            'selected_option_label' => $this->trimmedInput('selected_option_label'),
            'parameter_values' => $this->parameterValuesInput(),
        ]);
    }

    private function labRequestItem(): ?LabRequestItem
    {
        $labRequestItem = $this->route('labRequestItem') ?? $this->route('lab_request_item');

        return $labRequestItem instanceof LabRequestItem ? $labRequestItem : null;
    }

    /**
     * @return list<array{lab_test_result_parameter_id: string, value: ?string}>
     */
    private function parameterValuesInput(): array
    {
        $parameterValues = $this->input('parameter_values');

        if (! is_array($parameterValues)) {
            return [];
        }

        $normalizedValues = [];

        foreach ($parameterValues as $parameterValue) {
            if (! is_array($parameterValue)) {
                continue;
            }

            $parameterId = $parameterValue['lab_test_result_parameter_id'] ?? null;
            if (! is_string($parameterId)) {
                continue;
            }

            if ($parameterId === '') {
                continue;
            }

            $normalizedValues[] = [
                'lab_test_result_parameter_id' => $parameterId,
                'value' => $this->nullableString($parameterValue['value'] ?? null),
            ];
        }

        return $normalizedValues;
    }

    private function trimmedInput(string $key): ?string
    {
        return $this->nullableString($this->input($key));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
