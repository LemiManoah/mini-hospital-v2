<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateClinicRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'uuid', 'exists:facility_branches,id'],
            'clinic_code' => ['required', 'string', 'max:20'],
            'clinic_name' => ['required', 'string', 'max:100'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }
}
