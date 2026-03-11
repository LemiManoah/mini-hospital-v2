<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreInsurancePackageRequest extends FormRequest
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
            'insurance_company_id' => [
                'required',
                'uuid',
                Rule::exists('insurance_companies', 'id')->where(
                    static fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurance_packages', 'name')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('insurance_company_id', (string) $this->input('insurance_company_id'))
                ),
            ],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }
}
