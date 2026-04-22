<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Onboarding\CreateOnboardingDepartmentsDTO;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOnboardingDepartmentsRequest extends FormRequest
{
    public function createDto(): CreateOnboardingDepartmentsDTO
    {
        return CreateOnboardingDepartmentsDTO::fromRequest($this);
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
            'departments' => ['required', 'array', 'min:1'],
            'departments.*.name' => ['required', 'string', 'max:100'],
            'departments.*.location' => ['nullable', 'string', 'max:100'],
            'departments.*.is_clinical' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $departments = $this->input('departments', []);

        if (! is_array($departments)) {
            return;
        }

        $normalized = [];

        foreach ($departments as $department) {
            if (! is_array($department)) {
                continue;
            }

            $normalized[] = [
                'name' => is_string($department['name'] ?? null) ? $department['name'] : '',
                'location' => is_string($department['location'] ?? null) ? $department['location'] : null,
                'is_clinical' => filter_var($department['is_clinical'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        $this->merge([
            'departments' => $normalized,
        ]);
    }
}
