<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PatientVisit;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class RecordVisitPayment
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(PatientVisit $visit, array $attributes): Payment
    {
        return DB::transaction(function () use ($visit, $attributes): Payment {
            $billing = $this->ensureVisitBilling->handle($visit);
            $billing = $this->recalculateVisitBilling->handle($billing);

            $amount = round((float) $attributes['amount'], 2);

            if ($billing->balance_amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This visit has no outstanding balance to settle.',
                ]);
            }

            if ($amount > (float) $billing->balance_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot be greater than the outstanding balance.',
                ]);
            }

            $userId = Auth::id();

            $payment = Payment::query()->create([
                'tenant_id' => $visit->tenant_id,
                'facility_branch_id' => $visit->facility_branch_id,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $visit->id,
                'receipt_number' => $this->generateReceiptNumber(),
                'payment_date' => $attributes['payment_date'] ?? now(),
                'amount' => $amount,
                'payment_method' => $attributes['payment_method'],
                'reference_number' => $attributes['reference_number'] ?: null,
                'notes' => $attributes['notes'] ?: null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->recalculateVisitBilling->handle($billing);

            return $payment;
        });
    }

    private function generateReceiptNumber(): string
    {
        return 'RCT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
