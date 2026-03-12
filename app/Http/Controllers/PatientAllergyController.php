<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientAllergyRequest;
use App\Http\Requests\UpdatePatientAllergyRequest;
use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PatientAllergyController
{
    public function index(Request $request, Patient $patient): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $allergies = $patient->allergies()
            ->with(['allergen:id,name', 'createdBy:id,first_name,last_name'])
            ->when(
                $search !== '',
                static fn ($query) => $query->where(
                    static fn ($searchQuery) => $searchQuery
                        ->where('notes', 'like', sprintf('%%%s%%', $search))
                        ->orWhereRelation('allergen', 'name', 'like', sprintf('%%%s%%', $search))
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('patient-allergy/index', [
            'patient' => $patient->only('id', 'patient_number', 'first_name', 'last_name'),
            'allergies' => $allergies,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Patient $patient): Response
    {
        return Inertia::render('patient-allergy/create', [
            'patient' => $patient->only('id', 'patient_number', 'first_name', 'last_name'),
        ]);
    }

    public function store(StorePatientAllergyRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $patient->allergies()->create($validated);

        return redirect()
            ->route('patients.allergies.index', $patient)
            ->with('success', 'Allergy recorded successfully');
    }

    public function edit(Patient $patient, PatientAllergy $patientAllergy): Response
    {
        $patientAllergy->load(['allergen:id,name', 'createdBy:id,first_name,last_name']);

        return Inertia::render('patient-allergy/edit', [
            'patient' => $patient->only('id', 'patient_number', 'first_name', 'last_name'),
            'allergy' => $patientAllergy,
        ]);
    }

    public function update(
        UpdatePatientAllergyRequest $request,
        Patient $patient,
        PatientAllergy $patientAllergy
    ): RedirectResponse {
        $validated = $request->validated();

        $patientAllergy->update($validated);

        return redirect()
            ->route('patients.allergies.index', $patient)
            ->with('success', 'Allergy updated successfully');
    }

    public function destroy(Patient $patient, PatientAllergy $patientAllergy): RedirectResponse
    {
        $patientAllergy->delete();

        return redirect()
            ->route('patients.allergies.index', $patient)
            ->with('success', 'Allergy deleted successfully');
    }

    public function toggleActive(Patient $patient, PatientAllergy $patientAllergy): RedirectResponse
    {
        $patientAllergy->update([
            'is_active' => !$patientAllergy->is_active,
        ]);

        $status = $patientAllergy->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('patients.allergies.index', $patient)
            ->with('success', "Allergy {$status} successfully");
    }
}
