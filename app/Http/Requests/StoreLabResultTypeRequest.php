<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLabResultTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('result_types', 'code')
                    ->where(static fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id')),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('result_types', 'name')
                    ->where(static fn ($query) => $query
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
