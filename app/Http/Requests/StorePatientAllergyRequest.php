<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StorePatientAllergyRequest extends FormRequest
{
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
            'allergen_id' => ['required', 'uuid', 'exists:allergens,id'],
            'severity' => ['required', new Enum(AllergySeverity::class)],
            'reaction' => ['required', new Enum(AllergyReaction::class)],
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
}


