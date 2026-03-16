<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ScheduleDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'uuid', 'exists:staff,id'],
            'clinic_id' => ['required', 'uuid', 'exists:clinics,id'],
            'day_of_week' => ['required', Rule::enum(ScheduleDay::class)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'max_patients' => ['required', 'integer', 'min:1', 'max:500'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valid_to' => $this->filled('valid_to') ? $this->input('valid_to') : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
