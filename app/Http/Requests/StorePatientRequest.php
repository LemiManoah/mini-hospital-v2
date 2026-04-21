<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\KinRelationship;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Enums\VisitType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StorePatientRequest extends FormRequest
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
        $tenantId = (string) $this->user()?->tenant_id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'age_input_mode' => ['required', Rule::in(['dob', 'age'])],
            'date_of_birth' => ['nullable', 'required_if:age_input_mode,dob', 'date'],
            'age' => ['nullable', 'required_if:age_input_mode,age', 'integer', 'min:0', 'max:150'],
            'age_units' => ['nullable', 'required_if:age_input_mode,age', Rule::in(['year', 'month', 'day'])],
            'gender' => ['required', new Enum(Gender::class)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients', 'email')],
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
            'visit_type' => ['required', new Enum(VisitType::class)],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:staff,id'],
            'is_emergency' => ['nullable', 'boolean'],
            'billing_type' => ['required', Rule::in(['cash', 'insurance'])],
            'redirect_to' => ['nullable', Rule::in(['visit', 'list', 'show', 'patient'])],
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


