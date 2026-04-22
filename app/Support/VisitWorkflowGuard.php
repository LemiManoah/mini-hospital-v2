<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BillingStatus;
use App\Enums\PayerType;
use App\Models\PatientVisit;
use App\Support\GeneralSettings\TenantGeneralSettings;

final readonly class VisitWorkflowGuard
{
    public function __construct(
        private TenantGeneralSettings $tenantGeneralSettings,
    ) {}

    public function paymentBlockMessage(PatientVisit $visit, string $context): ?string
    {
        $tenantId = $visit->tenant_id;

        if (! is_string($tenantId) || $tenantId === '') {
            return null;
        }

        $settingField = match ($context) {
            'consultation' => 'require_payment_before_consultation',
            'laboratory' => 'require_payment_before_laboratory',
            'pharmacy' => 'require_payment_before_pharmacy',
            'procedures' => 'require_payment_before_procedures',
            default => null,
        };

        if ($settingField === null || ! $this->tenantGeneralSettings->boolean($tenantId, $settingField)) {
            return null;
        }

        $visit->loadMissing(['billing', 'payer']);

        $billing = $visit->billing;

        if ($billing === null) {
            return null;
        }

        if ((float) ($billing->gross_amount ?? 0) <= 0 || (float) ($billing->balance_amount ?? 0) <= 0) {
            return null;
        }

        $status = $billing->status;

        if ($status !== null && $status->isSettled()) {
            return null;
        }

        $allowInsuranceBypass = $this->tenantGeneralSettings->boolean(
            $tenantId,
            'allow_insured_bypass_upfront_payment',
        );

        if (
            $allowInsuranceBypass
            && $billing->payer_type === PayerType::INSURANCE
            && $billing->status === BillingStatus::INSURANCE_PENDING
        ) {
            return null;
        }

        return match ($context) {
            'consultation' => 'Consultation cannot continue until the visit is paid or allowed insurance cover is in place.',
            'laboratory' => 'Laboratory orders are blocked until the visit is paid or allowed insurance cover is in place.',
            'pharmacy' => 'Prescriptions are blocked until the visit is paid or allowed insurance cover is in place.',
            'procedures' => 'Facility service orders are blocked until the visit is paid or allowed insurance cover is in place.',
            default => 'This workflow is blocked until the visit is paid or allowed insurance cover is in place.',
        };
    }

    /**
     * @return array{
     *     require_review_before_release: bool,
     *     require_approval_before_release: bool
     * }
     */
    public function labReleasePolicy(string $tenantId): array
    {
        return [
            'require_review_before_release' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'require_review_before_lab_release',
            ),
            'require_approval_before_release' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'require_approval_before_lab_release',
            ),
        ];
    }
}
