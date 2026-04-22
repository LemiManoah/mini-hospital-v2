<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateTriageRecordDTO;
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_priority' => $this->boolean('requires_priority'),
            'is_pediatric' => $this->boolean('is_pediatric'),
            'poisoning_case' => $this->boolean('poisoning_case'),
            'snake_bite_case' => $this->boolean('snake_bite_case'),
        ]);
    }
}
