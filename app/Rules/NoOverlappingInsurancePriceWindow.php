<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\GeneralStatus;
use App\Models\InsurancePolicyItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class NoOverlappingInsurancePriceWindow implements ValidationRule
{
    public function __construct(
        private string $tenantId,
        private string $insurancePolicyId,
        private string $chargeMasterId,
        private string $effectiveFrom,
        private ?string $effectiveTo = null,
        private ?string $ignoreId = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = InsurancePolicyItem::query()
            ->where('tenant_id', $this->tenantId)
            ->where('insurance_policy_id', $this->insurancePolicyId)
            ->where('charge_master_id', $this->chargeMasterId)
            ->where('status', GeneralStatus::ACTIVE->value)
            ->when(
                $this->ignoreId !== null && $this->ignoreId !== '',
                fn (Builder $builder): Builder => $builder->where('id', '!=', $this->ignoreId)
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
            $fail('The selected effective date range overlaps an existing active policy price.');
        }
    }
}
