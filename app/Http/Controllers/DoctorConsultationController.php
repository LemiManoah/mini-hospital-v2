<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateConsultation;
use App\Actions\UpdateConsultation;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class DoctorConsultationController
{
    public function index(Request $request): Response
    {
        $staffId = Auth::user()?->staff_id;
        abort_if(! is_string($staffId) || $staffId === '', 403, 'Your user account is not linked to a staff profile.');

        $search = mb_trim((string) $request->query('search', ''));

        /** @var LengthAwarePaginator<int, PatientVisit> $visits */
        $visits = PatientVisit::query()
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
            ->where(function (Builder $query) use ($staffId): void {
                $query
                    ->where('doctor_id', $staffId)
                    ->orWhereNull('doctor_id')
                    ->orWhereHas('consultation', static function (Builder $consultationQuery) use ($staffId): void {
                        $consultationQuery->where('doctor_id', $staffId);
                    });
            })
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
            ->withQueryString();

        return Inertia::render('doctor/consultations/index', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(PatientVisit $visit): Response
    {
        $staffId = Auth::user()?->staff_id;
        abort_if(! is_string($staffId) || $staffId === '', 403, 'Your user account is not linked to a staff profile.');
        abort_unless($this->canAccessVisit($visit, $staffId), 403, 'You do not have access to this consultation workspace.');

        $visit->load([
            'patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number,email,blood_group,next_of_kin_name,next_of_kin_phone,address_id,country_id',
            'patient.address:id,city,district',
            'patient.country:id,country_name',
            'branch:id,name',
            'clinic:id,clinic_name',
            'doctor:id,first_name,last_name',
            'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'payer.insuranceCompany:id,name',
            'payer.insurancePackage:id,name',
            'triage:id,visit_id,nurse_id,triage_datetime,triage_grade,attendance_type,news_score,pews_score,conscious_level,mobility_status,chief_complaint,history_of_presenting_illness,assigned_clinic_id,requires_priority,is_pediatric,poisoning_case,poisoning_agent,snake_bite_case,referred_by,nurse_notes',
            'triage.nurse:id,first_name,last_name',
            'triage.assignedClinic:id,clinic_name',
            'triage.vitalSigns' => static fn ($query) => $query
                ->with(['recordedBy:id,first_name,last_name'])
                ->latest('recorded_at'),
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,history_of_presenting_illness,review_of_systems,past_medical_history_summary,family_history,social_history,subjective_notes,objective_findings,assessment,plan,primary_diagnosis,primary_icd10_code',
            'consultation.doctor:id,first_name,last_name',
        ]);

        return Inertia::render('doctor/consultations/show', [
            'visit' => $visit,
        ]);
    }

    public function store(
        StoreConsultationRequest $request,
        PatientVisit $visit,
        CreateConsultation $createConsultation,
    ): RedirectResponse {
        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return to_route('doctors.consultations.index')->with('error', 'Your user account is not linked to a staff profile.');
        }

        abort_unless($this->canAccessVisit($visit, $staffId), 403, 'You do not have access to this consultation workspace.');

        if ($visit->triage === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Record triage before starting a consultation.');
        }

        if ($visit->consultation()->exists()) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'This visit already has a consultation record.');
        }

        $createConsultation->handle($visit, $request->validated());

        return to_route('doctors.consultations.show', $visit)->with('success', 'Consultation started successfully.');
    }

    public function update(
        UpdateConsultationRequest $request,
        PatientVisit $visit,
        UpdateConsultation $updateConsultation,
    ): RedirectResponse {
        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return to_route('doctors.consultations.index')->with('error', 'Your user account is not linked to a staff profile.');
        }

        abort_unless($this->canAccessVisit($visit, $staffId), 403, 'You do not have access to this consultation workspace.');

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Start the consultation before updating it.');
        }

        $updateConsultation->handle($consultation, $request->validated());

        return to_route('doctors.consultations.show', $visit)->with('success', 'Consultation saved successfully.');
    }

    private function canAccessVisit(PatientVisit $visit, string $staffId): bool
    {
        if ($visit->status->value === 'completed' || $visit->status->value === 'cancelled') {
            return false;
        }

        if (! $visit->triage()->exists()) {
            return false;
        }

        if ($visit->doctor_id === $staffId || $visit->doctor_id === null) {
            return true;
        }

        return $visit->consultation()
            ->where('doctor_id', $staffId)
            ->exists();
    }
}
