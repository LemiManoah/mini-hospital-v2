<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingDepositStatus;
use App\Enums\BillingDocumentType;
use App\Models\BillingDeposit;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordBillingDeposit
{
    public function __construct(
        private GenerateBillingDocumentNumber $documentNumber,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(Patient $patient, string $branchId, float $amount, string $paymentMethodId, ?PatientVisit $visit = null, ?string $referenceNumber = null, ?string $notes = null): BillingDeposit
    {
        return DB::transaction(function () use ($patient, $branchId, $amount, $paymentMethodId, $visit, $referenceNumber, $notes): BillingDeposit {
            $tenantId = $patient->tenant_id;

            if ($tenantId === '') {
                throw ValidationException::withMessages([
                    'patient' => 'The selected patient is missing tenant context.',
                ]);
            }

            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Deposit amount must be greater than zero.',
                ]);
            }

            $paymentMethod = PaymentMethod::query()
                ->whereKey($paymentMethodId)
                ->where('tenant_id', $tenantId)
                ->where('facility_branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if (! $paymentMethod instanceof PaymentMethod) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'The selected payment method is not available for this branch.',
                ]);
            }

            if ($paymentMethod->requires_reference && (string) $referenceNumber === '') {
                throw ValidationException::withMessages([
                    'reference_number' => sprintf('%s requires a reference number.', $paymentMethod->name),
                ]);
            }

            $userId = Auth::id();

            $deposit = BillingDeposit::query()->create([
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'patient_id' => $patient->id,
                'patient_visit_id' => $visit?->id,
                'deposit_number' => $this->documentNumber->handle(BillingDocumentType::DepositReceipt, $tenantId, $branchId),
                'payment_method_id' => $paymentMethod->id,
                'payment_method' => $paymentMethod->code,
                'reference_number' => $referenceNumber,
                'amount' => $amount,
                'applied_amount' => 0,
                'refunded_amount' => 0,
                'status' => BillingDepositStatus::Held,
                'received_at' => now(),
                'notes' => $notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'deposit.recorded',
                subject: $deposit,
                description: 'Billing deposit recorded.',
                tenantId: $tenantId,
                branchId: $branchId,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'deposit_id' => $deposit->id,
                    'deposit_number' => $deposit->deposit_number,
                    'patient_id' => $patient->id,
                    'patient_visit_id' => $visit?->id,
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method' => $paymentMethod->code,
                ],
            );

            return $deposit->refresh();
        });
    }
}
