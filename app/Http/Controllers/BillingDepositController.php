<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApplyBillingDeposit;
use App\Actions\EnsureBranchPaymentMethods;
use App\Actions\RecordBillingDeposit;
use App\Http\Requests\ApplyBillingDepositRequest;
use App\Http\Requests\StoreBillingDepositRequest;
use App\Models\BillingDeposit;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PaymentMethod;
use App\Models\VisitBilling;
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BillingDepositController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private EnsureBranchPaymentMethods $ensureBranchPaymentMethods,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:billing_deposits.view', only: ['index']),
            new Middleware('permission:billing_deposits.create', only: ['store']),
            new Middleware('permission:billing_deposits.apply', only: ['apply']),
        ];
    }

    public function index(Request $request): Response
    {
        $branch = BranchContext::getActiveBranch();

        abort_if(! $branch instanceof FacilityBranch, 403, 'Select an active branch before managing deposits.');

        $search = mb_trim((string) $request->query('search', ''));

        /** @var LengthAwarePaginator<int, BillingDeposit> $deposits */
        $deposits = $this->activeBranchWorkspace
            ->apply(BillingDeposit::query())
            ->with([
                'patient:id,patient_number,first_name,last_name,phone_number',
                'visit:id,visit_number',
            ])
            ->when($search !== '', static fn (Builder $query): Builder => $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('deposit_number', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                        $patientQuery
                            ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search));
                    });
            }))
            ->latest('received_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (BillingDeposit $deposit): array => $this->serializeDeposit($deposit));

        return Inertia::render('finance/deposits/index', [
            'deposits' => $deposits,
            'filters' => ['search' => $search],
            'paymentMethods' => $this->ensureBranchPaymentMethods
                ->handle($branch->tenant_id, $branch->id)
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->id,
                    'label' => $method->name,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreBillingDepositRequest $request, RecordBillingDeposit $action): RedirectResponse
    {
        $branch = BranchContext::getActiveBranch();

        abort_if(! $branch instanceof FacilityBranch, 403, 'Select an active branch before recording deposits.');

        $patient = Patient::query()
            ->where('tenant_id', $branch->tenant_id)
            ->where('patient_number', $request->string('patient_number')->toString())
            ->first();

        if (! $patient instanceof Patient) {
            throw ValidationException::withMessages([
                'patient_number' => 'No patient was found for this patient number.',
            ]);
        }

        $visit = null;
        $visitNumber = $request->string('visit_number')->toString();

        if ($visitNumber !== '') {
            $visit = PatientVisit::query()
                ->where('tenant_id', $branch->tenant_id)
                ->where('facility_branch_id', $branch->id)
                ->where('patient_id', $patient->id)
                ->where('visit_number', $visitNumber)
                ->first();

            if (! $visit instanceof PatientVisit) {
                throw ValidationException::withMessages([
                    'visit_number' => 'No matching visit was found for this patient in the active branch.',
                ]);
            }
        }

        $action->handle(
            patient: $patient,
            branchId: $branch->id,
            amount: round($this->validatedFloat($request, 'amount'), 2),
            paymentMethodId: $this->validatedString($request, 'payment_method_id'),
            visit: $visit,
            referenceNumber: $request->string('reference_number')->toString() ?: null,
            notes: $request->string('notes')->toString() ?: null,
        );

        return to_route('finance.deposits.index')->with('success', 'Deposit recorded successfully.');
    }

    public function apply(ApplyBillingDepositRequest $request, BillingDeposit $deposit, ApplyBillingDeposit $action): RedirectResponse
    {
        $this->activeBranchWorkspace->authorizeModel($deposit);

        $visit = PatientVisit::query()
            ->where('tenant_id', $deposit->tenant_id)
            ->where('facility_branch_id', $deposit->facility_branch_id)
            ->where('patient_id', $deposit->patient_id)
            ->where('visit_number', $request->string('visit_number')->toString())
            ->with('billing')
            ->first();

        if (! $visit instanceof PatientVisit || ! $visit->billing instanceof VisitBilling) {
            throw ValidationException::withMessages([
                'visit_number' => 'No billed visit was found for this deposit application.',
            ]);
        }

        $action->handle(
            deposit: $deposit,
            billing: $visit->billing,
            amount: round($this->validatedFloat($request, 'amount'), 2),
            notes: $request->string('notes')->toString() ?: null,
        );

        return to_route('finance.deposits.index')->with('success', 'Deposit applied successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDeposit(BillingDeposit $deposit): array
    {
        $availableAmount = round((float) $deposit->amount - (float) $deposit->applied_amount - (float) $deposit->refunded_amount, 2);

        return [
            'id' => $deposit->id,
            'deposit_number' => $deposit->deposit_number,
            'patient_name' => $deposit->patient === null ? 'Unknown patient' : mb_trim(sprintf('%s %s', $deposit->patient->first_name, $deposit->patient->last_name)),
            'patient_number' => $deposit->patient?->patient_number,
            'visit_number' => $deposit->visit?->visit_number,
            'payment_method' => $deposit->payment_method,
            'reference_number' => $deposit->reference_number,
            'amount' => round((float) $deposit->amount, 2),
            'applied_amount' => round((float) $deposit->applied_amount, 2),
            'refunded_amount' => round((float) $deposit->refunded_amount, 2),
            'available_amount' => $availableAmount,
            'status' => $this->backedEnumValue($deposit->status, $deposit->getAttribute('status')),
            'received_at' => $deposit->received_at?->toISOString(),
            'notes' => $deposit->notes,
        ];
    }

    private function backedEnumValue(mixed $value, mixed $fallback): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($fallback) ? $fallback : '';
    }

    private function validatedFloat(StoreBillingDepositRequest|ApplyBillingDepositRequest $request, string $key): float
    {
        $value = $request->validated($key);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function validatedString(StoreBillingDepositRequest|ApplyBillingDepositRequest $request, string $key): string
    {
        $value = $request->validated($key);

        return is_string($value) ? $value : '';
    }
}
