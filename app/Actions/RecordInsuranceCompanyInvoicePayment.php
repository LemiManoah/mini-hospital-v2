<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingStatus;
use App\Enums\InsuredVisitClaimStatus;
use App\Models\InsuranceClaimAllocation;
use App\Models\InsuranceCompanyInvoice;
use App\Models\InsuranceCompanyInvoicePayment;
use App\Models\InsuredVisitClaim;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordInsuranceCompanyInvoicePayment
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    /**
     * @param  array<int, array{insured_visit_claim_id: string, allocated_amount: int|float|string, notes?: string|null}>  $allocations
     */
    public function handle(
        InsuranceCompanyInvoice $invoice,
        float $paidAmount,
        array $allocations,
        ?string $paymentDate = null,
        ?string $receipt = null,
    ): InsuranceCompanyInvoicePayment {
        return DB::transaction(function () use ($invoice, $paidAmount, $allocations, $paymentDate, $receipt): InsuranceCompanyInvoicePayment {
            $lockedInvoice = InsuranceCompanyInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $paidAmount = round($paidAmount, 2);

            if ($paidAmount <= 0) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'The insurer payment amount must be greater than zero.',
                ]);
            }

            if ($allocations === []) {
                throw ValidationException::withMessages([
                    'allocations' => 'At least one claim allocation is required for an insurer payment.',
                ]);
            }

            $claimIds = collect($allocations)->pluck('insured_visit_claim_id');

            if ($claimIds->count() !== $claimIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Each claim can only appear once in an insurer payment allocation.',
                ]);
            }

            $allocationTotal = round(array_sum(array_map(
                fn (array $allocation): float => round((float) $allocation['allocated_amount'], 2),
                $allocations,
            )), 2);

            if ($allocationTotal !== $paidAmount) {
                throw ValidationException::withMessages([
                    'allocations' => 'The claim allocation total must match the insurer payment amount.',
                ]);
            }

            $invoiceOutstanding = round((float) $lockedInvoice->bill_amount - (float) $lockedInvoice->paid_amount, 2);

            if ($paidAmount > $invoiceOutstanding) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'The insurer payment cannot exceed the invoice outstanding balance.',
                ]);
            }

            $claims = InsuredVisitClaim::query()
                ->where('insurance_company_invoice_id', $lockedInvoice->id)
                ->whereIn('id', $claimIds->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($claims->count() !== $claimIds->count()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Every allocation must reference a claim on the selected insurer invoice.',
                ]);
            }

            $userId = Auth::id();
            $payment = InsuranceCompanyInvoicePayment::query()->create([
                'tenant_id' => $lockedInvoice->tenant_id,
                'facility_branch_id' => $lockedInvoice->facility_branch_id,
                'insurance_company_invoice_id' => $lockedInvoice->id,
                'payment_date' => $paymentDate ?? now()->toDateString(),
                'receipt' => $receipt,
                'paid_amount' => $paidAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($allocations as $allocation) {
                $claim = $claims->get($allocation['insured_visit_claim_id']);
                $allocatedAmount = round((float) $allocation['allocated_amount'], 2);

                if (! $claim instanceof InsuredVisitClaim || $allocatedAmount <= 0) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Each claim allocation amount must be greater than zero.',
                    ]);
                }

                $claimOutstanding = $this->claimOutstanding($claim);

                if ($allocatedAmount > $claimOutstanding) {
                    throw ValidationException::withMessages([
                        'allocations' => 'A claim allocation cannot exceed the claim outstanding balance.',
                    ]);
                }

                InsuranceClaimAllocation::query()->create([
                    'tenant_id' => $lockedInvoice->tenant_id,
                    'facility_branch_id' => $lockedInvoice->facility_branch_id,
                    'insured_visit_claim_id' => $claim->id,
                    'insurance_company_invoice_id' => $lockedInvoice->id,
                    'insurance_company_invoice_payment_id' => $payment->id,
                    'allocation_date' => $payment->payment_date,
                    'allocated_amount' => $allocatedAmount,
                    'notes' => $allocation['notes'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $newClaimPaidAmount = round((float) $claim->paid_amount + $allocatedAmount, 2);
                $claimPayableAmount = $this->claimPayableAmount($claim);

                $claim->forceFill([
                    'paid_amount' => $newClaimPaidAmount,
                    'status' => $newClaimPaidAmount >= $claimPayableAmount
                        ? InsuredVisitClaimStatus::PAID
                        : InsuredVisitClaimStatus::PARTIALLY_PAID,
                    'paid_at' => $newClaimPaidAmount >= $claimPayableAmount ? now() : null,
                    'updated_by' => $userId,
                ])->save();
            }

            $newInvoicePaidAmount = round((float) $lockedInvoice->paid_amount + $paidAmount, 2);
            $lockedInvoice->forceFill([
                'paid_amount' => $newInvoicePaidAmount,
                'status' => $newInvoicePaidAmount >= (float) $lockedInvoice->bill_amount
                    ? BillingStatus::FULLY_PAID
                    : BillingStatus::PARTIAL_PAID,
                'updated_by' => $userId,
            ])->save();

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'insurance_invoice_payment.recorded',
                subject: $payment,
                description: 'Insurance company invoice payment recorded and allocated to claims.',
                tenantId: $lockedInvoice->tenant_id,
                branchId: $lockedInvoice->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'insurance_company_invoice_id' => $lockedInvoice->id,
                    'payment_id' => $payment->id,
                    'paid_amount' => $paidAmount,
                    'allocation_count' => count($allocations),
                    'allocations' => $allocations,
                ],
            );

            return $payment->refresh();
        });
    }

    private function claimPayableAmount(InsuredVisitClaim $claim): float
    {
        $approvedAmount = round((float) $claim->approved_amount, 2);

        if ($approvedAmount > 0) {
            return $approvedAmount;
        }

        return round((float) $claim->claimed_amount - (float) $claim->rejected_amount - (float) $claim->copay_amount, 2);
    }

    private function claimOutstanding(InsuredVisitClaim $claim): float
    {
        return round($this->claimPayableAmount($claim) - (float) $claim->paid_amount, 2);
    }
}
