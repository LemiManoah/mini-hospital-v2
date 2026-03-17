<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Support\ValidatesAppointmentScheduling;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Appointment|null $appointment */
            $appointment = $this->route('appointment');

            resolve(ValidatesAppointmentScheduling::class)->validate(
                $validator,
                [
                    'doctor_id' => $appointment?->doctor_id,
                    'clinic_id' => $appointment?->clinic_id,
                    'appointment_date' => $this->input('appointment_date'),
                    'start_time' => $this->input('start_time'),
                    'end_time' => $this->input('end_time'),
                ],
            );
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'end_time' => $this->filled('end_time') ? $this->input('end_time') : null,
        ]);
    }
}
