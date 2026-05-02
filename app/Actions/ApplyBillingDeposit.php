<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingDepositStatus;
use App\Enums\BillingDocumentType;
use App\Models\BillingDeposit;
use App\Models\Payment;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApplyBillingDeposit
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private GenerateBillingDocumentNumber $documentNumber,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(BillingDeposit $deposit, VisitBilling $billing, float $amount, ?string $notes = null): BillingDeposit
    {
        return DB::transaction(function () use ($deposit, $billing, $amount, $notes): BillingDeposit {
            $deposit = BillingDeposit::query()->lockForUpdate()->findOrFail($deposit->id);
            $billing = VisitBilling::query()->lockForUpdate()->findOrFail($billing->id);

            if ($deposit->tenant_id !== $billing->tenant_id || $deposit->facility_branch_id !== $billing->facility_branch_id) {
                throw ValidationException::withMessages([
                    'deposit' => 'The selected deposit does not belong to the same billing workspace.',
                ]);
            }

            $amount = round($amount, 2);
            $availableAmount = round((float) $deposit->amount - (float) $deposit->applied_amount - (float) $deposit->refunded_amount, 2);
            $billing = $this->recalculateVisitBilling->handle($billing);

            if ($amount <= 0 || $amount > $availableAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Deposit application amount must be greater than zero and no more than the held deposit balance.',
                ]);
            }

            if ($amount > (float) $billing->balance_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Deposit application amount cannot exceed the visit billing balance.',
                ]);
            }

            $tenantId = (string) $deposit->tenant_id;
            $userId = Auth::id();

            Payment::query()->create([
                'tenant_id' => $tenantId,
                'facility_branch_id' => $billing->facility_branch_id,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $billing->patient_visit_id,
                'receipt_number' => $this->documentNumber->handle(BillingDocumentType::PatientReceipt, $tenantId, $billing->facility_branch_id),
                'payment_date' => now(),
                'amount' => $amount,
                'payment_method_id' => null,
                'payment_method' => 'deposit',
                'reference_number' => $deposit->deposit_number,
                'notes' => $notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $newAppliedAmount = round((float) $deposit->applied_amount + $amount, 2);
            $newAvailableAmount = round((float) $deposit->amount - $newAppliedAmount - (float) $deposit->refunded_amount, 2);

            $deposit->forceFill([
                'patient_visit_id' => $deposit->patient_visit_id ?? $billing->patient_visit_id,
                'visit_billing_id' => $billing->id,
                'applied_amount' => $newAppliedAmount,
                'status' => $newAvailableAmount <= 0 ? BillingDepositStatus::Applied : BillingDepositStatus::PartiallyApplied,
                'applied_at' => now(),
                'updated_by' => $userId,
            ])->save();

            $billing = $this->recalculateVisitBilling->handle($billing);
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'deposit.applied',
                subject: $deposit,
                description: 'Billing deposit applied to visit bill.',
                tenantId: $tenantId,
                branchId: $billing->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'deposit_id' => $deposit->id,
                    'deposit_number' => $deposit->deposit_number,
                    'visit_billing_id' => $billing->id,
                    'patient_visit_id' => $billing->patient_visit_id,
                    'applied_amount' => $amount,
                    'deposit_status' => $deposit->status?->value,
                    'billing_balance_amount' => (float) $billing->balance_amount,
                ],
            );

            return $deposit->refresh();
        });
    }
}
