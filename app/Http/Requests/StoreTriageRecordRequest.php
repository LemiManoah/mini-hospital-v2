<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateTriageRecordDTO;
use App\Data\Clinical\CreateVitalSignDTO;
use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\MobilityStatus;
use App\Enums\TriageGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTriageRecordRequest extends FormRequest
{
    public function createDto(): CreateTriageRecordDTO
    {
        return CreateTriageRecordDTO::fromRequest($this);
    }

    public function createVitalSignDto(): CreateVitalSignDTO
    {
        return CreateVitalSignDTO::fromRequest($this);
    }

    public function hasVitalsData(): bool
    {
        $validated = $this->validated();

        $fields = [
            'temperature', 'pulse_rate', 'respiratory_rate', 'systolic_bp',
            'diastolic_bp', 'oxygen_saturation', 'blood_glucose', 'pain_score',
            'height_cm', 'weight_kg', 'head_circumference_cm', 'chest_circumference_cm',
            'muac_cm', 'capillary_refill', 'oxygen_delivery_method', 'oxygen_flow_rate',
        ];

        return array_any($fields, fn ($field): bool => filled($validated[$field] ?? null));
    }

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
            // Triage fields
            'triage_grade' => ['required', Rule::enum(TriageGrade::class)],
            'attendance_type' => ['required', Rule::enum(AttendanceType::class)],
            'news_score' => ['nullable', 'integer', 'min:0', 'max:20'],
            'pews_score' => ['nullable', 'integer', 'min:0', 'max:20'],
            'conscious_level' => ['required', Rule::enum(ConsciousLevel::class)],
            'mobility_status' => ['required', Rule::enum(MobilityStatus::class)],
            'chief_complaint' => ['required', 'string', 'max:1000'],
            'history_of_presenting_illness' => ['nullable', 'string'],
            'assigned_clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'requires_priority' => ['nullable', 'boolean'],
            'is_pediatric' => ['nullable', 'boolean'],
            'poisoning_case' => ['nullable', 'boolean'],
            'poisoning_agent' => ['nullable', 'string', 'max:100'],
            'snake_bite_case' => ['nullable', 'boolean'],
            'referred_by' => ['nullable', 'string', 'max:100'],
            'nurse_notes' => ['nullable', 'string'],

            // Vitals fields (all optional - saved alongside triage when provided)
            'temperature' => ['nullable', 'numeric'],
            'temperature_unit' => ['required', 'in:celsius,fahrenheit'],
            'pulse_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:120'],
            'systolic_bp' => ['nullable', 'integer', 'min:0', 'max:350'],
            'diastolic_bp' => ['nullable', 'integer', 'min:0', 'max:250'],
            'oxygen_saturation' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'on_supplemental_oxygen' => ['nullable', 'boolean'],
            'oxygen_delivery_method' => ['nullable', 'string', 'max:50'],
            'oxygen_flow_rate' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'blood_glucose' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'blood_glucose_unit' => ['required', 'in:mg_dl,mmol_l'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'head_circumference_cm' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'chest_circumference_cm' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'muac_cm' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'capillary_refill' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $newsScore = $this->integerInput('news_score');
        $pewsScore = $this->integerInput('pews_score');

        $this->merge([
            'requires_priority' => $this->boolean('requires_priority'),
            'is_pediatric' => $this->boolean('is_pediatric'),
            'poisoning_case' => $this->boolean('poisoning_case'),
            'snake_bite_case' => $this->boolean('snake_bite_case'),
            'news_score' => $newsScore,
            'pews_score' => $pewsScore,
            'on_supplemental_oxygen' => $this->boolean('on_supplemental_oxygen'),
            'temperature_unit' => $this->input('temperature_unit') ?: 'celsius',
            'blood_glucose_unit' => $this->input('blood_glucose_unit') ?: 'mg_dl',
        ]);
    }

    private function integerInput(string $key): ?int
    {
        $value = $this->input($key);

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
