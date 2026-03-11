<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\GeneralStatus;
use App\Models\InsurancePackagePrice;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class NoOverlappingInsurancePriceWindow implements ValidationRule
{
    public function __construct(
        private string $tenantId,
        private string $facilityBranchId,
        private string $insurancePackageId,
        private string $billableType,
        private string $billableId,
        private string $effectiveFrom,
        private ?string $effectiveTo = null,
        private ?string $ignoreId = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = InsurancePackagePrice::query()
            ->where('tenant_id', $this->tenantId)
            ->where('facility_branch_id', $this->facilityBranchId)
            ->where('insurance_package_id', $this->insurancePackageId)
            ->where('billable_type', $this->billableType)
            ->where('billable_id', $this->billableId)
            ->where('status', GeneralStatus::ACTIVE->value)
            ->when(
                $this->ignoreId !== null && $this->ignoreId !== '',
                static fn (Builder $builder) => $builder->where('id', '!=', $this->ignoreId)
            )
            ->where(function (Builder $builder): void {
                $builder
                    ->where(function (Builder $rangeQuery): void {
                        $rangeQuery
                            ->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $this->effectiveFrom);
                    })
                    ->where(function (Builder $rangeQuery): void {
                        if ($this->effectiveTo === null || $this->effectiveTo === '') {
                            $rangeQuery->whereRaw('1 = 1');

                            return;
                        }

                        $rangeQuery
                            ->whereNull('effective_from')
                            ->orWhere('effective_from', '<=', $this->effectiveTo);
                    });
            });

        if ($query->exists()) {
            $fail('The selected effective date range overlaps an existing active insurance package price.');
        }
    }
}
