<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SpecimenType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSpecimenTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;
        /** @var SpecimenType $specimenType */
        $specimenType = $this->route('specimen_type') ?? $this->route('specimenType');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specimen_types', 'name')
                    ->ignore($specimenType->id)
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
