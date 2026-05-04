<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateInsuranceCompanyInvoice;
use App\Actions\ListAuditTimeline;
use App\Actions\RecordInsuranceCompanyInvoicePayment;
use App\Enums\InsuredVisitClaimStatus;
use App\Http\Requests\StoreInsuranceCompanyInvoiceBatchRequest;
use App\Http\Requests\StoreInsuranceCompanyInvoicePaymentRequest;
use App\Models\FacilityBranch;
use App\Models\InsuranceClaimAllocation;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCompanyInvoice;
use App\Models\InsuranceCompanyInvoicePayment;
use App\Models\InsuredVisitClaim;
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InsuranceCompanyInvoiceController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private ListAuditTimeline $listAuditTimeline,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_claims.view', only: ['index', 'show']),
            new Middleware('permission:insurance_claims.create', only: ['store']),
            new Middleware('permission:insurance_payments.create', only: ['storePayment']),
        ];
    }

    public function index(Request $request): Response
    {
        $branch = BranchContext::getActiveBranch();

        abort_if(! $branch instanceof FacilityBranch, 403, 'Select an active branch before managing insurer invoices.');

        $status = mb_trim((string) $request->query('status', ''));

        /** @var LengthAwarePaginator<int, InsuranceCompanyInvoice> $invoices */
        $invoices = $this->activeBranchWorkspace
            ->apply(InsuranceCompanyInvoice::query())
            ->with('insuranceCompany:id,name')
            ->withCount('claims')
            ->when(
                $status !== '' && $status !== 'all',
                static fn (Builder $query): Builder => $query->where('status', $status),
            )
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (InsuranceCompanyInvoice $invoice): array => $this->serializeInvoiceRow($invoice));

        $readyClaimBatches = InsuredVisitClaim::query()
            ->select('insurance_company_id')
            ->selectRaw('count(*) as claims_count')
            ->selectRaw('sum(claimed_amount) as claim_total')
            ->where('tenant_id', $branch->tenant_id)
            ->where('facility_branch_id', $branch->id)
            ->where('status', InsuredVisitClaimStatus::READY_FOR_INVOICE)
            ->whereNull('insurance_company_invoice_id')
            ->with('insuranceCompany:id,name')
            ->groupBy('insurance_company_id')
            ->orderByDesc('claim_total')
            ->get()
            ->map(static function (InsuredVisitClaim $claim): array {
                $insuranceCompany = $claim->getRelation('insuranceCompany');

                return [
                    'insurance_company_id' => $claim->insurance_company_id,
                    'insurance_company_name' => $insuranceCompany instanceof InsuranceCompany ? $insuranceCompany->name : 'Unknown insurer',
                    'claims_count' => self::intAttribute($claim, 'claims_count'),
                    'claim_total' => self::floatAttribute($claim, 'claim_total'),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('finance/insurance-invoices/index', [
            'invoices' => $invoices,
            'readyClaimBatches' => $readyClaimBatches,
            'filters' => [
                'status' => $status,
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All statuses'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'partial_paid', 'label' => 'Partial Paid'],
                ['value' => 'fully_paid', 'label' => 'Fully Paid'],
            ],
        ]);
    }

    public function store(
        StoreInsuranceCompanyInvoiceBatchRequest $request,
        GenerateInsuranceCompanyInvoice $action,
    ): RedirectResponse {
        $branch = BranchContext::getActiveBranch();

        abort_if(! $branch instanceof FacilityBranch, 403, 'Select an active branch before generating insurer invoices.');

        $invoice = $action->handle(
            tenantId: $branch->tenant_id,
            branchId: $branch->id,
            insuranceCompanyId: $request->insuranceCompanyId(),
            startDate: $request->startDate(),
            endDate: $request->endDate(),
        );

        return to_route('finance.insurance-invoices.show', $invoice)
            ->with('success', 'Insurer invoice generated successfully.');
    }

    public function show(InsuranceCompanyInvoice $invoice): Response
    {
        $this->activeBranchWorkspace->authorizeModel($invoice);

        $invoice->loadCount(['claims'])
            ->load([
                'insuranceCompany:id,name',
                'claims:id,tenant_id,facility_branch_id,visit_billing_id,patient_visit_id,insurance_company_id,insurance_package_id,insurance_company_invoice_id,claim_reference,claimed_amount,approved_amount,rejected_amount,copay_amount,paid_amount,status,invoiced_at,paid_at',
                'claims.visit:id,patient_id,visit_number',
                'claims.visit.patient:id,patient_number,first_name,last_name',
            ])
            ->load([
                'payments' => static fn (HasMany $query): HasMany => $query
                    ->select('id', 'tenant_id', 'facility_branch_id', 'insurance_company_invoice_id', 'payment_date', 'receipt', 'paid_amount')
                    ->with(['allocations.claim:id,claim_reference'])
                    ->latest('payment_date'),
            ]);

        return Inertia::render('finance/insurance-invoices/show', [
            'invoice' => $this->serializeInvoiceDetail($invoice),
            'audit_activity' => $this->listAuditTimeline->handle(
                subjects: [
                    $invoice,
                    ...$invoice->claims->all(),
                    ...$invoice->payments->all(),
                ],
                tenantId: $invoice->tenant_id,
                logNames: ['billing'],
            ),
        ]);
    }

    public function storePayment(
        StoreInsuranceCompanyInvoicePaymentRequest $request,
        InsuranceCompanyInvoice $invoice,
        RecordInsuranceCompanyInvoicePayment $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($invoice);

        $action->handle(
            invoice: $invoice,
            paidAmount: $request->paidAmount(),
            allocations: $request->allocations(),
            paymentDate: $request->paymentDate(),
            receipt: $request->receipt(),
        );

        return to_route('finance.insurance-invoices.show', $invoice)
            ->with('success', 'Insurer payment recorded successfully.');
    }

    private static function floatAttribute(InsuredVisitClaim $claim, string $attribute): float
    {
        $value = $claim->getAttribute($attribute);

        return round(is_numeric($value) ? (float) $value : 0.0, 2);
    }

    private static function intAttribute(InsuredVisitClaim $claim, string $attribute): int
    {
        $value = $claim->getAttribute($attribute);

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvoiceRow(InsuranceCompanyInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'code' => $invoice->code,
            'insurance_company_name' => $invoice->insuranceCompany?->name,
            'start_date' => $invoice->start_date?->toDateString(),
            'end_date' => $invoice->end_date?->toDateString(),
            'bill_amount' => round((float) $invoice->bill_amount, 2),
            'paid_amount' => round((float) $invoice->paid_amount, 2),
            'balance_amount' => round((float) $invoice->bill_amount - (float) $invoice->paid_amount, 2),
            'status' => $this->backedEnumValue($invoice->status, $invoice->getAttribute('status')),
            'claims_count' => $invoice->claims_count,
            'created_at' => $invoice->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvoiceDetail(InsuranceCompanyInvoice $invoice): array
    {
        return [
            ...$this->serializeInvoiceRow($invoice),
            'claims' => $invoice->claims
                ->map(fn (InsuredVisitClaim $claim): array => $this->serializeClaim($claim))
                ->values()
                ->all(),
            'payments' => $invoice->payments
                ->map(fn (InsuranceCompanyInvoicePayment $payment): array => [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date->toDateString(),
                    'receipt' => $payment->receipt,
                    'paid_amount' => round((float) $payment->paid_amount, 2),
                    'allocations' => $payment->allocations
                        ->map(static fn (InsuranceClaimAllocation $allocation): array => [
                            'id' => $allocation->id,
                            'claim_reference' => $allocation->claim?->claim_reference,
                            'allocated_amount' => round((float) $allocation->allocated_amount, 2),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeClaim(InsuredVisitClaim $claim): array
    {
        $payableAmount = $this->claimPayableAmount($claim);

        return [
            'id' => $claim->id,
            'claim_reference' => $claim->claim_reference,
            'visit_number' => $claim->visit?->visit_number,
            'patient_name' => $claim->visit?->patient === null
                ? 'Unknown patient'
                : mb_trim(sprintf('%s %s', $claim->visit->patient->first_name, $claim->visit->patient->last_name)),
            'patient_number' => $claim->visit?->patient?->patient_number,
            'claimed_amount' => round((float) $claim->claimed_amount, 2),
            'approved_amount' => round((float) $claim->approved_amount, 2),
            'rejected_amount' => round((float) $claim->rejected_amount, 2),
            'copay_amount' => round((float) $claim->copay_amount, 2),
            'payable_amount' => $payableAmount,
            'paid_amount' => round((float) $claim->paid_amount, 2),
            'outstanding_amount' => round($payableAmount - (float) $claim->paid_amount, 2),
            'status' => $this->backedEnumValue($claim->status, $claim->getAttribute('status')),
            'invoiced_at' => $claim->invoiced_at?->toISOString(),
            'paid_at' => $claim->paid_at?->toISOString(),
        ];
    }

    private function claimPayableAmount(InsuredVisitClaim $claim): float
    {
        $approvedAmount = round((float) $claim->approved_amount, 2);

        if ($approvedAmount > 0) {
            return $approvedAmount;
        }

        return round((float) $claim->claimed_amount - (float) $claim->rejected_amount - (float) $claim->copay_amount, 2);
    }

    private function backedEnumValue(mixed $value, mixed $fallback): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($fallback) ? $fallback : '';
    }
}
