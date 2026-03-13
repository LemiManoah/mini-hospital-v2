<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartPatientVisit;
use App\Actions\TransitionPatientVisitStatus;
use App\Enums\VisitStatus;
use App\Http\Requests\StorePatientVisitRequest;
use App\Models\Patient;
use App\Models\PatientVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class PatientVisitController
{
    public function store(StorePatientVisitRequest $request, Patient $patient, StartPatientVisit $action): RedirectResponse
    {
        $hasActiveVisit = $patient->visits()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActiveVisit) {
            return back()->with('error', 'Patient already has an active visit. Please complete or cancel the existing visit first.');
        }

        $action->handle($patient, $request->validated());

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
