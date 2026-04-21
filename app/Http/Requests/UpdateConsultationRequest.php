<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConsultationOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateConsultationRequest extends FormRequest
{
    /**

     * @return array<string, mixed>

     */

    public function rules(): array
    {
        return [
            'intent' => ['required', Rule::in(['save_draft', 'complete'])],
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
            'outcome' => ['nullable', Rule::enum(ConsultationOutcome::class)],
            'follow_up_instructions' => ['nullable', 'string'],
            'follow_up_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_referred' => ['nullable', 'boolean'],
            'referred_to_department' => ['nullable', 'string', 'max:100'],
            'referred_to_facility' => ['nullable', 'string', 'max:100'],
            'referral_reason' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $intent = $this->string('intent')->toString();
            if ($intent !== 'complete') {
                return;
            }

            if (mb_trim((string) $this->input('chief_complaint', '')) === '') {
                $validator->errors()->add('chief_complaint', 'Chief complaint is required before completing the consultation.');
            }

            if (mb_trim((string) $this->input('primary_diagnosis', '')) === '') {
                $validator->errors()->add('primary_diagnosis', 'Primary diagnosis is required before completing the consultation.');
            }

            if (
                mb_trim((string) $this->input('assessment', '')) === ''
                && mb_trim((string) $this->input('plan', '')) === ''
            ) {
                $validator->errors()->add('assessment', 'Add an assessment or a plan before completing the consultation.');
            }

            if (mb_trim((string) $this->input('outcome', '')) === '') {
                $validator->errors()->add('outcome', 'Outcome is required before completing the consultation.');
            }

            if ($this->boolean('is_referred')) {
                $hasDestination = mb_trim((string) $this->input('referred_to_department', '')) !== ''
                    || mb_trim((string) $this->input('referred_to_facility', '')) !== '';

                if (! $hasDestination) {
                    $validator->errors()->add('referred_to_department', 'Add a referral destination before completing the consultation.');
                }

                if (mb_trim((string) $this->input('referral_reason', '')) === '') {
                    $validator->errors()->add('referral_reason', 'Referral reason is required before completing the consultation.');
                }
            }

            if ($this->filled('follow_up_days') && mb_trim((string) $this->input('follow_up_instructions', '')) === '') {
                $validator->errors()->add('follow_up_instructions', 'Follow-up instructions are required when follow-up days are provided.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->missing('history_of_present_illness') && $this->filled('history_of_presenting_illness')) {
            $this->merge([
                'history_of_present_illness' => $this->input('history_of_presenting_illness'),
            ]);
        }

        $this->merge([
            'intent' => $this->input('intent') ?: 'save_draft',
            'is_referred' => $this->boolean('is_referred'),
            'outcome' => $this->filled('outcome') ? $this->input('outcome') : null,
        ]);
    }
}

