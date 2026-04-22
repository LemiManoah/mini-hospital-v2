<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingStatus;
use App\Models\PatientVisit;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use RuntimeException;

final class EnsureVisitBilling
{
    public function handle(PatientVisit $visit): VisitBilling
    {
        $visit->loadMissing(['payer', 'billing']);

        if ($visit->billing !== null) {
            return $visit->billing;
        }

        /** @var VisitPayer $payer */
        $payer = $visit->payer ?? throw new RuntimeException('Visit payer is required before billing can be created.');
        $billingType = $payer->billing_type ?? throw new RuntimeException('Visit payer billing type is required before billing can be created.');

        return VisitBilling::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'patient_visit_id' => $visit->id,
            'visit_payer_id' => $payer->id,
            'payer_type' => $billingType,
            'insurance_company_id' => $payer->insurance_company_id,
            'insurance_package_id' => $payer->insurance_package_id,
            'status' => $billingType->value === 'insurance'
                ? BillingStatus::INSURANCE_PENDING
                : BillingStatus::PENDING,
        ]);
    }
}
