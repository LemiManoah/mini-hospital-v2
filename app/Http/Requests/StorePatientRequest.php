<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePatientRequest extends FormRequest
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
        $tenantId = (string) $this->user()?->tenant_id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'age_input_mode' => ['required', Rule::in(['dob', 'age'])],
            'date_of_birth' => ['nullable', 'required_if:age_input_mode,dob', 'date'],
            'age' => ['nullable', 'required_if:age_input_mode,age', 'integer', 'min:0', 'max:150'],
            'age_units' => ['nullable', 'required_if:age_input_mode,age', Rule::in(['year', 'month', 'day'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'unknown'])],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients', 'email')],
            'phone_number' => ['required', 'string', 'max:20'],
            'alternative_phone' => ['nullable', 'string', 'max:20'],
            'next_of_kin_name' => ['nullable', 'string', 'max:100'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20'],
            'next_of_kin_relationship' => ['nullable', 'string', 'max:50'],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'visit_type' => ['required', 'string'],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:staff,id'],
            'is_emergency' => ['nullable', 'boolean'],
            'billing_type' => ['required', Rule::in(['cash', 'insurance'])],
            'insurance_company_id' => [
                'nullable',
                'required_if:billing_type,insurance',
                'uuid',
                Rule::exists('insurance_companies', 'id')->where(
                    function (Builder $query) use ($tenantId): void {
                        $query->where('tenant_id', $tenantId);
                    }
                ),
            ],
            'insurance_package_id' => [
                'nullable',
                'required_if:billing_type,insurance',
                'uuid',
                Rule::exists('insurance_packages', 'id')->where(
                    function (Builder $query) use ($tenantId): void {
                        $query->where('tenant_id', $tenantId)
                            ->where('insurance_company_id', (string) $this->input('insurance_company_id'));
                    }
                ),
            ],
        ];
    }
}
