<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssessPatientVisitCompletion;
use App\Actions\TransitionPatientVisitStatus;
use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\MobilityStatus;
use App\Enums\PayerType;
use App\Enums\TriageGrade;
use App\Enums\VisitStatus;
use App\Models\Clinic;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PatientVisitController
{
    public function index(Request $request, AssessPatientVisitCompletion $assessment): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        /** @var LengthAwarePaginator<int, PatientVisit> $visits */
        $visits = PatientVisit::query()
            ->with([
                'patient:id,patient_number,first_name,last_name,middle_name,phone_number',
                'clinic:id,clinic_name',
                'doctor:id,first_name,last_name',
                'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
                'payer.insuranceCompany:id,name',
                'payer.insurancePackage:id,name',
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
                'completion_check' => $assessment->handle($visit),
            ]);

        return Inertia::render('visit/active', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(PatientVisit $visit, AssessPatientVisitCompletion $assessment): Response
    {
        $visit->load([
            'patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number,email,blood_group,next_of_kin_name,next_of_kin_phone,address_id,country_id',
            'patient.address:id,city,district',
            'patient.country:id,country_name',
            'branch:id,name',
            'clinic:id,clinic_name',
            'doctor:id,first_name,last_name',
            'registeredBy:id,staff_id,email',
            'registeredBy.staff:id,first_name,last_name',
            'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'payer.insuranceCompany:id,name',
            'payer.insurancePackage:id,name',
            'triage:id,visit_id,nurse_id,triage_datetime,triage_grade,attendance_type,news_score,pews_score,conscious_level,mobility_status,chief_complaint,history_of_presenting_illness,assigned_clinic_id,requires_priority,is_pediatric,poisoning_case,poisoning_agent,snake_bite_case,referred_by,nurse_notes',
            'triage.nurse:id,first_name,last_name',
            'triage.assignedClinic:id,clinic_name',
            'triage.vitalSigns' => static fn ($query) => $query
                ->with(['recordedBy:id,first_name,last_name'])
                ->latest('recorded_at'),
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,history_of_present_illness,review_of_systems,past_medical_history_summary,family_history,social_history,subjective_notes,objective_findings,assessment,plan,primary_diagnosis,primary_icd10_code',
            'consultation.doctor:id,first_name,last_name',
        ]);

        return Inertia::render('visit/show', [
            'visit' => $visit,
            'availableTransitions' => $this->availableTransitions($visit),
            'completionCheck' => $assessment->handle($visit),
            'triageGrades' => $this->enumOptions(TriageGrade::cases()),
            'attendanceTypes' => $this->enumOptions(AttendanceType::cases()),
            'consciousLevels' => $this->enumOptions(ConsciousLevel::cases()),
            'mobilityStatuses' => $this->enumOptions(MobilityStatus::cases()),
            'clinics' => Clinic::query()->select('id')->selectRaw('clinic_name as name')->orderBy('clinic_name')->get(),
            'temperatureUnits' => [
                ['value' => 'celsius', 'label' => 'Celsius'],
                ['value' => 'fahrenheit', 'label' => 'Fahrenheit'],
            ],
            'bloodGlucoseUnits' => [
                ['value' => 'mg_dl', 'label' => 'mg/dL'],
                ['value' => 'mmol_l', 'label' => 'mmol/L'],
            ],
        ]);
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
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

        $visit = DB::transaction(static function () use ($patient, $validated): PatientVisit {
            $activeBranch = BranchContext::getActiveBranch();
            $prefix = $activeBranch instanceof FacilityBranch ? mb_strtoupper(mb_substr($activeBranch->name, 0, 3)) : 'VIS';
            $userId = Auth::id();

            $latest = PatientVisit::query()
                ->where('visit_number', 'like', sprintf('%s-%%', $prefix))
                ->lockForUpdate()
                ->latest('visit_number')
                ->value('visit_number');

            $nextNumber = 1;
            if (is_string($latest) && preg_match('/^(?<prefix>[A-Z]+)-(?<num>\d+)$/', $latest, $matches) === 1) {
                $nextNumber = ((int) $matches['num']) + 1;
            }

            $visit = PatientVisit::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'facility_branch_id' => $activeBranch?->id,
                'visit_number' => sprintf('%s-%06d', $prefix, $nextNumber),
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

            VisitPayer::query()->create([
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
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'completed', 'cancelled'])],
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

    public function markInProgress(PatientVisit $visit, TransitionPatientVisitStatus $action): JsonResponse
    {
        $visit = $action->handle($visit, VisitStatus::IN_PROGRESS);

        return response()->json([
            'status' => $visit->status->value,
            'started_at' => $visit->started_at,
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableTransitions(PatientVisit $visit): array
    {
        return match ($visit->status) {
            VisitStatus::REGISTERED => [
                ['value' => VisitStatus::IN_PROGRESS->value, 'label' => 'Mark In Progress'],
                ['value' => VisitStatus::CANCELLED->value, 'label' => 'Cancel Visit'],
            ],
            VisitStatus::IN_PROGRESS, VisitStatus::AWAITING_PAYMENT => [
                ['value' => VisitStatus::COMPLETED->value, 'label' => 'Complete Visit'],
                ['value' => VisitStatus::CANCELLED->value, 'label' => 'Cancel Visit'],
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
        return collect($cases)
            ->map(static fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])
            ->values()
            ->all();
    }

    private function statusRedirect(PatientVisit $visit, string $redirectTo): RedirectResponse
    {
        return $redirectTo === 'index'
            ? to_route('visits.index')
            : to_route('visits.show', $visit);
    }
}
