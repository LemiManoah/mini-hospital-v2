<?php

declare(strict_types = 1)
;

namespace App\Http\Requests;

use App\Enums\FacilityServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFacilityServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_code' => ['required', 'string', 'max:50', Rule::unique('facility_services', 'service_code')],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(FacilityServiceCategory::class)],
            'department_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'default_instructions' => ['nullable', 'string'],
            'is_billable' => ['nullable', 'boolean'],
            'charge_master_id' => ['nullable', 'string', 'max:36'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_billable' => $this->boolean('is_billable'),
            'is_active' => $this->boolean('is_active', true),
            'charge_master_id' => $this->filled('charge_master_id') ? $this->input('charge_master_id') : null,
        ]);
    }
}
