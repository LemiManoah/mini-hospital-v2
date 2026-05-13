<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EnsureBranchPaymentMethods;
use App\Actions\ListAuditTimeline;
use App\Actions\RecordVisitPayment;
use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Requests\StoreVisitPaymentRequest;
use App\Models\PatientVisit;
use App\Models\PaymentMethod;
use App\Models\VisitBilling;
use App\Models\VisitCharge;
use App\Support\ActiveBranchWorkspace;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FinanceOpdPaymentController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private EnsureBranchPaymentMethods $ensureBranchPaymentMethods,
        private ListAuditTimeline $listAuditTimeline,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments.view', only: ['index', 'show']),
            new Middleware('permission:payments.create', only: ['store']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $payerType = mb_trim((string) $request->query('payer_type', ''));
        $status = mb_trim((string) $request->query('status', ''));

        /** @var LengthAwarePaginator<int, PatientVisit> $visits */
        $visits = $this->activeBranchWorkspace->apply(PatientVisit::query())
            ->with([
                'patient:id,patient_number,first_name,last_name,phone_number',
                'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
                'payer.insuranceCompany:id,name',
                'payer.insurancePackage:id,name',
                'billing:id,patient_visit_id,visit_payer_id,payer_type,gross_amount,discount_amount,paid_amount,balance_amount,status,billed_at,settled_at',
                'charges:id,visit_billing_id,patient_visit_id,description,line_total,copay_amount,status',
            ])
            ->whereIn('status', [
                VisitStatus::REGISTERED->value,
                VisitStatus::IN_PROGRESS->value,
                VisitStatus::AWAITING_PAYMENT->value,
            ])
            ->whereIn('visit_type', [
                VisitType::NEW->value,
                VisitType::FOLLOW_UP->value,
                VisitType::OUTPATIENT->value,
                VisitType::OPD_CONSULTATION->value,
                VisitType::PROCEDURE->value,
                VisitType::LAB_INVESTIGATION->value,
                VisitType::TELEMEDICINE->value,
                VisitType::EMERGENCY->value,
            ])
            ->whereHas('billing', static fn (Builder $query): Builder => $query
                ->where('gross_amount', '>', 0)
                ->where('balance_amount', '>', 0))
            ->when(
                $search !== '',
                static fn (Builder $query): Builder => $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->whereLike('visit_number', sprintf('%%%s%%', $search))
                        ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                            $patientQuery
                                ->whereLike('patient_number', sprintf('%%%s%%', $search))
                                ->orWhereLike('first_name', sprintf('%%%s%%', $search))
                                ->orWhereLike('last_name', sprintf('%%%s%%', $search))
                                ->orWhereLike('phone_number', sprintf('%%%s%%', $search));
                        });
                })
            )
            ->when(
                $payerType !== '' && $payerType !== 'all',
                static fn (Builder $query): Builder => $query->whereHas(
                    'payer',
                    static fn (Builder $payerQuery): Builder => $payerQuery->where('billing_type', $payerType),
                )
            )
            ->when(
                $status !== '' && $status !== 'all',
                static fn (Builder $query): Builder => $query->whereHas(
                    'billing',
                    static fn (Builder $billingQuery): Builder => $billingQuery->where('status', $status),
                )
            )
            ->latest('registered_at')
            ->paginate(12)
            ->withQueryString()
            ->through(static fn (PatientVisit $visit): array => [
                'id' => $visit->id,
                'visit_number' => $visit->visit_number,
                'visit_type' => self::backedEnumValue($visit->visit_type, $visit->getAttribute('visit_type')),
                'status' => self::backedEnumValue($visit->status, $visit->getAttribute('status')),
                'registered_at' => $visit->registered_at?->toISOString(),
                'patient' => $visit->patient === null ? null : [
                    'id' => $visit->patient->id,
                    'patient_number' => $visit->patient->patient_number,
                    'full_name' => mb_trim(sprintf('%s %s', $visit->patient->first_name, $visit->patient->last_name)),
                    'phone_number' => $visit->patient->phone_number,
                ],
                'payer' => $visit->payer === null ? null : [
                    'billing_type' => self::backedEnumValue($visit->payer->billing_type, $visit->payer->getAttribute('billing_type')),
                    'insurance_company_name' => $visit->payer->insuranceCompany?->name,
                    'insurance_package_name' => $visit->payer->insurancePackage?->name,
                ],
                'billing' => $visit->billing === null ? null : [
                    'gross_amount' => (float) ($visit->billing->gross_amount ?? 0),
                    'discount_amount' => (float) ($visit->billing->discount_amount ?? 0),
                    'paid_amount' => (float) ($visit->billing->paid_amount ?? 0),
                    'balance_amount' => (float) ($visit->billing->balance_amount ?? 0),
                    'status' => self::backedEnumValue($visit->billing->status, $visit->billing->getAttribute('status')),
                    'split' => self::billingSplit($visit->billing, $visit->charges),
                ],
                'charges_count' => $visit->charges->count(),
            ]);

        return Inertia::render('finance/opd-payments/index', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
                'payer_type' => $payerType,
                'status' => $status,
            ],
            'payerTypeOptions' => [
                ['value' => 'all', 'label' => 'All payers'],
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'insurance', 'label' => 'Insurance'],
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All statuses'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'partial_paid', 'label' => 'Partial Paid'],
                ['value' => 'insurance_pending', 'label' => 'Insurance Pending'],
            ],
        ]);
    }

    public function show(PatientVisit $visit): Response
    {
        $this->activeBranchWorkspace->authorizeModel($visit);
        $tenantId = $visit->tenant_id;

        abort_unless(is_string($tenantId), 404);

        $visit->load([
            'patient:id,patient_number,first_name,last_name,middle_name,phone_number,gender',
            'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'payer.insuranceCompany:id,name',
            'payer.insurancePackage:id,name',
            'billing:id,patient_visit_id,visit_payer_id,payer_type,gross_amount,discount_amount,paid_amount,balance_amount,status,billed_at,settled_at',
            'billing.payments' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'payment_method_id', 'receipt_number', 'payment_date', 'amount', 'payment_method', 'reference_number', 'is_refund', 'notes')
                ->latest('payment_date'),
            'billing.discounts' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'amount', 'reason', 'status', 'notes', 'requested_by', 'requested_at', 'approved_by', 'approved_at', 'reversed_by', 'reversed_at', 'reversal_reason')
                ->latest(),
            'charges' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'source_type', 'source_id', 'charge_master_id', 'charge_code', 'description', 'quantity', 'unit_price', 'line_total', 'copay_amount', 'status', 'charged_at')
                ->latest('charged_at'),
        ]);

        if ($visit->billing instanceof VisitBilling) {
            $visit->billing->setAttribute('split', self::billingSplit($visit->billing, $visit->charges));
        }

        return Inertia::render('finance/opd-payments/show', [
            'visit' => $visit,
            'paymentMethods' => $this->ensureBranchPaymentMethods
                ->handle($tenantId, $visit->facility_branch_id)
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->id,
                    'label' => $method->name,
                ])
                ->values()
                ->all(),
            'audit_activity' => $this->listAuditTimeline->handle(
                subjects: [
                    $visit->billing,
                    ...($visit->billing?->payments?->all() ?? []),
                    ...($visit->billing?->discounts?->all() ?? []),
                ],
                tenantId: $tenantId,
                logNames: ['billing'],
            ),
        ]);
    }

    public function store(
        StoreVisitPaymentRequest $request,
        PatientVisit $visit,
        RecordVisitPayment $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $action->handle($visit, $request->createDto());

        return to_route('finance.opd-payments.show', $visit)->with('success', 'Payment recorded successfully.');
    }

    private static function backedEnumValue(mixed $value, mixed $fallback): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($fallback) ? $fallback : '';
    }

    /**
     * @param  Collection<int, VisitCharge>  $charges
     * @return array{patient_responsibility_amount: float, insurer_responsibility_amount: float, patient_paid_amount: float, patient_balance_amount: float, insurer_balance_amount: float, copay_amount: float}
     */
    private static function billingSplit(VisitBilling $billing, Collection $charges): array
    {
        $grossAmount = max(0.0, round((float) $billing->gross_amount, 2));
        $discountAmount = max(0.0, round((float) $billing->discount_amount, 2));
        $paidAmount = max(0.0, round((float) $billing->paid_amount, 2));
        $balanceAmount = max(0.0, round((float) $billing->balance_amount, 2));
        $activeCopayAmount = round($charges->sum(static function (VisitCharge $charge): float {
            if (self::backedEnumValue($charge->status, $charge->getAttribute('status')) !== 'active') {
                return 0.0;
            }

            return (float) $charge->copay_amount;
        }), 2);
        $copayAmount = min($grossAmount, max(0.0, $activeCopayAmount));

        if ($billing->payer_type !== PayerType::INSURANCE) {
            $patientResponsibility = max(0.0, round($grossAmount - $discountAmount, 2));

            return [
                'patient_responsibility_amount' => $patientResponsibility,
                'insurer_responsibility_amount' => 0.0,
                'patient_paid_amount' => min($paidAmount, $patientResponsibility),
                'patient_balance_amount' => max(0.0, round($patientResponsibility - $paidAmount, 2)),
                'insurer_balance_amount' => 0.0,
                'copay_amount' => 0.0,
            ];
        }

        $patientResponsibility = max(0.0, round($copayAmount - $discountAmount, 2));
        $insurerResponsibility = max(0.0, round($grossAmount - $copayAmount, 2));
        $patientPaidAmount = min($paidAmount, $patientResponsibility);
        $patientBalanceAmount = max(0.0, round($patientResponsibility - $patientPaidAmount, 2));

        return [
            'patient_responsibility_amount' => $patientResponsibility,
            'insurer_responsibility_amount' => $insurerResponsibility,
            'patient_paid_amount' => $patientPaidAmount,
            'patient_balance_amount' => $patientBalanceAmount,
            'insurer_balance_amount' => max(0.0, round($balanceAmount - $patientBalanceAmount, 2)),
            'copay_amount' => $copayAmount,
        ];
    }
}
