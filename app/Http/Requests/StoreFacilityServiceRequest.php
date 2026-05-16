<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConsultationType;
use App\Enums\FacilityServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreFacilityServiceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(FacilityServiceCategory::class)],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'is_billable' => ['nullable', 'boolean'],
            'is_consultation' => ['nullable', 'boolean'],
            'consultation_type' => ['nullable', Rule::enum(ConsultationType::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (($this->boolean('is_billable') || $this->boolean('is_consultation')) && ! $this->filled('unit_price')) {
                $validator->errors()->add('unit_price', 'Unit price is required for billable services.');
            }

            if ($this->boolean('is_consultation') && ! $this->filled('consultation_type')) {
                $validator->errors()->add('consultation_type', 'Consultation type is required for consultation services.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost_price' => $this->filled('cost_price') ? $this->input('cost_price') : null,
            'unit_price' => $this->filled('unit_price') ? $this->input('unit_price') : null,
            'is_billable' => $this->boolean('is_billable') || $this->boolean('is_consultation'),
            'is_consultation' => $this->boolean('is_consultation'),
            'consultation_type' => $this->boolean('is_consultation') ? $this->input('consultation_type') : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
