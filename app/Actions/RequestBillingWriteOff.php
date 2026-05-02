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

final readonly class RequestBillingWriteOff
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(VisitBilling $billing, float $amount, string $reason, ?string $notes = null): BillingWriteOff
    {
        return DB::transaction(function () use ($billing, $amount, $reason, $notes): BillingWriteOff {
            $billing = $this->recalculateVisitBilling->handle($billing);
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Write-off amount must be greater than zero.',
                ]);
            }

            if ($amount > (float) $billing->balance_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Write-off amount cannot be greater than the outstanding balance.',
                ]);
            }

            $reason = mb_trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'A write-off reason is required.',
                ]);
            }

            $userId = Auth::id();

            $writeOff = BillingWriteOff::query()->create([
                'tenant_id' => $billing->tenant_id,
                'facility_branch_id' => $billing->facility_branch_id,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $billing->patient_visit_id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => BillingWriteOffStatus::PENDING,
                'notes' => $notes,
                'requested_by' => $userId,
                'requested_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'write_off.requested',
                subject: $writeOff,
                description: 'Billing write-off requested.',
                tenantId: $billing->tenant_id,
                branchId: $billing->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $reason,
                newValues: [
                    'write_off_id' => $writeOff->id,
                    'visit_billing_id' => $billing->id,
                    'patient_visit_id' => $billing->patient_visit_id,
                    'amount' => $amount,
                    'status' => $writeOff->status->value,
                ],
            );

            return $writeOff->refresh();
        });
    }
}
