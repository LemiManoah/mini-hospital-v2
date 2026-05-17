<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\CreateVisitPaymentDTO;
use App\Enums\BillingDocumentType;
use App\Models\CurrencyExchangeRate;
use App\Models\FacilityBranch;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordVisitPayment
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private GenerateBillingDocumentNumber $documentNumber,
        private RecordAuditActivity $recordAuditActivity,
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

            $currencyContext = $this->currencyContext($visit, $data);
            $amount = round($currencyContext['base_amount'], 2);

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
            $paymentMethod = PaymentMethod::query()
                ->whereKey($data->paymentMethodId)
                ->where('tenant_id', $visit->tenant_id)
                ->where('facility_branch_id', $visit->facility_branch_id)
                ->where('is_active', true)
                ->first();

            if (! $paymentMethod instanceof PaymentMethod) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'The selected payment method is not available for this branch.',
                ]);
            }

            $payment = Payment::query()->create([
                'tenant_id' => $visit->tenant_id,
                'facility_branch_id' => $visit->facility_branch_id,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $visit->id,
                'receipt_number' => $this->documentNumber->handle(BillingDocumentType::PatientReceipt, $tenantId, $visit->facility_branch_id),
                'payment_date' => $data->paymentDate ?? now(),
                'amount' => $amount,
                'currency_id' => $currencyContext['currency_id'],
                'tender_amount' => $currencyContext['tender_amount'],
                'exchange_rate' => $currencyContext['exchange_rate'],
                'payment_method_id' => $paymentMethod->id,
                'payment_method' => $paymentMethod->code,
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->recalculateVisitBilling->handle($billing);

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'payment.recorded',
                subject: $payment,
                description: 'Payment recorded for patient visit.',
                tenantId: $visit->tenant_id,
                branchId: $visit->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'payment_id' => $payment->id,
                    'visit_id' => $visit->id,
                    'visit_billing_id' => $billing->id,
                    'amount' => $amount,
                    'currency_id' => $currencyContext['currency_id'],
                    'tender_amount' => $currencyContext['tender_amount'],
                    'exchange_rate' => $currencyContext['exchange_rate'],
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method' => $paymentMethod->code,
                    'reference_number' => $payment->reference_number,
                    'receipt_number' => $payment->receipt_number,
                ],
            );

            return $payment;
        });
    }

    /**
     * @return array{currency_id: string|null, tender_amount: float, exchange_rate: float, base_amount: float}
     */
    private function currencyContext(PatientVisit $visit, CreateVisitPaymentDTO $data): array
    {
        $visit->loadMissing('branch');

        $branch = $visit->branch;

        if (! $branch instanceof FacilityBranch) {
            throw ValidationException::withMessages([
                'currency_id' => 'The visit branch could not be resolved for payment currency conversion.',
            ]);
        }

        $baseCurrencyId = $branch->getAttribute('currency_id');
        $baseCurrencyId = is_string($baseCurrencyId) && $baseCurrencyId !== '' ? $baseCurrencyId : null;

        $selectedCurrencyId = $data->currencyId ?: $baseCurrencyId;
        $tenderAmount = round($data->amount, 2);

        if ($selectedCurrencyId === null || $baseCurrencyId === null || $selectedCurrencyId === $baseCurrencyId) {
            return [
                'currency_id' => $selectedCurrencyId,
                'tender_amount' => $tenderAmount,
                'exchange_rate' => 1.0,
                'base_amount' => $tenderAmount,
            ];
        }

        if (! $branch->multi_currency_enabled) {
            throw ValidationException::withMessages([
                'currency_id' => 'Multi-currency is not enabled for this branch.',
            ]);
        }

        if (! $branch->supportedCurrencies()->where('currencies.id', $selectedCurrencyId)->exists()) {
            throw ValidationException::withMessages([
                'currency_id' => 'The selected currency is not enabled for this branch.',
            ]);
        }

        $rate = CurrencyExchangeRate::query()
            ->where('tenant_id', $visit->tenant_id)
            ->where('facility_branch_id', $visit->facility_branch_id)
            ->where('from_currency_id', $selectedCurrencyId)
            ->where('to_currency_id', $baseCurrencyId)
            ->where('effective_date', '<=', now()->toDateString())
            ->latest('effective_date')
            ->value('rate');

        if (! is_numeric($rate)) {
            throw ValidationException::withMessages([
                'currency_id' => 'No active exchange rate exists for the selected payment currency.',
            ]);
        }

        $exchangeRate = (float) $rate;

        return [
            'currency_id' => $selectedCurrencyId,
            'tender_amount' => $tenderAmount,
            'exchange_rate' => $exchangeRate,
            'base_amount' => round($tenderAmount * $exchangeRate, 2),
        ];
    }
}
