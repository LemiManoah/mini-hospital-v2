<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\KinRelationship;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'age_input_mode' => ['required', Rule::in(['dob', 'age'])],
            'date_of_birth' => ['nullable', 'required_if:age_input_mode,dob', 'date'],
            'age' => ['nullable', 'required_if:age_input_mode,age', 'integer', 'min:0', 'max:150'],
            'age_units' => ['nullable', 'required_if:age_input_mode,age', Rule::in(['year', 'month', 'day'])],
            'gender' => ['required', new Enum(Gender::class)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients', 'email')->ignore($patient->id)],
            'phone_number' => ['required', 'string', 'max:20'],
            'alternative_phone' => ['nullable', 'string', 'max:20'],
            'next_of_kin_name' => ['nullable', 'string', 'max:100'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20'],
            'next_of_kin_relationship' => ['nullable', new Enum(KinRelationship::class)],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'occupation' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', new Enum(Religion::class)],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'blood_group' => ['nullable', new Enum(BloodGroup::class)],
        ];
    }
}
