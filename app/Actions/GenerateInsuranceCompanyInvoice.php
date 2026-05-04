<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingDocumentType;
use App\Enums\BillingStatus;
use App\Enums\InsuredVisitClaimStatus;
use App\Models\InsuranceCompanyInvoice;
use App\Models\InsuredVisitClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class GenerateInsuranceCompanyInvoice
{
    public function __construct(
        private GenerateBillingDocumentNumber $documentNumber,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

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
                ->when($branchId !== null, fn (Builder $query): Builder => $query->where('facility_branch_id', $branchId))
                ->when($startDate !== null, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $startDate))
                ->when($endDate !== null, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $endDate))
                ->lockForUpdate()
                ->get();

            if ($claims->isEmpty()) {
                throw ValidationException::withMessages([
                    'claims' => 'No ready insurance claims were found for this invoice batch.',
                ]);
            }

            $claimAmount = round($claims->sum(fn (InsuredVisitClaim $claim): float => (float) $claim->claimed_amount), 2);
            $firstClaim = $claims->sortBy('created_at')->first();
            $lastClaim = $claims->sortByDesc('created_at')->first();
            $userId = Auth::id();
            $invoice = InsuranceCompanyInvoice::query()->create([
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'insurance_company_id' => $insuranceCompanyId,
                'code' => $this->documentNumber->handle(BillingDocumentType::InsuranceInvoice, $tenantId, $branchId),
                'start_date' => $startDate ?? $firstClaim?->created_at?->toDateString(),
                'end_date' => $endDate ?? $lastClaim?->created_at?->toDateString(),
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
}
