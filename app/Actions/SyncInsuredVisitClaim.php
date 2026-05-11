<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InsuredVisitClaimStatus;
use App\Enums\PayerType;
use App\Enums\VisitChargeStatus;
use App\Models\InsuredVisitClaim;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class SyncInsuredVisitClaim
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(VisitBilling $billing): ?InsuredVisitClaim
    {
        $billing->refresh();

        if ($billing->payer_type !== PayerType::INSURANCE) {
            return null;
        }

        if ((float) $billing->gross_amount <= 0) {
            return null;
        }

        if ($billing->insurance_company_id === null) {
            throw ValidationException::withMessages([
                'insurance_company_id' => 'An insured billing record must have an insurance company before a claim can be created.',
            ]);
        }

        $copayAmount = min(
            max(0.0, round((float) $billing->charges()->where('status', VisitChargeStatus::ACTIVE->value)->sum('copay_amount'), 2)),
            max(0.0, round((float) $billing->gross_amount - (float) $billing->discount_amount, 2)),
        );
        $claimAmount = max(0.0, round((float) $billing->gross_amount - (float) $billing->discount_amount, 2));
        $targetStatus = $claimAmount > 0
            ? InsuredVisitClaimStatus::READY_FOR_INVOICE
            : InsuredVisitClaimStatus::OPEN;
        $userId = Auth::id();

        $claim = InsuredVisitClaim::query()
            ->where('visit_billing_id', $billing->id)
            ->first();

        if ($claim instanceof InsuredVisitClaim && ! $claim->status?->canSyncClaimAmount()) {
            return $claim;
        }

        $oldValues = $claim instanceof InsuredVisitClaim
            ? [
                'claimed_amount' => (float) $claim->claimed_amount,
                'status' => $claim->status?->value,
            ]
            : [];

        if (
            $claim instanceof InsuredVisitClaim
            && round((float) $claim->claimed_amount, 2) === $claimAmount
            && round((float) $claim->copay_amount, 2) === $copayAmount
            && $claim->status === $targetStatus
            && $claim->insurance_company_id === $billing->insurance_company_id
            && $claim->insurance_package_id === $billing->insurance_package_id
        ) {
            return $claim;
        }

        $claim = InsuredVisitClaim::query()->updateOrCreate(
            ['visit_billing_id' => $billing->id],
            [
                'tenant_id' => $billing->tenant_id,
                'facility_branch_id' => $billing->facility_branch_id,
                'patient_visit_id' => $billing->patient_visit_id,
                'insurance_company_id' => $billing->insurance_company_id,
                'insurance_package_id' => $billing->insurance_package_id,
                'claim_reference' => $claim instanceof InsuredVisitClaim ? $claim->claim_reference : $this->generateClaimReference(),
                'claimed_amount' => $claimAmount,
                'approved_amount' => $claim instanceof InsuredVisitClaim ? $claim->approved_amount : 0,
                'rejected_amount' => 0,
                'copay_amount' => $copayAmount,
                'status' => $targetStatus,
                'updated_by' => $userId,
                'created_by' => $claim instanceof InsuredVisitClaim ? $claim->created_by : $userId,
            ],
        );

        $user = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'billing',
            event: empty($oldValues) ? 'insurance_claim.created' : 'insurance_claim.synced',
            subject: $claim,
            description: empty($oldValues) ? 'Insurance visit claim created.' : 'Insurance visit claim synced.',
            tenantId: $billing->tenant_id,
            branchId: $billing->facility_branch_id,
            staffId: $user instanceof User ? $user->staffId() : null,
            oldValues: $oldValues,
            newValues: [
                'claim_id' => $claim->id,
                'claim_reference' => $claim->claim_reference,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $billing->patient_visit_id,
                'insurance_company_id' => $billing->insurance_company_id,
                'insurance_package_id' => $billing->insurance_package_id,
                'claimed_amount' => $claimAmount,
                'copay_amount' => $copayAmount,
                'status' => $claim->status?->value,
            ],
        );

        return $claim->refresh();
    }

    private function generateClaimReference(): string
    {
        return 'CLM-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
