<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\InsurancePolicyType;
use App\Enums\PayerType;
use App\Models\InsurancePolicyItem;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;

final class ResolveVisitChargeAmount
{
    public function handle(
        PatientVisit $visit,
        BillableItemType $billableType,
        string $billableId,
        ?float $fallbackAmount = null,
    ): ?float {
        $visit->loadMissing('payer');

        $payer = $visit->payer;

        if (
            $payer?->billing_type === PayerType::INSURANCE
            && $payer->insurance_package_id !== null
        ) {
            $policyType = InsurancePolicyType::fromBillableItemType($billableType);

            if (! $policyType instanceof InsurancePolicyType) {
                return $fallbackAmount === null ? null : round($fallbackAmount, 2);
            }

            $packagePrice = InsurancePolicyItem::query()
                ->where('tenant_id', $visit->tenant_id)
                ->where('item_type', $billableType->value)
                ->where('item_id', $billableId)
                ->where('status', GeneralStatus::ACTIVE->value)
                ->whereHas('policy', function (Builder $query) use ($payer, $policyType, $visit): void {
                    $today = now()->toDateString();

                    $query
                        ->where('facility_branch_id', $visit->facility_branch_id)
                        ->where('insurance_package_id', $payer->insurance_package_id)
                        ->where('policy_type', $policyType->value)
                        ->where('status', GeneralStatus::ACTIVE->value)
                        ->where(function (Builder $rangeQuery) use ($today): void {
                            $rangeQuery->whereNull('effective_from')
                                ->orWhere('effective_from', '<=', $today);
                        })
                        ->where(function (Builder $rangeQuery) use ($today): void {
                            $rangeQuery->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $today);
                        });
                })
                ->where(function (Builder $query): void {
                    $today = now()->toDateString();

                    $query->whereNull('effective_from')
                        ->orWhere('effective_from', '<=', $today);
                })
                ->where(function (Builder $query): void {
                    $today = now()->toDateString();

                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $today);
                })
                ->latest('effective_from')
                ->value('price');

            if ($packagePrice !== null) {
                return round($this->floatValue($packagePrice), 2);
            }
        }

        return $fallbackAmount === null ? null : round($fallbackAmount, 2);
    }

    private function floatValue(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }
}
