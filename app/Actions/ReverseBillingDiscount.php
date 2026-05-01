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

final readonly class ReverseBillingDiscount
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(BillingDiscount $discount, string $reason): BillingDiscount
    {
        return DB::transaction(function () use ($discount, $reason): BillingDiscount {
            $discount = BillingDiscount::query()
                ->with('billing')
                ->lockForUpdate()
                ->findOrFail($discount->id);

            if ($discount->status !== BillingDiscountStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'discount' => 'Only approved discounts can be reversed.',
                ]);
            }

            $reason = mb_trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reversal_reason' => 'A reversal reason is required.',
                ]);
            }

            $userId = Auth::id();

            $discount->forceFill([
                'status' => BillingDiscountStatus::REVERSED,
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_by' => $userId,
            ])->save();

            $billing = $discount->billing;

            if ($billing !== null) {
                $billing = $this->recalculateVisitBilling->handle($billing);
            }

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'discount.reversed',
                subject: $discount,
                description: 'Billing discount reversed.',
                tenantId: $discount->tenant_id,
                branchId: $discount->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $reason,
                oldValues: [
                    'status' => BillingDiscountStatus::APPROVED->value,
                ],
                newValues: [
                    'discount_id' => $discount->id,
                    'visit_billing_id' => $discount->visit_billing_id,
                    'patient_visit_id' => $discount->patient_visit_id,
                    'amount' => (float) $discount->amount,
                    'status' => BillingDiscountStatus::REVERSED->value,
                    'billing_discount_amount' => $billing instanceof VisitBilling ? (float) $billing->discount_amount : null,
                    'billing_balance_amount' => $billing instanceof VisitBilling ? (float) $billing->balance_amount : null,
                ],
            );

            return $discount->refresh();
        });
    }
}
