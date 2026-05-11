<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Models\InsurancePolicy;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateInsurancePolicyRequest extends FormRequest
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
        /** @var InsurancePolicy $policy */
        $policy = $this->route('insurance_policy');
        $tenantId = (string) $this->user()?->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurance_policies', 'name')
                    ->where(static fn (Builder $query): Builder => $query
                        ->where('tenant_id', $tenantId)
                        ->where('facility_branch_id', $policy->facility_branch_id)
                        ->where('insurance_package_id', $policy->insurance_package_id))
                    ->ignore($policy->id),
            ],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }

    /**
     * @return array{name: string, effective_from?: string|null, effective_to?: string|null, status: string}
     */
    public function policyData(): array
    {
        return [
            'name' => $this->stringValue('name'),
            'effective_from' => $this->nullableStringValue('effective_from'),
            'effective_to' => $this->nullableStringValue('effective_to'),
            'status' => $this->stringValue('status'),
        ];
    }

    private function stringValue(string $key): string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : '';
    }

    private function nullableStringValue(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
