<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Rules\NoOverlappingInsurancePriceWindow;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class StoreInsurancePackagePriceRequest extends FormRequest
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
        $tenantId = (string) $this->user()?->tenant_id;
        $facilityBranchId = $this->string('facility_branch_id')->toString();
        $insurancePackageId = $this->string('insurance_package_id')->toString();
        $billableType = $this->string('billable_type')->toString();
        $billableId = $this->string('billable_id')->toString();
        $effectiveFrom = $this->string('effective_from')->toString();
        $effectiveTo = $this->filled('effective_to') ? $this->string('effective_to')->toString() : null;

        return [
            'facility_branch_id' => [
                'required',
                'uuid',
                Rule::exists('facility_branches', 'id')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                ),
            ],
            'insurance_package_id' => [
                'required',
                'uuid',
                Rule::exists('insurance_packages', 'id')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                ),
            ],
            'billable_type' => ['required', new Enum(BillableItemType::class)],
            'billable_id' => ['required', 'uuid'],
            'price' => [
                'required',
                'numeric',
                'min:0',
                new NoOverlappingInsurancePriceWindow(
                    tenantId: $tenantId,
                    facilityBranchId: $facilityBranchId,
                    insurancePackageId: $insurancePackageId,
                    billableType: $billableType,
                    billableId: $billableId,
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
            if (! $this->filled('effective_from')) {
                return;
            }

            if (! $this->filled('effective_to')) {
                return;
            }

            $from = strtotime($this->string('effective_from')->toString());
            $to = strtotime($this->string('effective_to')->toString());

            if ($from !== false && $to !== false && $to < $from) {
                $validator->errors()->add('effective_to', 'The effective to date must be after or equal to effective from date.');
            }
        });
    }
}


