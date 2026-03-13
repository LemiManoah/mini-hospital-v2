<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransitionPatientVisitStatus;
use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class PatientVisitController
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $hasActiveVisit = $patient->visits()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActiveVisit) {
            return back()->with('error', 'Patient already has an active visit. Please complete or cancel the existing visit first.');
        }

        $validated = $request->validate([
            'visit_type' => 'required|string',
            'clinic_id' => 'nullable|uuid|exists:clinics,id',
            'doctor_id' => 'nullable|uuid|exists:staff,id',
            'is_emergency' => 'nullable|boolean',
            'billing_type' => ['required', Rule::in(['cash', 'insurance'])],
            'insurance_company_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_companies,id'],
            'insurance_package_id' => ['nullable', 'required_if:billing_type,insurance', 'uuid', 'exists:insurance_packages,id'],
        ]);

        DB::transaction(static function () use ($patient, $validated): void {
            $activeBranch = BranchContext::getActiveBranch();
            $prefix = $activeBranch ? strtoupper(mb_substr($activeBranch->name, 0, 3)) : 'VIS';
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

            $visit = PatientVisit::create([
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

            VisitPayer::create([
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
        });

        return to_route('patients.show', $patient->id)->with('success', 'Visit started successfully.');
    }

    public function markInProgress(PatientVisit $visit, TransitionPatientVisitStatus $action): JsonResponse
    {
        $visit = $action->handle($visit, VisitStatus::IN_PROGRESS);

        return response()->json([
            'status' => $visit->status->value,
            'started_at' => $visit->started_at,
        ]);
    }
}
