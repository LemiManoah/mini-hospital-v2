<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ScheduleExceptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDoctorScheduleExceptionRequest extends FormRequest
{
    /**

     * @return array<string, mixed>

     */

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'uuid', 'exists:staff,id'],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'exception_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i', Rule::requiredIf(! $this->boolean('is_all_day'))],
            'end_time' => ['nullable', 'date_format:H:i', Rule::requiredIf(! $this->boolean('is_all_day')), 'after:start_time'],
            'type' => ['required', Rule::enum(ScheduleExceptionType::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
            'is_all_day' => ['nullable', 'boolean'],
        ];
    }
}

