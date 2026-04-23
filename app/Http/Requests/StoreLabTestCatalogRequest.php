<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateLabTestCatalogDTO;
use App\Models\LabResultType;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreLabTestCatalogRequest extends FormRequest
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
        $tenantId = $this->user()?->tenant_id;

        return [
            'test_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('lab_test_catalogs', 'test_code')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                ),
            ],
            'test_name' => ['required', 'string', 'max:200'],
            'lab_test_category_id' => [
                'required',
                'uuid',
                Rule::exists('lab_test_categories', 'id')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')
                ),
            ],
            'specimen_type_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'specimen_type_ids.*' => [
                'required',
                'uuid',
                Rule::exists('specimen_types', 'id')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')
                ),
            ],
            'result_type_id' => [
                'required',
                'uuid',
                Rule::exists('result_types', 'id')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')
                ),
            ],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'result_options' => ['nullable', 'array'],
            'result_options.*.label' => ['nullable', 'string', 'max:150'],
            'result_parameters' => ['nullable', 'array'],
            'result_parameters.*.label' => ['nullable', 'string', 'max:150'],
            'result_parameters.*.unit' => ['nullable', 'string', 'max:50'],
            'result_parameters.*.reference_range' => ['nullable', 'string', 'max:120'],
            'result_parameters.*.value_type' => ['nullable', Rule::in(['numeric', 'text'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $resultTypeCode = $this->selectedResultTypeCode();

            if ($resultTypeCode === 'defined_option' && $this->filledResultOptions() === []) {
                $validator->errors()->add('result_options', 'Add at least one result option for defined option tests.');
            }

            if ($resultTypeCode === 'parameter_panel' && $this->filledResultParameters() === []) {
                $validator->errors()->add('result_parameters', 'Add at least one result parameter for parameter panel tests.');
            }
        });
    }

    public function createDto(): CreateLabTestCatalogDTO
    {
        return CreateLabTestCatalogDTO::fromRequest($this);
    }

    protected function prepareForValidation(): void
    {
        $specimenTypeIds = $this->specimenTypeIdsInput();
        $resultOptions = $this->resultOptionsInput();
        $resultParameters = $this->resultParametersInput();

        $this->merge([
            'description' => $this->filled('description') ? $this->input('description') : null,
            'specimen_type_ids' => $specimenTypeIds,
            'is_active' => $this->boolean('is_active', true),
            'result_options' => $resultOptions,
            'result_parameters' => $resultParameters,
        ]);
    }

    private function selectedResultTypeCode(): ?string
    {
        $resultTypeId = $this->input('result_type_id');

        if (! is_string($resultTypeId) || $resultTypeId === '') {
            return null;
        }

        $code = LabResultType::query()
            ->whereKey($resultTypeId)
            ->value('code');

        return is_string($code) ? $code : null;
    }

    /**
     * @return list<array{label: string}>
     */
    private function filledResultOptions(): array
    {
        return array_values(array_filter(
            $this->resultOptionsInput(),
            static fn (array $item): bool => $item['label'] !== '',
        ));
    }

    /**
     * @return list<array{label: string, unit: ?string, reference_range: ?string, value_type: ?string}>
     */
    private function filledResultParameters(): array
    {
        return array_values(array_filter(
            $this->resultParametersInput(),
            static fn (array $item): bool => $item['label'] !== '',
        ));
    }

    /**
     * @return list<string>
     */
    private function specimenTypeIdsInput(): array
    {
        $value = $this->input('specimen_type_ids');

        if (! is_array($value)) {
            return [];
        }

        $specimenTypeIds = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $trimmed = mb_trim($item);
            if ($trimmed === '') {
                continue;
            }

            if (in_array($trimmed, $specimenTypeIds, true)) {
                continue;
            }

            $specimenTypeIds[] = $trimmed;
        }

        return $specimenTypeIds;
    }

    /**
     * @return list<array{label: string}>
     */
    private function resultOptionsInput(): array
    {
        $value = $this->input('result_options');

        if (! is_array($value)) {
            return [];
        }

        $resultOptions = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = $item['label'] ?? null;

            $resultOptions[] = [
                'label' => is_string($label) ? mb_trim($label) : '',
            ];
        }

        return $resultOptions;
    }

    /**
     * @return list<array{label: string, unit: ?string, reference_range: ?string, value_type: ?string}>
     */
    private function resultParametersInput(): array
    {
        $value = $this->input('result_parameters');

        if (! is_array($value)) {
            return [];
        }

        $resultParameters = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = $item['label'] ?? null;
            $unit = $item['unit'] ?? null;
            $referenceRange = $item['reference_range'] ?? null;
            $valueType = $item['value_type'] ?? null;

            $resultParameters[] = [
                'label' => is_string($label) ? mb_trim($label) : '',
                'unit' => is_string($unit) ? (mb_trim($unit) !== '' ? mb_trim($unit) : null) : null,
                'reference_range' => is_string($referenceRange)
                    ? (mb_trim($referenceRange) !== '' ? mb_trim($referenceRange) : null)
                    : null,
                'value_type' => is_string($valueType) ? (mb_trim($valueType) !== '' ? mb_trim($valueType) : null) : null,
            ];
        }

        return $resultParameters;
    }
}
