<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingDiscountStatus;
use App\Models\BillingDiscount;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RequestBillingDiscount
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(VisitBilling $billing, float $amount, string $reason, ?string $notes = null): BillingDiscount
    {
        return DB::transaction(function () use ($billing, $amount, $reason, $notes): BillingDiscount {
            $billing = $this->recalculateVisitBilling->handle($billing);
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Discount amount must be greater than zero.',
                ]);
            }

            if ($amount > (float) $billing->balance_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Discount amount cannot be greater than the outstanding balance.',
                ]);
            }

            $reason = mb_trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'A discount reason is required.',
                ]);
            }

            $userId = Auth::id();

            $discount = BillingDiscount::query()->create([
                'tenant_id' => $billing->tenant_id,
                'facility_branch_id' => $billing->facility_branch_id,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $billing->patient_visit_id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => BillingDiscountStatus::PENDING,
                'notes' => $notes,
                'requested_by' => $userId,
                'requested_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'discount.requested',
                subject: $discount,
                description: 'Billing discount requested.',
                tenantId: $billing->tenant_id,
                branchId: $billing->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $reason,
                newValues: [
                    'discount_id' => $discount->id,
                    'visit_billing_id' => $billing->id,
                    'patient_visit_id' => $billing->patient_visit_id,
                    'amount' => $amount,
                    'status' => $discount->status->value,
                ],
            );

            return $discount->refresh();
        });
    }
}
