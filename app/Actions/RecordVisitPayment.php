<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\CreateVisitPaymentDTO;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Support\GeneralSettings\TenantGeneralSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class RecordVisitPayment
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private TenantGeneralSettings $settings,
    ) {}

    public function handle(PatientVisit $visit, CreateVisitPaymentDTO $data): Payment
    {
        return DB::transaction(function () use ($visit, $data): Payment {
            $tenantId = $visit->tenant_id;
            if (! is_string($tenantId) || $tenantId === '') {
                throw ValidationException::withMessages([
                    'visit' => 'The selected visit is missing tenant context for payment processing.',
                ]);
            }

            $billing = $this->ensureVisitBilling->handle($visit);
            $billing = $this->recalculateVisitBilling->handle($billing);

            $amount = round($data->amount, 2);

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
                'receipt_number' => $this->generateReceiptNumber($tenantId),
                'payment_date' => $data->paymentDate ?? now(),
                'amount' => $amount,
                'payment_method' => $data->paymentMethod,
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->recalculateVisitBilling->handle($billing);

            return $payment;
        });
    }

    private function generateReceiptNumber(string $tenantId): string
    {
        $rawPrefix = (string) ($this->settings->value($tenantId, 'receipt_number_prefix') ?: 'RCT');
        $prefix = mb_strtoupper(mb_trim($rawPrefix)) ?: 'RCT';

        return $prefix.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
