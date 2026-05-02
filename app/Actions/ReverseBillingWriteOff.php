<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingWriteOffStatus;
use App\Models\BillingWriteOff;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReverseBillingWriteOff
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(BillingWriteOff $writeOff, string $reason): BillingWriteOff
    {
        return DB::transaction(function () use ($writeOff, $reason): BillingWriteOff {
            $writeOff = BillingWriteOff::query()
                ->with('billing')
                ->lockForUpdate()
                ->findOrFail($writeOff->id);

            if ($writeOff->status !== BillingWriteOffStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'write_off' => 'Only approved write-offs can be reversed.',
                ]);
            }

            $reason = mb_trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reversal_reason' => 'A reversal reason is required.',
                ]);
            }

            $userId = Auth::id();

            $writeOff->forceFill([
                'status' => BillingWriteOffStatus::REVERSED,
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_by' => $userId,
            ])->save();

            $billing = $writeOff->billing;

            if ($billing !== null) {
                $billing = $this->recalculateVisitBilling->handle($billing);
            }

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'write_off.reversed',
                subject: $writeOff,
                description: 'Billing write-off reversed.',
                tenantId: $writeOff->tenant_id,
                branchId: $writeOff->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $reason,
                oldValues: [
                    'status' => BillingWriteOffStatus::APPROVED->value,
                ],
                newValues: [
                    'write_off_id' => $writeOff->id,
                    'visit_billing_id' => $writeOff->visit_billing_id,
                    'patient_visit_id' => $writeOff->patient_visit_id,
                    'amount' => (float) $writeOff->amount,
                    'status' => BillingWriteOffStatus::REVERSED->value,
                    'billing_write_off_amount' => $billing instanceof VisitBilling ? (float) $billing->write_off_amount : null,
                    'billing_balance_amount' => $billing instanceof VisitBilling ? (float) $billing->balance_amount : null,
                ],
            );

            return $writeOff->refresh();
        });
    }
}
