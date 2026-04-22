<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LabResultType;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLabResultTypeRequest extends FormRequest
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
        /** @var LabResultType $resultType */
        $resultType = $this->route('result_type') ?? $this->route('resultType');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('result_types', 'code')
                    ->ignore($resultType->id)
                    ->where(static fn (QueryBuilder $query): QueryBuilder => $query
                        ->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('result_types', 'name')
                    ->ignore($resultType->id)
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
