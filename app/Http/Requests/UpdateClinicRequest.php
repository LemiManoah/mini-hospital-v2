<?php

declare(strict_types = 1)
;

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateClinicRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'uuid', 'exists:facility_branches,id'],
            'clinic_code' => ['required', 'string', 'max:20'],
            'clinic_name' => ['required', 'string', 'max:100'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }
}
