<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOnboardingDepartmentsRequest extends FormRequest
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
            'departments' => ['required', 'array', 'min:1'],
            'departments.*.name' => ['required', 'string', 'max:100'],
            'departments.*.location' => ['nullable', 'string', 'max:100'],
            'departments.*.is_clinical' => ['nullable', 'boolean'],
        ];
    }
}


