<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'history_of_present_illness' => ['nullable', 'string'],
            'review_of_systems' => ['nullable', 'string'],
            'past_medical_history_summary' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'social_history' => ['nullable', 'string'],
            'subjective_notes' => ['nullable', 'string', 'max:1000'],
            'objective_findings' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'primary_diagnosis' => ['nullable', 'string', 'max:255'],
            'primary_icd10_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->missing('history_of_present_illness') && $this->filled('history_of_presenting_illness')) {
            $this->merge([
                'history_of_present_illness' => $this->input('history_of_presenting_illness'),
            ]);
        }
    }
}
