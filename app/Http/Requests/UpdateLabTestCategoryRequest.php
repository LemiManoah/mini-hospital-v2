<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LabTestCategory;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLabTestCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;
        /** @var LabTestCategory $labTestCategory */
        $labTestCategory = $this->route('lab_test_category') ?? $this->route('labTestCategory');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lab_test_categories', 'name')
                    ->ignore($labTestCategory->id)
                    ->where(static fn (QueryBuilder $query): QueryBuilder => $query
                        ->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->filled('description') ? $this->input('description') : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
