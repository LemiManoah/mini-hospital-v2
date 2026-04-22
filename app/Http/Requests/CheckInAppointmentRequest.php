<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PayerType;
use App\Enums\VisitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckInAppointmentRequest extends FormRequest
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
            'billing_type' => ['required', Rule::enum(PayerType::class)],
            'insurance_company_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_companies,id'],
            'insurance_package_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_packages,id'],
            'is_emergency' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_emergency' => $this->boolean('is_emergency'),
            'insurance_company_id' => $this->filled('insurance_company_id') ? $this->input('insurance_company_id') : null,
            'insurance_package_id' => $this->filled('insurance_package_id') ? $this->input('insurance_package_id') : null,
        ]);
    }
}
