<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateVitalSign;
use App\Http\Requests\StoreVitalSignRequest;
use App\Models\PatientVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class VisitVitalSignController
{
    public function store(
        StoreVitalSignRequest $request,
        PatientVisit $visit,
        CreateVitalSign $createVitalSign,
    ): RedirectResponse {
        $triage = $visit->triage;
        if ($triage === null) {
            return to_route('visits.show', $visit)->with('error', 'Record triage before adding vital signs.');
        }

        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return to_route('visits.show', $visit)->with('error', 'Your user account is not linked to a staff profile.');
        }

        $createVitalSign->handle($visit, $request->validated());

        return to_route('visits.show', $visit)->with('success', 'Vital signs recorded successfully.');
    }
}
