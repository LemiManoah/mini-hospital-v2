<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingWriteOffStatus;
use App\Models\BillingWriteOff;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApproveBillingWriteOff
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(BillingWriteOff $writeOff): BillingWriteOff
    {
        return DB::transaction(function () use ($writeOff): BillingWriteOff {
            $writeOff = BillingWriteOff::query()
                ->with('billing')
                ->lockForUpdate()
                ->findOrFail($writeOff->id);

            if ($writeOff->status !== BillingWriteOffStatus::PENDING) {
                throw ValidationException::withMessages([
                    'write_off' => 'Only pending write-offs can be approved.',
                ]);
            }

            $billing = $writeOff->billing;

            if ($billing === null) {
                throw ValidationException::withMessages([
                    'write_off' => 'The selected write-off is missing its billing record.',
                ]);
            }

            $billing = $this->recalculateVisitBilling->handle($billing);

            if ((float) $writeOff->amount > (float) $billing->balance_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Write-off amount cannot be greater than the outstanding balance.',
                ]);
            }

            $userId = Auth::id();

            $writeOff->forceFill([
                'status' => BillingWriteOffStatus::APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'updated_by' => $userId,
            ])->save();

            $billing = $this->recalculateVisitBilling->handle($billing);
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'write_off.approved',
                subject: $writeOff,
                description: 'Billing write-off approved.',
                tenantId: $writeOff->tenant_id,
                branchId: $writeOff->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $writeOff->reason,
                newValues: [
                    'write_off_id' => $writeOff->id,
                    'visit_billing_id' => $writeOff->visit_billing_id,
                    'patient_visit_id' => $writeOff->patient_visit_id,
                    'amount' => (float) $writeOff->amount,
                    'status' => BillingWriteOffStatus::APPROVED->value,
                    'billing_write_off_amount' => (float) $billing->write_off_amount,
                    'billing_balance_amount' => (float) $billing->balance_amount,
                ],
            );

            return $writeOff->refresh();
        });
    }
}
