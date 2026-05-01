<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingStatus;
use App\Enums\InsuredVisitClaimStatus;
use App\Models\InsuranceCompanyInvoice;
use App\Models\InsuredVisitClaim;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class GenerateInsuranceCompanyInvoice
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(
        string $tenantId,
        ?string $branchId,
        string $insuranceCompanyId,
        ?string $startDate = null,
        ?string $endDate = null,
    ): InsuranceCompanyInvoice {
        return DB::transaction(function () use ($tenantId, $branchId, $insuranceCompanyId, $startDate, $endDate): InsuranceCompanyInvoice {
            $claims = InsuredVisitClaim::query()
                ->where('tenant_id', $tenantId)
                ->where('insurance_company_id', $insuranceCompanyId)
                ->where('status', InsuredVisitClaimStatus::READY_FOR_INVOICE)
                ->whereNull('insurance_company_invoice_id')
                ->when($branchId !== null, fn ($query) => $query->where('facility_branch_id', $branchId))
                ->when($startDate !== null, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
                ->when($endDate !== null, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
                ->lockForUpdate()
                ->get();

            if ($claims->isEmpty()) {
                throw ValidationException::withMessages([
                    'claims' => 'No ready insurance claims were found for this invoice batch.',
                ]);
            }

            $claimAmount = round($claims->sum(fn (InsuredVisitClaim $claim): float => (float) $claim->claimed_amount), 2);
            $userId = Auth::id();
            $invoice = InsuranceCompanyInvoice::query()->create([
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'insurance_company_id' => $insuranceCompanyId,
                'code' => $this->generateInvoiceCode(),
                'start_date' => $startDate ?? $claims->min('created_at')?->toDateString(),
                'end_date' => $endDate ?? $claims->max('created_at')?->toDateString(),
                'bill_amount' => $claimAmount,
                'paid_amount' => 0,
                'status' => BillingStatus::PENDING,
                'is_printed' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            InsuredVisitClaim::query()
                ->whereKey($claims->pluck('id'))
                ->update([
                    'insurance_company_invoice_id' => $invoice->id,
                    'status' => InsuredVisitClaimStatus::INVOICED,
                    'invoiced_at' => now(),
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'billing',
                event: 'insurance_invoice.generated',
                subject: $invoice,
                description: 'Insurance company invoice generated from ready claims.',
                tenantId: $tenantId,
                branchId: $branchId,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'invoice_id' => $invoice->id,
                    'code' => $invoice->code,
                    'insurance_company_id' => $insuranceCompanyId,
                    'claim_count' => $claims->count(),
                    'claim_ids' => $claims->pluck('id')->all(),
                    'bill_amount' => $claimAmount,
                ],
            );

            return $invoice->refresh();
        });
    }

    private function generateInvoiceCode(): string
    {
        return 'ICI-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
