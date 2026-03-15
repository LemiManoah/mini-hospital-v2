<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompleteConsultation;
use App\Actions\CreateConsultation;
use App\Actions\UpdateConsultation;
use App\Models\FacilityService;
use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\PregnancyStatus;
use App\Enums\Priority;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\Drug;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

final class DoctorConsultationController
{
    public function index(Request $request, DoctorConsultationAccess $consultationAccess): Response
    {
        $staffId = $consultationAccess->resolveStaffId();
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
        $staffId = $consultationAccess->resolveStaffId();
        $consultationAccess->authorizeVisit($visit, $staffId);

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
            'triage.vitalSigns' => static function (HasMany $query): void {
                $query->with(['recordedBy:id,first_name,last_name'])
                    ->latest('recorded_at');
            },
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,history_of_present_illness,review_of_systems,past_medical_history_summary,family_history,social_history,subjective_notes,objective_findings,assessment,plan,primary_diagnosis,primary_icd10_code,outcome,follow_up_instructions,follow_up_days,is_referred,referred_to_department,referred_to_facility,referral_reason',
            'consultation.doctor:id,first_name,last_name',
            'labRequests' => static function (HasMany $query): void {
                $query->with([
                    'requestedBy:id,first_name,last_name',
                    'items.test:id,test_name,test_code,category',
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
            'consultationOutcomes' => collect(Consultation::OUTCOMES)
                ->map(static fn (string $outcome): array => [
                    'value' => $outcome,
                    'label' => mb_convert_case(str_replace('_', ' ', $outcome), MB_CASE_TITLE),
                ])
                ->values()
                ->all(),
            'labTestOptions' => LabTestCatalog::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('test_name')
                ->get(['id', 'test_code', 'test_name', 'category', 'base_price'])
                ->map(static fn (LabTestCatalog $test): array => [
                    'id' => $test->id,
                    'test_code' => $test->test_code,
                    'test_name' => $test->test_name,
                    'category' => $test->category,
                    'base_price' => $test->base_price,
                ])
                ->all(),
            'drugOptions' => Drug::query()
                ->where('is_active', true)
                ->orderBy('generic_name')
                ->get(['id', 'generic_name', 'brand_name', 'strength', 'dosage_form'])
                ->map(static fn (Drug $drug): array => [
                    'id' => $drug->id,
                    'generic_name' => $drug->generic_name,
                    'brand_name' => $drug->brand_name,
                    'strength' => $drug->strength,
                    'dosage_form' => $drug->dosage_form->value,
                ])
                ->all(),
            'labPriorities' => collect(Priority::cases())
                ->map(static fn (Priority $priority): array => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                ])
                ->values()
                ->all(),
            'imagingModalities' => collect(ImagingModality::cases())
                ->map(static fn (ImagingModality $modality): array => [
                    'value' => $modality->value,
                    'label' => $modality->label(),
                ])
                ->values()
                ->all(),
            'imagingPriorities' => collect(ImagingPriority::cases())
                ->map(static fn (ImagingPriority $priority): array => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                ])
                ->values()
                ->all(),
            'imagingLateralities' => collect(ImagingLaterality::cases())
                ->map(static fn (ImagingLaterality $laterality): array => [
                    'value' => $laterality->value,
                    'label' => $laterality->label(),
                ])
                ->values()
                ->all(),
            'pregnancyStatuses' => collect(PregnancyStatus::cases())
                ->map(static fn (PregnancyStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
            'facilityServiceOptions' => FacilityService::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'service_code', 'name', 'category', 'department_name', 'default_instructions', 'is_billable'])
                ->map(static fn (FacilityService $service): array => [
                    'id' => $service->id,
                    'service_code' => $service->service_code,
                    'name' => $service->name,
                    'category' => $service->category->value,
                    'department_name' => $service->department_name,
                    'default_instructions' => $service->default_instructions,
                    'is_billable' => $service->is_billable,
                ])
                ->all(),
        ]);
    }

    public function store(
        StoreConsultationRequest $request,
        PatientVisit $visit,
        CreateConsultation $createConsultation,
        DoctorConsultationAccess $consultationAccess,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId();
        $consultationAccess->authorizeVisit($visit, $staffId);

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
        CompleteConsultation $completeConsultation,
        DoctorConsultationAccess $consultationAccess,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId();
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
