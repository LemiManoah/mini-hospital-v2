<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Enums\InsurancePolicyType;
use App\Models\ChargeMaster;
use App\Models\FacilityBranch;
use App\Models\InsurancePackage;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class StoreInsurancePolicyRequest extends FormRequest
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
        /** @var User|null $user */
        $user = $this->user();
        $activeBranch = $user instanceof User ? BranchContext::getActiveBranch($user) : null;
        $activeBranchId = $activeBranch instanceof FacilityBranch ? $activeBranch->id : '';

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurance_policies', 'name')->where(
                    static fn (Builder $query): Builder => $query
                        ->where('tenant_id', $tenantId)
                        ->where('facility_branch_id', $activeBranchId)
                        ->where('insurance_package_id', $insurancePackage->id)
                ),
            ],
            'policy_type' => ['required', new Enum(InsurancePolicyType::class)],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', new Enum(GeneralStatus::class)],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.charge_master_id' => ['required_with:items', 'uuid'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.copay_type' => ['required_with:items', new Enum(InsuranceCopayType::class)],
            'items.*.copay_value' => ['required_with:items', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.effective_from' => ['nullable', 'date'],
            'items.*.effective_to' => ['nullable', 'date'],
            'items.*.status' => ['required_with:items', new Enum(GeneralStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $policyType = $this->policyType();

            if (! $policyType instanceof InsurancePolicyType) {
                return;
            }

            $items = $this->input('items', []);

            if (! is_iterable($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                if (! is_int($index) && ! is_string($index)) {
                    continue;
                }

                if (! is_array($item)) {
                    continue;
                }

                if (! is_string($item['charge_master_id'] ?? null)) {
                    continue;
                }

                if (! $this->chargeMasterMatchesPolicy($policyType, $item['charge_master_id'])) {
                    $validator->errors()->add(sprintf('items.%s.charge_master_id', (string) $index), 'The selected charge master item does not match the selected policy type.');
                }

                if (($item['copay_type'] ?? null) === InsuranceCopayType::PERCENTAGE->value && is_numeric($item['copay_value'] ?? null) && (float) $item['copay_value'] > 100) {
                    $validator->errors()->add(sprintf('items.%s.copay_value', (string) $index), 'Percentage copay cannot be greater than 100.');
                }
            }
        });
    }

    /**
     * @return array{
     *     facility_branch_id: string,
     *     name: string,
     *     policy_type: string,
     *     effective_from?: string|null,
     *     effective_to?: string|null,
     *     status: string,
     *     items: list<array{charge_master_id: string, price: numeric-string, copay_type: string, copay_value: numeric-string, effective_from?: string|null, effective_to?: string|null, status: string}>
     * }
     */
    public function policyData(string $facilityBranchId): array
    {
        return [
            'facility_branch_id' => $facilityBranchId,
            'name' => $this->stringValue('name'),
            'policy_type' => $this->stringValue('policy_type'),
            'effective_from' => $this->nullableStringValue('effective_from'),
            'effective_to' => $this->nullableStringValue('effective_to'),
            'status' => $this->stringValue('status'),
            'items' => $this->itemsData(),
        ];
    }

    private function policyType(): ?InsurancePolicyType
    {
        $policyType = $this->input('policy_type');

        return is_string($policyType) ? InsurancePolicyType::tryFrom($policyType) : null;
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

    /**
     * @return list<array{charge_master_id: string, price: numeric-string, copay_type: string, copay_value: numeric-string, effective_from?: string|null, effective_to?: string|null, status: string}>
     */
    private function itemsData(): array
    {
        $items = $this->validated('items', []);

        if (! is_array($items)) {
            return [];
        }

        $itemsData = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemsData[] = [
                'charge_master_id' => is_string($item['charge_master_id'] ?? null) ? $item['charge_master_id'] : '',
                'price' => $this->numericStringValue($item['price'] ?? null),
                'copay_type' => is_string($item['copay_type'] ?? null) ? $item['copay_type'] : InsuranceCopayType::NONE->value,
                'copay_value' => $this->numericStringValue($item['copay_value'] ?? null),
                'effective_from' => is_string($item['effective_from'] ?? null) && $item['effective_from'] !== '' ? $item['effective_from'] : null,
                'effective_to' => is_string($item['effective_to'] ?? null) && $item['effective_to'] !== '' ? $item['effective_to'] : null,
                'status' => is_string($item['status'] ?? null) ? $item['status'] : GeneralStatus::ACTIVE->value,
            ];
        }

        return $itemsData;
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

    /**
     * @return numeric-string
     */
    private function numericStringValue(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return $value;
        }

        return '0';
    }
}
