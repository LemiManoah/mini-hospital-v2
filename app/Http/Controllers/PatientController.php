<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePatient;
use App\Actions\DeletePatient;
use App\Actions\UpdatePatient;
use App\Http\Requests\DeletePatientRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Address;
use App\Models\Country;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PatientController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->with([
                'country:id,country_name',
                'primaryInsurance:id,patient_id,insurance_company_id,insurance_package_id',
                'primaryInsurance.insuranceCompany:id,name',
                'primaryInsurance.insurancePackage:id,name',
            ])
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

    public function create(): Response
    {
        return Inertia::render('patient/create', [
            'countries' => Country::query()->select('id', 'country_name')->orderBy('country_name')->get(),
            'addresses' => Address::query()->select('id', 'city', 'district')->orderBy('city')->get(),
            'companies' => InsuranceCompany::query()->select('id', 'name')->orderBy('name')->get(),
            'packages' => InsurancePackage::query()->select('id', 'name', 'insurance_company_id')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePatientRequest $request, CreatePatient $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('patients.index')->with('success', 'Patient registered successfully.');
    }

    public function edit(Patient $patient): Response
    {
        $patient->load([
            'primaryInsurance:id,patient_id,insurance_company_id,insurance_package_id',
        ]);

        return Inertia::render('patient/edit', [
            'patient' => $patient,
            'countries' => Country::query()->select('id', 'country_name')->orderBy('country_name')->get(),
            'addresses' => Address::query()->select('id', 'city', 'district')->orderBy('city')->get(),
            'companies' => InsuranceCompany::query()->select('id', 'name')->orderBy('name')->get(),
            'packages' => InsurancePackage::query()->select('id', 'name', 'insurance_company_id')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient, UpdatePatient $action): RedirectResponse
    {
        $action->handle($patient, $request->validated());

        return to_route('patients.index')->with('success', 'Patient updated successfully.');
    }

    public function destroy(DeletePatientRequest $request, Patient $patient, DeletePatient $action): RedirectResponse
    {
        $action->handle($patient);

        return to_route('patients.index')->with('success', 'Patient deleted successfully.');
    }
}
