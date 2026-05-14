<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Enums\InsurancePolicyType;
use App\Enums\PayerType;
use App\Models\ChargeMaster;
use App\Models\InsurancePolicyItem;
use App\Models\PatientVisit;
use App\ValueObjects\VisitChargePricing;
use Illuminate\Database\Eloquent\Builder;

final class ResolveVisitChargeAmount
{
    public function handle(
        PatientVisit $visit,
        BillableItemType $billableType,
        string $billableId,
        ?float $fallbackAmount = null,
    ): ?float {
        return $this->resolve($visit, $billableType, $billableId, $fallbackAmount)?->unitPrice;
    }

    public function resolveChargeMaster(
        PatientVisit $visit,
        ChargeMaster $chargeMaster,
        float $quantity = 1.0,
    ): ?VisitChargePricing {
        if (! $this->chargeMasterIsUsable($chargeMaster)) {
            return null;
        }

        if (! $chargeMaster->billable_type instanceof BillableItemType || $chargeMaster->billable_id === null) {
            return new VisitChargePricing(round((float) $chargeMaster->unit_price, 2));
        }

        return $this->resolve(
            $visit,
            $chargeMaster->billable_type,
            $chargeMaster->billable_id,
            (float) $chargeMaster->unit_price,
            $quantity,
        );
    }

    public function resolve(
        PatientVisit $visit,
        BillableItemType $billableType,
        string $billableId,
        ?float $fallbackAmount = null,
        float $quantity = 1.0,
    ): ?VisitChargePricing {
        $visit->loadMissing('payer');

        $payer = $visit->payer;

        if (
            $payer?->billing_type === PayerType::INSURANCE
            && $payer->insurance_package_id !== null
        ) {
            $policyType = InsurancePolicyType::fromBillableItemType($billableType);

            if (! $policyType instanceof InsurancePolicyType) {
                return $fallbackAmount === null ? null : new VisitChargePricing(round($fallbackAmount, 2));
            }

            $policyItem = InsurancePolicyItem::query()
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
                ->first(['id', 'price', 'copay_type', 'copay_value']);

            if ($policyItem instanceof InsurancePolicyItem) {
                $unitPrice = round($this->floatValue($policyItem->price), 2);

                return new VisitChargePricing(
                    unitPrice: $unitPrice,
                    copayAmount: $this->copayAmount($policyItem, $unitPrice, $quantity),
                    insurancePolicyItemId: $policyItem->id,
                );
            }
        }

        return $fallbackAmount === null ? null : new VisitChargePricing(round($fallbackAmount, 2));
    }

    private function copayAmount(InsurancePolicyItem $policyItem, float $unitPrice, float $quantity): float
    {
        $lineTotal = round($unitPrice * $quantity, 2);
        $copayValue = max(0.0, $this->floatValue($policyItem->copay_value));

        $amount = match ($policyItem->copay_type) {
            InsuranceCopayType::FIXED => $copayValue,
            InsuranceCopayType::PERCENTAGE => round($lineTotal * min($copayValue, 100.0) / 100, 2),
            default => 0.0,
        };

        return min($lineTotal, round($amount, 2));
    }

    private function chargeMasterIsUsable(ChargeMaster $chargeMaster): bool
    {
        if (! $chargeMaster->is_active) {
            return false;
        }

        $today = now()->toDateString();

        if ($chargeMaster->effective_from !== null && $chargeMaster->effective_from->toDateString() > $today) {
            return false;
        }

        if ($chargeMaster->effective_to !== null && $chargeMaster->effective_to->toDateString() < $today) {
            return false;
        }

        return true;
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
