<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use Illuminate\Foundation\Http\FormRequest;

final class StorePatientAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'allergen_id' => ['required', 'uuid', 'exists:allergens,id'],
            'severity' => ['required', 'in:' . implode(',', ['mild', 'moderate', 'severe', 'life_threatening'])],
            'reaction' => ['required', 'in:' . implode(',', ['rash', 'anaphylaxis', 'breathing_difficulty', 'itching', 'swelling', 'other'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'allergen_id.required' => 'Please select an allergen.',
            'allergen_id.exists' => 'Selected allergen is invalid.',
            'severity.required' => 'Please select a severity level.',
            'reaction.required' => 'Please select a reaction type.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'allergen_id' => 'allergen',
            'severity' => 'severity level',
            'reaction' => 'reaction type',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
