<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PayerType;
use App\Enums\VisitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePatientVisitRequest extends FormRequest
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
            'visit_type' => ['required', Rule::enum(VisitType::class)],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:staff,id'],
            'is_emergency' => ['nullable', 'boolean'],
            'billing_type' => ['required', Rule::enum(PayerType::class)],
            'insurance_company_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_companies,id'],
            'insurance_package_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_packages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_type.required' => 'Please select a visit type.',
            'visit_type.enum' => 'Invalid visit type selected.',
            'clinic_id.uuid' => 'Clinic ID must be a valid UUID.',
            'clinic_id.exists' => 'Selected clinic does not exist.',
            'doctor_id.uuid' => 'Doctor ID must be a valid UUID.',
            'doctor_id.exists' => 'Selected doctor does not exist.',
            'is_emergency.boolean' => 'Is Emergency must be true or false.',
            'billing_type.required' => 'Please select a billing type.',
            'billing_type.enum' => 'Invalid billing type selected.',
            'insurance_company_id.required_if' => 'Insurance company is required when billing type is insurance.',
            'insurance_company_id.uuid' => 'Insurance company ID must be a valid UUID.',
            'insurance_company_id.exists' => 'Selected insurance company does not exist.',
            'insurance_package_id.required_if' => 'Insurance package is required when billing type is insurance.',
            'insurance_package_id.uuid' => 'Insurance package ID must be a valid UUID.',
            'insurance_package_id.exists' => 'Selected insurance package does not exist.',
        ];
    }
}


