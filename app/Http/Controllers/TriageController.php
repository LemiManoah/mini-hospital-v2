<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\MobilityStatus;
use App\Enums\TriageGrade;
use App\Models\Clinic;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final class TriageController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:triage.view', only: ['index', 'show']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        /** @var LengthAwarePaginator<int, PatientVisit> $visits */
        $visits = PatientVisit::query()
            ->with([
                'patient:id,patient_number,first_name,last_name,middle_name,phone_number,gender',
                'clinic:id,clinic_name',
                'doctor:id,first_name,last_name',
                'triage:id,visit_id,nurse_id,triage_datetime,triage_grade,chief_complaint',
                'triage.nurse:id,first_name,last_name',
            ])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('visit_number', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('patient', static function (Builder $patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search));
                            });
                    });
                }
            )
            ->latest('registered_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('triage/index', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(PatientVisit $visit): Response
    {
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
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,primary_diagnosis,plan',
            'consultation.doctor:id,first_name,last_name',
        ]);

        return Inertia::render('triage/show', [
            'visit' => $visit,
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

    /**
     * @param  array<int, object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return collect($cases)
            ->map(static fn (object $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])
            ->values()
            ->all();
    }
}
