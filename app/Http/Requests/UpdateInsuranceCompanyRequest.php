<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Models\InsuranceCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateInsuranceCompanyRequest extends FormRequest
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
        /** @var InsuranceCompany $insuranceCompany */
        $insuranceCompany = $this->route('insurance_company');
        $tenantId = (string) $this->user()?->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurance_companies', 'name')
                    ->where(function (Builder $query) use ($tenantId): void {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->ignore($insuranceCompany->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'main_contact' => ['nullable', 'string', 'max:20'],
            'other_contact' => ['nullable', 'string', 'max:20'],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }
}


