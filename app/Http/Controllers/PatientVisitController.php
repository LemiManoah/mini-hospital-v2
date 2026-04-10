<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssessPatientVisitCompletion;
use App\Actions\EnsureVisitBilling;
use App\Actions\RecalculateVisitBilling;
use App\Actions\TransitionPatientVisitStatus;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\ImagingRequestStatus;
use App\Enums\LabRequestStatus;
use App\Enums\MobilityStatus;
use App\Enums\PayerType;
use App\Enums\PrescriptionStatus;
use App\Enums\TriageGrade;
use App\Enums\VisitStatus;
use App\Models\Allergen;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Staff;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use App\Support\BranchScopedNumberGenerator;
use App\Support\VisitOrderOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PatientVisitController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private VisitOrderOptions $visitOrderOptions,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['index', 'show']),
            new Middleware('permission:visits.create', only: ['store']),
            new Middleware('permission:visits.update', only: ['updateStatus']),
        ];
    }

    public function index(Request $request, AssessPatientVisitCompletion $assessment): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        /** @var LengthAwarePaginator<int, PatientVisit> $visits */
        $visits = $this->activeBranchWorkspace->apply(PatientVisit::query())
            ->with([
                'patient:id,patient_number,first_name,last_name,middle_name,phone_number',
                'clinic:id,clinic_name',
                'doctor:id,first_name,last_name',
                'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
                'payer.insuranceCompany:id,name',
                'payer.insurancePackage:id,name',
                'billing:id,patient_visit_id,balance_amount',
                'triage:id,visit_id',
                'consultation:id,visit_id,completed_at',
            ])
            ->withCount([
                'labRequests as pending_lab_requests_count' => static fn (Builder $query) => $query->whereNotIn('status', [
                    LabRequestStatus::COMPLETED->value,
                    LabRequestStatus::CANCELLED->value,
                    LabRequestStatus::REJECTED->value,
                ]),
                'imagingRequests as pending_imaging_requests_count' => static fn (Builder $query) => $query->whereNotIn('status', [
                    ImagingRequestStatus::COMPLETED->value,
                    ImagingRequestStatus::CANCELLED->value,
                ]),
                'prescriptions as pending_prescriptions_count' => static fn (Builder $query) => $query->whereNotIn('status', [
                    PrescriptionStatus::FULLY_DISPENSED->value,
                    PrescriptionStatus::CANCELLED->value,
                ]),
                'facilityServiceOrders as pending_facility_service_orders_count' => static fn (Builder $query) => $query->whereNotIn('status', [
                    FacilityServiceOrderStatus::COMPLETED->value,
                    FacilityServiceOrderStatus::CANCELLED->value,
                ]),
            ])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('visit_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                            $patientQuery
                                ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search));
                        });
                })
            )
            ->latest('registered_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (PatientVisit $visit): array => [
                ...$visit->toArray(),
                'completion_check' => $assessment->handleLoaded($visit),
            ]);

        return Inertia::render('visit/active', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, PatientVisit $visit, AssessPatientVisitCompletion $assessment): Response
    {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $billing = resolve(EnsureVisitBilling::class)->handle($visit);
        resolve(RecalculateVisitBilling::class)->handle($billing);

        $visit->load([
            'patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number,email,blood_group,next_of_kin_name,next_of_kin_phone,address_id,country_id',
            'patient.address:id,city,district',
            'patient.country:id,country_name',
            'patient.activeAllergies.allergen:id,name',
            'branch:id,name',
            'clinic:id,clinic_name',
            'doctor:id,first_name,last_name',
            'registeredBy:id,staff_id,email',
            'registeredBy.staff:id,first_name,last_name',
            'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'payer.insuranceCompany:id,name',
            'payer.insurancePackage:id,name',
            'billing:id,patient_visit_id,visit_payer_id,payer_type,gross_amount,discount_amount,paid_amount,balance_amount,status,billed_at,settled_at',
            'billing.payments' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'receipt_number', 'payment_date', 'amount', 'payment_method', 'reference_number', 'is_refund', 'notes')
                ->latest('payment_date'),
            'charges' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'source_type', 'source_id', 'charge_code', 'description', 'quantity', 'unit_price', 'line_total', 'status', 'charged_at')
                ->latest('charged_at'),
            'triage:id,visit_id,nurse_id,triage_datetime,triage_grade,attendance_type,news_score,pews_score,conscious_level,mobility_status,chief_complaint,history_of_presenting_illness,assigned_clinic_id,requires_priority,is_pediatric,poisoning_case,poisoning_agent,snake_bite_case,referred_by,nurse_notes',
            'triage.nurse:id,first_name,last_name',
            'triage.assignedClinic:id,clinic_name',
            'triage.vitalSigns' => static fn (HasMany $query): HasMany => $query
                ->with(['recordedBy:id,first_name,last_name'])
                ->latest('recorded_at'),
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,history_of_present_illness,review_of_systems,past_medical_history_summary,family_history,social_history,subjective_notes,objective_findings,assessment,plan,primary_diagnosis,primary_icd10_code',
            'consultation.doctor:id,first_name,last_name',
            'labRequests' => static fn (HasMany $query): HasMany => $query
                ->with([
                    'requestedBy:id,first_name,last_name',
                    'items.test:id,test_name,test_code,lab_test_category_id,result_type_id',
                    'items.test.labCategory:id,name',
                    'items.test.specimenTypes:id,name',
                    'items.test.resultTypeDefinition:id,code,name',
                    'items.resultEntry:id,lab_request_item_id,approved_by,approved_at,released_at,result_notes',
                    'items.resultEntry.approvedBy:id,first_name,last_name',
                    'items.resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,reference_range,sort_order',
                ])
                ->latest('request_date'),
            'imagingRequests' => static fn (HasMany $query): HasMany => $query
                ->with([
                    'requestedBy:id,first_name,last_name',
                    'scheduledBy:id,first_name,last_name',
                ])
                ->latest(),
            'prescriptions' => static fn (HasMany $query): HasMany => $query
                ->with([
                    'prescribedBy:id,first_name,last_name',
                    'items.inventoryItem:id,generic_name,brand_name,strength,dosage_form',
                ])
                ->latest('prescription_date'),
            'facilityServiceOrders' => static fn (HasMany $query): HasMany => $query
                ->with([
                    'service:id,name,service_code,category,selling_price,is_billable',
                    'orderedBy:id,first_name,last_name',
                    'performedBy:id,first_name,last_name',
                ])
                ->latest('ordered_at'),
        ]);

        $this->hideUnreleasedLabResults($visit);

        return Inertia::render('visit/show', [
            'visit' => $visit,
            'activeTab' => $request->query('tab', 'overview'),
            'activeClinicalTab' => $request->query('clinical_tab', 'lab'),
            'completionCheck' => $assessment->handle($visit),
            'triageGrades' => $this->enumOptions(TriageGrade::cases()),
            'attendanceTypes' => $this->enumOptions(AttendanceType::cases()),
            'consciousLevels' => $this->enumOptions(ConsciousLevel::cases()),
            'mobilityStatuses' => $this->enumOptions(MobilityStatus::cases()),
            'clinics' => Clinic::query()
                ->select('id')
                ->selectRaw('clinic_name as name')
                ->where('branch_id', BranchContext::getActiveBranchId())
                ->orderBy('clinic_name')
                ->get(),
            'temperatureUnits' => [
                ['value' => 'celsius', 'label' => 'Celsius'],
                ['value' => 'fahrenheit', 'label' => 'Fahrenheit'],
            ],
            'bloodGlucoseUnits' => [
                ['value' => 'mg_dl', 'label' => 'mg/dL'],
                ['value' => 'mmol_l', 'label' => 'mmol/L'],
            ],
            'paymentMethods' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'card', 'label' => 'Card'],
                ['value' => 'mobile_money', 'label' => 'Mobile Money'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
            ],
            'allergens' => Allergen::query()->orderBy('name')->get(['id', 'name', 'type']),
            'severityOptions' => collect(AllergySeverity::cases())->map(fn (AllergySeverity $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'reactionOptions' => collect(AllergyReaction::cases())->map(fn (AllergyReaction $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            ...$this->visitOrderOptions->forVisit($visit),
        ]);
    }

    public function store(
        Request $request,
        Patient $patient,
        BranchScopedNumberGenerator $numberGenerator,
    ): RedirectResponse {
        $hasActiveVisit = $patient->visits()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActiveVisit) {
            return back()->with('error', 'Patient already has an active visit. Please complete or cancel the existing visit first.');
        }

        $validated = $request->validate([
            'visit_type' => ['required', 'string'],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:staff,id'],
            'is_emergency' => ['nullable', 'boolean'],
            'billing_type' => ['required', Rule::in(['cash', 'insurance'])],
            'insurance_company_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_companies,id'],
            'insurance_package_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_packages,id'],
            'redirect_to' => ['nullable', Rule::in(['patient', 'visit', 'index'])],
        ]);

        if (
            isset($validated['clinic_id'])
            && $validated['clinic_id'] !== null
            && ! Clinic::query()
                ->whereKey($validated['clinic_id'])
                ->where('branch_id', BranchContext::getActiveBranchId())
                ->exists()
        ) {
            return back()->with('error', 'The selected clinic is not available in the active branch.');
        }

        if (
            isset($validated['doctor_id'])
            && is_string($validated['doctor_id'])
            && $validated['doctor_id'] !== ''
            && ! Staff::query()
                ->whereKey($validated['doctor_id'])
                ->whereHas('branches', function (Builder $query): void {
                    $query->where('facility_branches.id', BranchContext::getActiveBranchId());
                })
                ->exists()
        ) {
            return back()->with('error', 'The selected doctor is not available in the active branch.');
        }

        $visit = DB::transaction(function () use ($patient, $validated, $numberGenerator): PatientVisit {
            $activeBranch = BranchContext::getActiveBranch();
            $userId = Auth::id();

            $visit = PatientVisit::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'facility_branch_id' => $activeBranch?->id,
                'visit_number' => $numberGenerator->nextVisitNumber($activeBranch?->name),
                'visit_type' => $validated['visit_type'],
                'status' => VisitStatus::REGISTERED,
                'clinic_id' => $validated['clinic_id'] ?? null,
                'doctor_id' => $validated['doctor_id'] ?? null,
                'is_emergency' => ! empty($validated['is_emergency']),
                'registered_at' => now(),
                'registered_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $payer = VisitPayer::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_visit_id' => $visit->id,
                'billing_type' => $validated['billing_type'],
                'insurance_company_id' => $validated['billing_type'] === PayerType::INSURANCE->value
                    ? $validated['insurance_company_id']
                    : null,
                'insurance_package_id' => $validated['billing_type'] === PayerType::INSURANCE->value
                    ? $validated['insurance_package_id']
                    : null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            VisitBilling::query()->create([
                'tenant_id' => $patient->tenant_id,
                'facility_branch_id' => $activeBranch?->id,
                'patient_visit_id' => $visit->id,
                'visit_payer_id' => $payer->id,
                'payer_type' => $payer->billing_type,
                'insurance_company_id' => $payer->insurance_company_id,
                'insurance_package_id' => $payer->insurance_package_id,
            ]);

            return $visit;
        });

        $message = 'Visit started successfully.';
        $redirectTo = $validated['redirect_to'] ?? 'patient';

        return match ($redirectTo) {
            'visit' => to_route('visits.show', $visit)->with('success', $message),
            'index' => to_route('visits.index')->with('success', $message),
            default => to_route('patients.show', $patient->id)->with('success', $message),
        };
    }

    public function updateStatus(
        Request $request,
        PatientVisit $visit,
        TransitionPatientVisitStatus $action,
        AssessPatientVisitCompletion $assessment,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed'])],
            'redirect_to' => ['nullable', Rule::in(['show', 'index'])],
        ]);

        $redirectTo = $validated['redirect_to'] ?? 'show';

        $allowedStatuses = collect($this->availableTransitions($visit))
            ->pluck('value')
            ->all();

        if (! in_array($validated['status'], $allowedStatuses, true)) {
            return $this->statusRedirect($visit, $redirectTo)
                ->with('error', 'That status change is not allowed for the current visit state.');
        }

        if ($validated['status'] === VisitStatus::COMPLETED->value) {
            $completionCheck = $assessment->handle($visit);

            if ($completionCheck['can_complete'] === false) {
                $message = $completionCheck['blocking_reasons'][0] ?? 'This visit cannot be completed yet.';

                return $this->statusRedirect($visit, $redirectTo)->with('error', $message);
            }
        }

        $action->handle($visit, VisitStatus::from($validated['status']));

        return $this->statusRedirect($visit, $redirectTo)->with('success', 'Visit status updated successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableTransitions(PatientVisit $visit): array
    {
        return match ($visit->status) {
            VisitStatus::IN_PROGRESS, VisitStatus::AWAITING_PAYMENT => [
                ['value' => VisitStatus::COMPLETED->value, 'label' => 'Complete Visit'],
            ],
            default => [],
        };
    }

    /**
     * @param  array<int, object{value: string, label: string}>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        $options = [];

        foreach ($cases as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $options;
    }

    private function statusRedirect(PatientVisit $visit, string $redirectTo): RedirectResponse
    {
        return $redirectTo === 'index'
            ? to_route('visits.index')
            : to_route('visits.show', $visit);
    }

    private function hideUnreleasedLabResults(PatientVisit $visit): void
    {
        $visit->labRequests?->each(function (mixed $labRequest): void {
            $labRequest->items?->each(function (mixed $item): void {
                if ($item->result_visible) {
                    return;
                }

                $item->setRelation('resultEntry', null);
            });
        });
    }
}
