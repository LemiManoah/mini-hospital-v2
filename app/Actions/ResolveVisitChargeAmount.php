<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\PayerType;
use App\Models\InsurancePackagePrice;
use App\Models\PatientVisit;

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
            $packagePrice = InsurancePackagePrice::query()
                ->where('tenant_id', $visit->tenant_id)
                ->where('facility_branch_id', $visit->facility_branch_id)
                ->where('insurance_package_id', $payer->insurance_package_id)
                ->where('billable_type', $billableType->value)
                ->where('billable_id', $billableId)
                ->where('status', GeneralStatus::ACTIVE->value)
                ->where(function ($query): void {
                    $today = now()->toDateString();

                    $query->whereNull('effective_from')
                        ->orWhere('effective_from', '<=', $today);
                })
                ->where(function ($query): void {
                    $today = now()->toDateString();

                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $today);
                })
                ->latest('effective_from')
                ->value('price');

            if ($packagePrice !== null) {
                return round((float) $packagePrice, 2);
            }
        }

        return $fallbackAmount === null ? null : round($fallbackAmount, 2);
    }
}
