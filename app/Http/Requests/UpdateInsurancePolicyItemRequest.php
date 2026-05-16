<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Models\InsurancePolicy;
use App\Models\InsurancePolicyItem;
use App\Rules\NoOverlappingInsurancePriceWindow;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateInsurancePolicyItemRequest extends FormRequest
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
        /** @var InsurancePolicyItem $item */
        $item = $this->route('insurance_policy_item');
        $effectiveFrom = $this->string('effective_from')->toString();
        $effectiveTo = $this->filled('effective_to') ? $this->string('effective_to')->toString() : null;

        return [
            'price' => [
                'required',
                'numeric',
                'min:0',
                new NoOverlappingInsurancePriceWindow(
                    tenantId: (string) $this->user()?->tenant_id,
                    insurancePolicyId: $policy->id,
                    chargeMasterId: $item->charge_master_id,
                    effectiveFrom: $effectiveFrom,
                    effectiveTo: $effectiveTo,
                    ignoreId: $item->id,
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

    /**
     * @return array{price: numeric-string, copay_type: string, copay_value: numeric-string, effective_from: string, effective_to?: string|null, status: string}
     */
    public function itemData(): array
    {
        return [
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
}
