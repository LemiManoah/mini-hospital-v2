<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'uuid', 'exists:staff,id'],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'appointment_category_id' => ['nullable', 'uuid', 'exists:appointment_categories,id'],
            'appointment_mode_id' => ['nullable', 'uuid', 'exists:appointment_modes,id'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'reason_for_visit' => ['required', 'string'],
            'chief_complaint' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_walk_in' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'doctor_id' => $this->filled('doctor_id') ? $this->input('doctor_id') : null,
            'clinic_id' => $this->filled('clinic_id') ? $this->input('clinic_id') : null,
            'appointment_category_id' => $this->filled('appointment_category_id') ? $this->input('appointment_category_id') : null,
            'appointment_mode_id' => $this->filled('appointment_mode_id') ? $this->input('appointment_mode_id') : null,
            'end_time' => $this->filled('end_time') ? $this->input('end_time') : null,
            'is_walk_in' => $this->boolean('is_walk_in'),
        ]);
    }
}
