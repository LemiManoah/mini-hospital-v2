<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingDiscountStatus;
use App\Models\BillingDiscount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApproveBillingDiscount
{
    public function __construct(
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(BillingDiscount $discount): BillingDiscount
    {
        return DB::transaction(function () use ($discount): BillingDiscount {
            $discount = BillingDiscount::query()
                ->with('billing')
                ->lockForUpdate()
                ->findOrFail($discount->id);

            if ($discount->status !== BillingDiscountStatus::PENDING) {
                throw ValidationException::withMessages([
                    'discount' => 'Only pending discounts can be approved.',
                ]);
            }

            $billing = $discount->billing;

            if ($billing === null) {
                throw ValidationException::withMessages([
                    'discount' => 'The selected discount is missing its billing record.',
                ]);
            }

            $billing = $this->recalculateVisitBilling->handle($billing);

            if ((float) $discount->amount > (float) $billing->balance_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Discount amount cannot be greater than the outstanding balance.',
                ]);
            }

            $userId = Auth::id();

            $discount->forceFill([
                'status' => BillingDiscountStatus::APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'updated_by' => $userId,
            ])->save();

            $billing = $this->recalculateVisitBilling->handle($billing);
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'discount.approved',
                subject: $discount,
                description: 'Billing discount approved.',
                tenantId: $discount->tenant_id,
                branchId: $discount->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $discount->reason,
                newValues: [
                    'discount_id' => $discount->id,
                    'visit_billing_id' => $discount->visit_billing_id,
                    'patient_visit_id' => $discount->patient_visit_id,
                    'amount' => (float) $discount->amount,
                    'status' => BillingDiscountStatus::APPROVED->value,
                    'billing_discount_amount' => (float) $billing->discount_amount,
                    'billing_balance_amount' => (float) $billing->balance_amount,
                ],
            );

            return $discount->refresh();
        });
    }
}
