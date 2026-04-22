<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Models\InsurancePackage;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateInsurancePackageRequest extends FormRequest
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
        /** @var InsurancePackage $insurancePackage */
        $insurancePackage = $this->route('insurance_package');
        $tenantId = (string) $this->user()?->tenant_id;

        return [
            'insurance_company_id' => [
                'required',
                'uuid',
                Rule::exists('insurance_companies', 'id')->where(
                    function (Builder $query) use ($tenantId): void {
                        $query->where('tenant_id', $tenantId);
                    }
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurance_packages', 'name')
                    ->where(function (Builder $query) use ($tenantId): void {
                        $query->where('tenant_id', $tenantId)
                            ->where('insurance_company_id', (string) $this->input('insurance_company_id'));
                    })
                    ->ignore($insurancePackage->id),
            ],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }
}
