<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LabRequestItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CorrectLabResultEntryRequest extends FormRequest
{
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
            'correction_reason' => ['required', 'string'],
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

            if (mb_trim($this->string('correction_reason')->toString()) === '') {
                $validator->errors()->add('correction_reason', 'Enter the reason for correcting this released result.');
            }

            $resultType = $labRequestItem->test?->result_capture_type;

            if ($resultType === 'free_entry') {
                if (mb_trim($this->string('free_entry_value')->toString()) === '') {
                    $validator->errors()->add('free_entry_value', 'Enter the lab result before saving.');
                }

                return;
            }

            if ($resultType === 'defined_option') {
                $selectedOption = mb_trim($this->string('selected_option_label')->toString());
                $test = $labRequestItem->test;
                $allowedOptions = $test !== null
                    ? $test->resultOptions->pluck('label')->all()
                    : [];

                if ($selectedOption === '' || ! in_array($selectedOption, $allowedOptions, true)) {
                    $validator->errors()->add('selected_option_label', 'Choose a valid result option for this test.');
                }

                return;
            }

            if ($resultType !== 'parameter_panel') {
                return;
            }

            $parameterValues = $this->input('parameter_values', []);

            if (! is_array($parameterValues)) {
                $parameterValues = [];
            }

            $submittedValues = collect($parameterValues)
                ->filter(static fn (mixed $item): bool => is_array($item))
                ->mapWithKeys(static fn (array $item): array => [
                    is_string($item['lab_test_result_parameter_id'] ?? null) ? $item['lab_test_result_parameter_id'] : '' => $item,
                ]);

            $test = $labRequestItem->test;

            foreach ($test !== null ? $test->resultParameters : [] as $parameter) {
                $submitted = $submittedValues->get($parameter->id);
                $submittedValue = is_array($submitted) ? ($submitted['value'] ?? '') : '';
                $value = is_string($submittedValue) || is_numeric($submittedValue)
                    ? mb_trim((string) $submittedValue)
                    : '';

                if ($value === '') {
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
        $parameterValues = $this->input('parameter_values');

        $this->merge([
            'result_notes' => $this->filled('result_notes') ? $this->input('result_notes') : null,
            'correction_reason' => $this->filled('correction_reason') ? $this->input('correction_reason') : null,
            'parameter_values' => is_array($parameterValues) ? $parameterValues : [],
        ]);
    }

    private function labRequestItem(): mixed
    {
        return $this->route('labRequestItem') ?? $this->route('lab_request_item');
    }
}

