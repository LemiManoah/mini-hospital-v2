<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AppointmentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAppointmentCategoryRequest extends FormRequest
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
        /** @var AppointmentCategory $category */
        $category = $this->route('appointment_category') ?? $this->route('appointmentCategory');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('appointment_categories', 'name')->where('tenant_id', $this->user()?->tenant_id)->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'clinic_id' => $this->filled('clinic_id') ? $this->input('clinic_id') : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}

