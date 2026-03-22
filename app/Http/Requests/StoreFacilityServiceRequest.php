<?php

declare(strict_types=1);

namespace App\Http\Requests;

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

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(FacilityServiceCategory::class)],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'is_billable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_billable') && ! $this->filled('selling_price')) {
                $validator->errors()->add('selling_price', 'Selling price is required for billable services.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost_price' => $this->filled('cost_price') ? $this->input('cost_price') : null,
            'selling_price' => $this->filled('selling_price') ? $this->input('selling_price') : null,
            'is_billable' => $this->boolean('is_billable'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
