<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Enums\InsurancePolicyType;
use App\Models\ChargeMaster;
use App\Models\InsurancePolicy;
use App\Rules\NoOverlappingInsurancePriceWindow;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class StoreInsurancePolicyItemRequest extends FormRequest
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
        $chargeMasterId = $this->string('charge_master_id')->toString();
        $effectiveFrom = $this->string('effective_from')->toString();
        $effectiveTo = $this->filled('effective_to') ? $this->string('effective_to')->toString() : null;

        return [
            'charge_master_id' => ['required', 'uuid'],
            'price' => [
                'required',
                'numeric',
                'min:0',
                new NoOverlappingInsurancePriceWindow(
                    tenantId: (string) $this->user()?->tenant_id,
                    insurancePolicyId: $policy->id,
                    chargeMasterId: $chargeMasterId,
                    effectiveFrom: $effectiveFrom,
                    effectiveTo: $effectiveTo,
                ),
            ],
            'copay_type' => ['required', new Enum(InsuranceCopayType::class)],
            'copay_value' => [
                'required',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->string('copay_type')->toString() === InsuranceCopayType::PERCENTAGE->value && is_numeric($value) && (float) $value > 100) {
                        $fail('Percentage copay cannot be greater than 100.');
                    }
                },
            ],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var InsurancePolicy $policy */
            $policy = $this->route('insurance_policy');
            $chargeMasterId = $this->string('charge_master_id')->toString();

            if ($chargeMasterId !== '' && ! $this->chargeMasterMatchesPolicy($policy->policy_type, $chargeMasterId)) {
                $validator->errors()->add('charge_master_id', 'The selected charge master item does not match this policy type.');
            }
        });
    }

    /**
     * @return array{charge_master_id: string, price: numeric-string, copay_type: string, copay_value: numeric-string, effective_from: string, effective_to?: string|null, status: string}
     */
    public function itemData(): array
    {
        return [
            'charge_master_id' => $this->stringValue('charge_master_id'),
            'price' => $this->numericStringValue('price'),
            'copay_type' => $this->stringValue('copay_type'),
            'copay_value' => $this->numericStringValue('copay_value'),
            'effective_from' => $this->stringValue('effective_from'),
            'effective_to' => $this->nullableStringValue('effective_to'),
            'status' => $this->stringValue('status'),
        ];
    }

    private function stringValue(string $key): string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : '';
    }

    /**
     * @return numeric-string
     */
    private function numericStringValue(string $key): string
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (string) $value : '0';
    }

    private function nullableStringValue(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function chargeMasterMatchesPolicy(InsurancePolicyType $policyType, string $chargeMasterId): bool
    {
        $tenantId = (string) $this->user()?->tenant_id;

        return ChargeMaster::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($chargeMasterId)
            ->where('is_active', true)
            ->where('billable_type', $policyType->itemType()->value)
            ->effectiveOn(now()->toDateString())
            ->exists();
    }
}
