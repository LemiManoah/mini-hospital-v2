<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingStatus;
use App\Enums\PayerType;
use App\Enums\VisitChargeStatus;
use App\Models\VisitBilling;

final class RecalculateVisitBilling
{
    public function handle(VisitBilling $billing): VisitBilling
    {
        $grossAmount = (float) $billing->charges()
            ->where('status', VisitChargeStatus::ACTIVE->value)
            ->sum('line_total');

        $collectedPayments = (float) $billing->payments()
            ->where('is_refund', false)
            ->sum('amount');

        $refunds = (float) $billing->payments()
            ->where('is_refund', true)
            ->sum('amount');

        $discountAmount = (float) ($billing->discount_amount ?? 0);
        $paidAmount = max(0, $collectedPayments - $refunds);
        $balanceAmount = max(0, $grossAmount - $discountAmount - $paidAmount);

        $status = match (true) {
            $grossAmount > 0 && $balanceAmount <= 0 => BillingStatus::FULLY_PAID,
            $paidAmount > 0 && $balanceAmount > 0 => BillingStatus::PARTIAL_PAID,
            $grossAmount > 0 && $billing->payer_type === PayerType::INSURANCE => BillingStatus::INSURANCE_PENDING,
            $billing->payer_type === PayerType::INSURANCE => BillingStatus::INSURANCE_PENDING,
            default => BillingStatus::PENDING,
        };

        $billing->forceFill([
            'gross_amount' => $grossAmount,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'status' => $status,
            'billed_at' => $grossAmount > 0 ? ($billing->billed_at ?? now()) : null,
            'settled_at' => $status === BillingStatus::FULLY_PAID ? now() : null,
        ])->save();

        return $billing->refresh();
    }
}
