<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDepartmentRequest extends FormRequest
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
            'department_code' => ['required', 'string', 'max:20'],
            'department_name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'is_clinical' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'contact_info' => ['nullable', 'array'],
        ];
    }
}


