<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTriageRecord;
use App\Http\Requests\StoreTriageRecordRequest;
use App\Models\PatientVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class VisitTriageController
{
    public function store(
        StoreTriageRecordRequest $request,
        PatientVisit $visit,
        CreateTriageRecord $createTriage,
    ): RedirectResponse {
        if ($visit->triage()->exists()) {
            return to_route('visits.show', $visit)->with('error', 'This visit already has a triage record.');
        }

        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return to_route('visits.show', $visit)->with('error', 'Your user account is not linked to a staff profile.');
        }

        $createTriage->handle($visit, $request->validated());

        return to_route('visits.show', $visit)->with('success', 'Triage recorded successfully.');
    }
}
