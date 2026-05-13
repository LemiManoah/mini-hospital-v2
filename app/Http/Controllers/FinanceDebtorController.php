<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveBillingWriteOff;
use App\Actions\ListAuditTimeline;
use App\Actions\RequestBillingWriteOff;
use App\Actions\ReverseBillingWriteOff;
use App\Http\Requests\ReverseBillingWriteOffRequest;
use App\Http\Requests\StoreBillingWriteOffRequest;
use App\Models\BillingWriteOff;
use App\Models\Payment;
use App\Models\VisitBilling;
use App\Models\VisitCharge;
use App\Support\ActiveBranchWorkspace;
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

final readonly class FinanceDebtorController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private ListAuditTimeline $listAuditTimeline,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visit_billings.view', only: ['index', 'show']),
            new Middleware('permission:billing_write_offs.create', only: ['storeWriteOff']),
            new Middleware('permission:billing_write_offs.approve', only: ['approveWriteOff']),
            new Middleware('permission:billing_write_offs.reverse', only: ['reverseWriteOff']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        /** @var LengthAwarePaginator<int, VisitBilling> $billings */
        $billings = $this->activeBranchWorkspace
            ->apply(VisitBilling::query())
            ->with([
                'visit:id,patient_id,visit_number,visit_type,status,registered_at',
                'visit.patient:id,patient_number,first_name,last_name,phone_number',
                'visitPayer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
                'visitPayer.insuranceCompany:id,name',
                'visitPayer.insurancePackage:id,name',
            ])
            ->where('balance_amount', '>', 0)
            ->when($search !== '', static fn (Builder $query): Builder => $query->whereHas('visit', static function (Builder $visitQuery) use ($search): void {
                $visitQuery
                    ->whereLike('visit_number', sprintf('%%%s%%', $search))
                    ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                        $patientQuery
                            ->whereLike('patient_number', sprintf('%%%s%%', $search))
                            ->orWhereLike('first_name', sprintf('%%%s%%', $search))
                            ->orWhereLike('last_name', sprintf('%%%s%%', $search))
                            ->orWhereLike('phone_number', sprintf('%%%s%%', $search));
                    });
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (VisitBilling $billing): array => $this->serializeDebtorRow($billing));

        return Inertia::render('finance/debtors/index', [
            'billings' => $billings,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(VisitBilling $billing): Response
    {
        $this->activeBranchWorkspace->authorizeModel($billing);

        $billing->load([
            'visit.patient:id,patient_number,first_name,last_name,phone_number',
            'visitPayer.insuranceCompany:id,name',
            'visitPayer.insurancePackage:id,name',
            'charges' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'description', 'quantity', 'unit_price', 'line_total', 'status', 'charged_at')
                ->latest('charged_at'),
            'payments' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'receipt_number', 'payment_date', 'amount', 'payment_method', 'reference_number', 'is_refund', 'notes')
                ->latest('payment_date'),
            'writeOffs' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'amount', 'reason', 'status', 'notes', 'requested_at', 'approved_at', 'reversed_at', 'reversal_reason')
                ->latest(),
        ]);

        return Inertia::render('finance/debtors/show', [
            'billing' => $this->serializeDebtorDetail($billing),
            'audit_activity' => $this->listAuditTimeline->handle(
                subjects: [
                    $billing,
                    ...$billing->payments->all(),
                    ...$billing->writeOffs->all(),
                ],
                tenantId: $billing->tenant_id,
                logNames: ['billing'],
            ),
        ]);
    }

    public function storeWriteOff(
        StoreBillingWriteOffRequest $request,
        VisitBilling $billing,
        RequestBillingWriteOff $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($billing);

        $action->handle($billing, $request->amount(), $request->reason(), $request->notes());

        return to_route('finance.debtors.show', $billing)->with('success', 'Write-off requested successfully.');
    }

    public function approveWriteOff(
        VisitBilling $billing,
        BillingWriteOff $writeOff,
        ApproveBillingWriteOff $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($billing);
        abort_unless($writeOff->visit_billing_id === $billing->id, 404);

        $action->handle($writeOff);

        return to_route('finance.debtors.show', $billing)->with('success', 'Write-off approved successfully.');
    }

    public function reverseWriteOff(
        ReverseBillingWriteOffRequest $request,
        VisitBilling $billing,
        BillingWriteOff $writeOff,
        ReverseBillingWriteOff $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($billing);
        abort_unless($writeOff->visit_billing_id === $billing->id, 404);

        $action->handle($writeOff, $request->reversalReason());

        return to_route('finance.debtors.show', $billing)->with('success', 'Write-off reversed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDebtorRow(VisitBilling $billing): array
    {
        return [
            'id' => $billing->id,
            'visit_number' => $billing->visit?->visit_number,
            'registered_at' => $billing->visit?->registered_at?->toISOString(),
            'patient_name' => $billing->visit?->patient === null
                ? 'Unknown patient'
                : mb_trim(sprintf('%s %s', $billing->visit->patient->first_name, $billing->visit->patient->last_name)),
            'patient_number' => $billing->visit?->patient?->patient_number,
            'payer_type' => $this->backedEnumValue($billing->payer_type, $billing->getAttribute('payer_type')),
            'insurance_company_name' => $billing->visitPayer?->insuranceCompany?->name,
            'insurance_package_name' => $billing->visitPayer?->insurancePackage?->name,
            'gross_amount' => round((float) $billing->gross_amount, 2),
            'discount_amount' => round((float) $billing->discount_amount, 2),
            'write_off_amount' => round((float) $billing->write_off_amount, 2),
            'paid_amount' => round((float) $billing->paid_amount, 2),
            'balance_amount' => round((float) $billing->balance_amount, 2),
            'status' => $this->backedEnumValue($billing->status, $billing->getAttribute('status')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDebtorDetail(VisitBilling $billing): array
    {
        return [
            ...$this->serializeDebtorRow($billing),
            'charges' => $billing->charges->map(static fn (VisitCharge $charge): array => [
                'id' => $charge->id,
                'description' => $charge->description,
                'quantity' => round((float) $charge->quantity, 2),
                'unit_price' => round((float) $charge->unit_price, 2),
                'line_total' => round((float) $charge->line_total, 2),
                'status' => $charge->status,
                'charged_at' => $charge->charged_at?->toISOString(),
            ])->values()->all(),
            'payments' => $billing->payments->map(static fn (Payment $payment): array => [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'payment_date' => $payment->payment_date?->toISOString(),
                'amount' => round((float) $payment->amount, 2),
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'is_refund' => (bool) $payment->is_refund,
                'notes' => $payment->notes,
            ])->values()->all(),
            'write_offs' => $billing->writeOffs->map(static fn (BillingWriteOff $writeOff): array => [
                'id' => $writeOff->id,
                'amount' => round((float) $writeOff->amount, 2),
                'reason' => $writeOff->reason,
                'status' => $writeOff->status?->value,
                'notes' => $writeOff->notes,
                'requested_at' => $writeOff->requested_at?->toISOString(),
                'approved_at' => $writeOff->approved_at?->toISOString(),
                'reversed_at' => $writeOff->reversed_at?->toISOString(),
                'reversal_reason' => $writeOff->reversal_reason,
            ])->values()->all(),
        ];
    }

    private function backedEnumValue(mixed $value, mixed $fallback): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($fallback) ? $fallback : '';
    }
}
