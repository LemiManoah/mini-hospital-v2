<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeletePatient;
use App\Actions\RegisterPatientAndStartVisit;
use App\Actions\UpdatePatient;
use App\Data\Patient\CreatePatientRegistrationDTO;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\KinRelationship;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Enums\VisitType;
use App\Http\Requests\DeletePatientRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Address;
use App\Models\Allergen;
use App\Models\Clinic;
use App\Models\Country;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\Patient;
use App\Models\Staff;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PatientController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:patients.view', only: ['index', 'returning', 'show']),
            new Middleware('permission:patients.create', only: ['create', 'store']),
            new Middleware('permission:patients.update', only: ['edit', 'update']),
            new Middleware('permission:patients.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->with(['country:id,country_name'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where(
                    static fn (Builder $searchQuery) => $searchQuery
                        ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search))
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('patient/index', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function returning(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->with(['country:id,country_name', 'address'])
            ->whereHas('visits', static fn (Builder $query) => $query->where('status', 'completed'))
            ->withCount([
                'visits as completed_visits_count' => static fn (Builder $query) => $query->where('status', 'completed'),
            ])
            ->withMax([
                'visits as last_completed_visit_at' => static fn (Builder $query) => $query->where('status', 'completed'),
            ], 'completed_at')
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where(
                    static fn (Builder $searchQuery) => $searchQuery
                        ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search))
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('patient/returning', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
            ],
            ...$this->visitFormOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('patient/create', [
            'countries' => Country::query()->select('id', 'country_name')->orderBy('country_name')->get(),
            'addresses' => Address::query()->select('id', 'city', 'district')->orderBy('city')->get(),
            ...$this->visitFormOptions(),
            'genderOptions' => $this->enumOptions(Gender::cases()),
            'maritalStatusOptions' => $this->enumOptions(MaritalStatus::cases()),
            'bloodGroupOptions' => $this->enumOptions(BloodGroup::cases()),
            'religionOptions' => $this->enumOptions(Religion::cases()),
            'kinRelationshipOptions' => $this->enumOptions(KinRelationship::cases()),
        ]);
    }

    public function store(StorePatientRequest $request, RegisterPatientAndStartVisit $action): RedirectResponse
    {
        $dto = $request->createDto();

        if (! $this->usesActiveBranchAssignments($dto)) {
            return back()->with('error', 'Select a doctor and clinic from the active branch.');
        }

        $registration = $action->handle($dto);
        $patient = $registration['patient'];
        $visit = $registration['visit'];
        $redirectTo = $request->input('redirect_to', 'visit');

        if ($redirectTo === 'list') {
            return to_route('patients.index')->with('success', 'Patient registered and visit started successfully.');
        }

        if (in_array($redirectTo, ['show', 'patient'], true)) {
            return to_route('patients.show', $patient)->with('success', 'Patient registered and visit started successfully.');
        }

        return to_route('visits.show', $visit)->with('success', 'Patient registered and visit started successfully.');
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('patient/edit', [
            'patient' => $patient,
            'countries' => Country::query()->select('id', 'country_name')->orderBy('country_name')->get(),
            'addresses' => Address::query()->select('id', 'city', 'district')->orderBy('city')->get(),
            'genderOptions' => $this->enumOptions(Gender::cases()),
            'maritalStatusOptions' => $this->enumOptions(MaritalStatus::cases()),
            'bloodGroupOptions' => $this->enumOptions(BloodGroup::cases()),
            'religionOptions' => $this->enumOptions(Religion::cases()),
            'kinRelationshipOptions' => $this->enumOptions(KinRelationship::cases()),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient, UpdatePatient $action): RedirectResponse
    {
        $action->handle($patient, $request->updateDto());

        return to_route('patients.index')->with('success', 'Patient updated successfully.');
    }

    public function destroy(DeletePatientRequest $request, Patient $patient, DeletePatient $action): RedirectResponse
    {
        $action->handle($patient);

        return to_route('patients.index')->with('success', 'Patient deleted successfully.');
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'country:id,country_name',
            'address',
            'allergies:id,patient_id,allergen_id,reaction,severity,is_active',
            'allergies.allergen:id,name',
            'visits' => static function (HasMany $query): void {
                $query->with([
                    'clinic:id,clinic_name',
                    'doctor:id,first_name,last_name',
                    'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
                    'payer.insuranceCompany:id,name',
                    'payer.insurancePackage:id,name',
                ])
                    ->latest()
                    ->limit(10);
            },
        ]);

        $stats = [
            'total_visits' => $patient->visits()->count(),
            'completed_visits' => $patient->visits()->where('status', 'completed')->count(),
            'emergency_visits' => $patient->visits()->where('is_emergency', true)->count(),
            'last_visit' => $patient->visits()->latest('created_at')->first()?->created_at,
        ];

        return Inertia::render('patient/show', [
            'patient' => $patient,
            'stats' => $stats,
            'allergens' => Allergen::query()->orderBy('name')->get(['id', 'name', 'type']),
            'severityOptions' => collect(AllergySeverity::cases())->map(fn (AllergySeverity $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'reactionOptions' => collect(AllergyReaction::cases())->map(fn (AllergyReaction $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            ...$this->visitFormOptions(),
            'hasActiveVisit' => $patient->visits()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists(),
        ]);
    }

    /**
     * @param  array<int, Gender|MaritalStatus|BloodGroup|Religion|KinRelationship>  $cases
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

    /**
     * @return array{companies: Collection<int, InsuranceCompany>, packages: Collection<int, InsurancePackage>, clinics: Collection<int, Clinic>, doctors: Collection<int, Staff>, visitTypes: Collection<int, array{value: string, label: string}>}
     */
    private function visitFormOptions(): array
    {
        return [
            'companies' => InsuranceCompany::query()->select('id', 'name')->orderBy('name')->get(),
            'packages' => InsurancePackage::query()->select('id', 'name', 'insurance_company_id')->orderBy('name')->get(),
            'clinics' => Clinic::query()
                ->select('id')
                ->selectRaw('clinic_name as name')
                ->where('branch_id', BranchContext::getActiveBranchId())
                ->orderBy('clinic_name')
                ->get(),
            'doctors' => Staff::query()
                ->select('id', 'first_name', 'last_name')
                ->where('type', 'medical')
                ->whereHas('branches', function (Builder $query): void {
                    $query->where('facility_branches.id', BranchContext::getActiveBranchId());
                })
                ->orderBy('first_name')
                ->get(),
            'visitTypes' => collect(VisitType::cases())->map(fn (VisitType $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
        ];
    }

    private function usesActiveBranchAssignments(CreatePatientRegistrationDTO $dto): bool
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        if (! is_string($activeBranchId) || $activeBranchId === '') {
            return false;
        }

        $clinicId = $dto->clinicId;
        if (
            is_string($clinicId)
            && $clinicId !== ''
            && ! Clinic::query()
                ->whereKey($clinicId)
                ->where('branch_id', $activeBranchId)
                ->exists()
        ) {
            return false;
        }

        $doctorId = $dto->doctorId;

        return ! is_string($doctorId)
            || $doctorId === ''
            || Staff::query()
                ->whereKey($doctorId)
                ->whereHas('branches', function (Builder $query) use ($activeBranchId): void {
                    $query->where('facility_branches.id', $activeBranchId);
                })
                ->exists();
    }
}
