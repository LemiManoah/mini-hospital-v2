<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StaffType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreStaffRequest extends FormRequest
{
    /**
     * @return array<string, array<int, Enum|string>|string>
     */
    public function rules(): array
    {
        return [
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:staff,employee_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:staff,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'staff_position_id' => ['required', 'uuid', 'exists:staff_positions,id'],
            'type' => ['required', new Enum(StaffType::class)],
            'license_number' => ['nullable', 'string', 'max:50'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['required', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
