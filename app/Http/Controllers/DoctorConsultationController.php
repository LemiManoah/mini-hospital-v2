<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompleteConsultation;
use App\Actions\CreateConsultation;
use App\Actions\UpdateConsultation;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\ConsultationOutcome;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Allergen;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use App\Support\DoctorConsultationAccess;
use App\Support\VisitOrderOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DoctorConsultationController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private VisitOrderOptions $visitOrderOptions,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:consultations.view', only: ['index', 'show']),
            new Middleware('permission:consultations.create', only: ['store']),
            new Middleware('permission:consultations.update', only: ['update']),
        ];
    }

    public function index(Request $request, DoctorConsultationAccess $consultationAccess): Response
    {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $search = mb_trim((string) $request->query('search', ''));
        $isPrivilegedUser = $consultationAccess->isPrivilegedUser($request->user());

        /** @var LengthAwarePaginator<int, PatientVisit> $visits */
        $visits = $this->activeBranchWorkspace->apply(PatientVisit::query())
            ->with([
                'patient:id,patient_number,first_name,last_name,middle_name,phone_number,gender',
                'clinic:id,clinic_name',
                'doctor:id,first_name,last_name',
                'triage:id,visit_id,triage_datetime,triage_grade,chief_complaint',
                'consultation:id,visit_id,doctor_id,started_at,completed_at,primary_diagnosis',
                'consultation.doctor:id,first_name,last_name',
            ])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('triage')
            ->when(
                ! $isPrivilegedUser,
                static function (Builder $query) use ($staffId): void {
                    $query->where(function (Builder $staffScopeQuery) use ($staffId): void {
                        $staffScopeQuery
                            ->where('doctor_id', $staffId)
                            ->orWhereNull('doctor_id')
                            ->orWhereHas('consultation', static function (Builder $consultationQuery) use ($staffId): void {
                                $consultationQuery->where('doctor_id', $staffId);
                            });
                    });
                }
            )
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where(
                        function (Builder $searchQuery) use ($search): void {
                            $searchQuery
                                ->where('visit_number', 'like', sprintf('%%%s%%', $search))
                                ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                                    $patientQuery
                                        ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                                        ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                                        ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                        ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search));
                                });
                        }
                    );
                }
            )
            ->latest('registered_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('doctor/consultations/index', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, PatientVisit $visit, DoctorConsultationAccess $consultationAccess): Response
    {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId);

        $visit->load([
            'patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number,email,blood_group,next_of_kin_name,next_of_kin_phone,address_id,country_id',
            'patient.address:id,city,district',
            'patient.country:id,country_name',
            'patient.activeAllergies.allergen:id,name',
            'branch:id,name',
            'clinic:id,clinic_name',
            'doctor:id,first_name,last_name',
            'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'payer.insuranceCompany:id,name',
            'payer.insurancePackage:id,name',
            'triage:id,visit_id,nurse_id,triage_datetime,triage_grade,attendance_type,news_score,pews_score,conscious_level,mobility_status,chief_complaint,history_of_presenting_illness,assigned_clinic_id,requires_priority,is_pediatric,poisoning_case,poisoning_agent,snake_bite_case,referred_by,nurse_notes',
            'triage.nurse:id,first_name,last_name',
            'triage.assignedClinic:id,clinic_name',
            'triage.vitalSigns' => static function (HasMany $query): void {
                $query->with(['recordedBy:id,first_name,last_name'])
                    ->latest('recorded_at');
            },
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,history_of_present_illness,review_of_systems,past_medical_history_summary,family_history,social_history,subjective_notes,objective_findings,assessment,plan,primary_diagnosis,primary_icd10_code,outcome,follow_up_instructions,follow_up_days,is_referred,referred_to_department,referred_to_facility,referral_reason',
            'consultation.doctor:id,first_name,last_name',
            'labRequests' => static function (HasMany $query): void {
                $query->with([
                    'requestedBy:id,first_name,last_name',
                    'items.test:id,test_name,test_code,lab_test_category_id,result_type_id',
                    'items.test.labCategory:id,name',
                    'items.test.specimenTypes:id,name',
                    'items.test.resultTypeDefinition:id,code,name',
                    'items.resultEntry:id,lab_request_item_id,approved_by,approved_at,released_at,result_notes',
                    'items.resultEntry.approvedBy:id,first_name,last_name',
                    'items.resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,reference_range,sort_order',
                ])
                    ->latest('request_date');
            },
            'imagingRequests' => static function (HasMany $query): void {
                $query->with([
                    'requestedBy:id,first_name,last_name',
                    'scheduledBy:id,first_name,last_name',
                ])
                    ->latest();
            },
            'prescriptions' => static function (HasMany $query): void {
                $query->with([
                    'prescribedBy:id,first_name,last_name',
                    'items.drug:id,generic_name,brand_name,strength,dosage_form',
                ])
                    ->latest('prescription_date');
            },
            'facilityServiceOrders' => static function (HasMany $query): void {
                $query->with([
                    'service:id,name,service_code,category,is_billable',
                    'orderedBy:id,first_name,last_name',
                    'performedBy:id,first_name,last_name',
                ])
                    ->latest('ordered_at');
            },
        ]);

        return Inertia::render('doctor/consultations/show', [
            'visit' => $visit,
            'activeTab' => $request->query('tab', 'overview'),
            'consultationOutcomes' => collect(ConsultationOutcome::cases())
                ->map(static fn (ConsultationOutcome $outcome): array => [
                    'value' => $outcome->value,
                    'label' => $outcome->label(),
                ])
                ->values()
                ->all(),
            'allergens' => Allergen::query()->orderBy('name')->get(['id', 'name', 'type']),
            'severityOptions' => collect(AllergySeverity::cases())->map(fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'reactionOptions' => collect(AllergyReaction::cases())->map(fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            ...$this->visitOrderOptions->forVisit($visit),
        ]);
    }

    public function store(
        StoreConsultationRequest $request,
        PatientVisit $visit,
        CreateConsultation $createConsultation,
        DoctorConsultationAccess $consultationAccess,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId);

        if ($visit->triage === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Record triage before starting a consultation.');
        }

        if ($visit->consultation()->exists()) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'This visit already has a consultation record.');
        }

        if ($staffId === null && $visit->doctor_id === null) {
            return to_route('doctors.consultations.show', $visit)
                ->with('error', 'Assign a doctor or link this user to a staff profile before starting the consultation.');
        }

        $createConsultation->handle($visit, $request->validated());

        return to_route('doctors.consultations.show', $visit)->with('success', 'Consultation started successfully.');
    }

    public function update(
        UpdateConsultationRequest $request,
        PatientVisit $visit,
        UpdateConsultation $updateConsultation,
        CompleteConsultation $completeConsultation,
        DoctorConsultationAccess $consultationAccess,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId);

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Start the consultation before updating it.');
        }

        if ($consultation->completed_at !== null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'This consultation has already been finalized.');
        }

        $validated = $request->validated();

        if (($validated['intent'] ?? 'save_draft') === 'complete') {
            $completeConsultation->handle($consultation, $validated);

            return to_route('doctors.consultations.show', $visit)->with('success', 'Consultation finalized successfully.');
        }

        $updateConsultation->handle($consultation, $validated);

        return to_route('doctors.consultations.show', $visit)->with('success', 'Consultation saved successfully.');
    }

}
