<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Enums\InsurancePolicyType;
use App\Enums\InventoryItemType;
use App\Models\FacilityService;
use App\Models\InsurancePolicy;
use App\Models\InventoryItem;
use App\Models\LabTestCatalog;
use App\Rules\NoOverlappingInsurancePriceWindow;
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
        $itemType = $policy->policy_type->itemType()->value;
        $itemId = $this->string('item_id')->toString();
        $effectiveFrom = $this->string('effective_from')->toString();
        $effectiveTo = $this->filled('effective_to') ? $this->string('effective_to')->toString() : null;

        return [
            'item_id' => ['required', 'uuid'],
            'price' => [
                'required',
                'numeric',
                'min:0',
                new NoOverlappingInsurancePriceWindow(
                    tenantId: (string) $this->user()?->tenant_id,
                    insurancePolicyId: $policy->id,
                    itemType: $itemType,
                    itemId: $itemId,
                    effectiveFrom: $effectiveFrom,
                    effectiveTo: $effectiveTo,
                ),
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
            $itemId = $this->string('item_id')->toString();

            if ($itemId !== '' && ! $this->itemExists($policy->policy_type, $itemId)) {
                $validator->errors()->add('item_id', 'The selected item does not match this policy type.');
            }
        });
    }

    /**
     * @return array{item_id: string, price: numeric-string, effective_from: string, effective_to?: string|null, status: string}
     */
    public function itemData(): array
    {
        return [
            'item_id' => $this->stringValue('item_id'),
            'price' => $this->numericStringValue('price'),
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

    private function itemExists(InsurancePolicyType $policyType, string $itemId): bool
    {
        $tenantId = (string) $this->user()?->tenant_id;

        return match ($policyType) {
            InsurancePolicyType::PHARMACY => InventoryItem::query()
                ->where('tenant_id', $tenantId)
                ->where('item_type', InventoryItemType::DRUG->value)
                ->where('is_active', true)
                ->whereKey($itemId)
                ->exists(),
            InsurancePolicyType::LAB => LabTestCatalog::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereKey($itemId)
                ->exists(),
            InsurancePolicyType::SERVICES => FacilityService::query()
                ->where('tenant_id', $tenantId)
                ->where('is_billable', true)
                ->where('is_active', true)
                ->whereKey($itemId)
                ->exists(),
        };
    }
}
