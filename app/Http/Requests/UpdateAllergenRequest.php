<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AllergyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAllergenRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('allergens', 'name')->ignore($this->route('allergen')),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(AllergyType::class)],
        ];
    }
}


