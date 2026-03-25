<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateLabTestCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;
        /** @var LabTestCatalog $labTestCatalog */
        $labTestCatalog = $this->route('lab_test_catalog');

        return [
            'test_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('lab_test_catalogs', 'test_code')
                    ->ignore($labTestCatalog->id)
                    ->where(static fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'test_name' => ['required', 'string', 'max:200'],
            'lab_test_category_id' => [
                'required',
                'uuid',
                Rule::exists('lab_test_categories', 'id')->where(
                    static fn ($query) => $query->where('tenant_id', $tenantId)
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
                    static fn ($query) => $query->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')
                ),
            ],
            'result_type_id' => [
                'required',
                'uuid',
                Rule::exists('result_types', 'id')->where(
                    static fn ($query) => $query->where('tenant_id', $tenantId)
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->filled('description') ? $this->input('description') : null,
            'specimen_type_ids' => collect($this->input('specimen_type_ids', []))
                ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
                ->unique()
                ->values()
                ->all(),
            'is_active' => $this->boolean('is_active', true),
            'result_options' => is_array($this->input('result_options')) ? $this->input('result_options') : [],
            'result_parameters' => is_array($this->input('result_parameters')) ? $this->input('result_parameters') : [],
        ]);
    }

    private function selectedResultTypeCode(): ?string
    {
        $resultTypeId = $this->input('result_type_id');

        if (! is_string($resultTypeId) || $resultTypeId === '') {
            return null;
        }

        return LabResultType::query()
            ->whereKey($resultTypeId)
            ->value('code');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filledResultOptions(): array
    {
        return collect($this->input('result_options', []))
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->filter(static fn (array $item): bool => mb_trim((string) ($item['label'] ?? '')) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filledResultParameters(): array
    {
        return collect($this->input('result_parameters', []))
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->filter(static fn (array $item): bool => mb_trim((string) ($item['label'] ?? '')) !== '')
            ->values()
            ->all();
    }
}
