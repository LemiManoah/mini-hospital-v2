<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOnboardingBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'branch_code' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'main_contact' => ['nullable', 'string', 'max:20'],
            'other_contact' => ['nullable', 'string', 'max:20'],
            'currency_id' => ['required', 'string', 'exists:currencies,id'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'string', 'exists:countries,id'],
            'has_store' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_store' => $this->boolean('has_store'),
        ]);
    }
}
