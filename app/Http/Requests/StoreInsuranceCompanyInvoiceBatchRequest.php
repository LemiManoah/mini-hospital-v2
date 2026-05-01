<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInsuranceCompanyInvoiceBatchRequest extends FormRequest
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
            'insurance_company_id' => ['required', 'uuid', 'exists:insurance_companies,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function insuranceCompanyId(): string
    {
        return (string) $this->validated('insurance_company_id');
    }

    public function startDate(): ?string
    {
        $value = $this->validated('start_date');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function endDate(): ?string
    {
        $value = $this->validated('end_date');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
