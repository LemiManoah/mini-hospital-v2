<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAppointmentModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('appointment_modes', 'name')->where('tenant_id', $this->user()?->tenant_id)],
            'description' => ['nullable', 'string'],
            'is_virtual' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_virtual' => $this->boolean('is_virtual'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
