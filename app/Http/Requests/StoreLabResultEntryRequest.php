<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LabRequestItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreLabResultEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'result_notes' => $this->filled('result_notes') ? $this->input('result_notes') : null,
            'parameter_values' => is_array($this->input('parameter_values')) ? $this->input('parameter_values') : [],
        ]);
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

            $resultType = $labRequestItem->test?->result_capture_type;

            if ($resultType === 'free_entry') {
                if (mb_trim((string) $this->input('free_entry_value', '')) === '') {
                    $validator->errors()->add('free_entry_value', 'Enter the lab result before saving.');
                }

                return;
            }

            if ($resultType === 'defined_option') {
                $selectedOption = mb_trim((string) $this->input('selected_option_label', ''));
                $allowedOptions = $labRequestItem->test?->resultOptions?->pluck('label')->all() ?? [];

                if ($selectedOption === '' || ! in_array($selectedOption, $allowedOptions, true)) {
                    $validator->errors()->add('selected_option_label', 'Choose a valid result option for this test.');
                }

                return;
            }

            if ($resultType !== 'parameter_panel') {
                return;
            }

            $submittedValues = collect($this->input('parameter_values', []))
                ->filter(static fn (mixed $item): bool => is_array($item))
                ->mapWithKeys(static fn (array $item): array => [
                    (string) ($item['lab_test_result_parameter_id'] ?? '') => $item,
                ]);

            foreach ($labRequestItem->test?->resultParameters ?? [] as $parameter) {
                $submitted = $submittedValues->get($parameter->id);
                $value = mb_trim((string) ($submitted['value'] ?? ''));

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

    private function labRequestItem(): mixed
    {
        return $this->route('labRequestItem') ?? $this->route('lab_request_item');
    }
}
