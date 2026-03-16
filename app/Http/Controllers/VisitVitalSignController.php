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
        $redirectTo = $request->input('redirect_to') === 'triage' ? 'triage' : 'visit';
        $triage = $visit->triage;
        if ($triage === null) {
            return $this->redirect($visit, $redirectTo)->with('error', 'Record triage before adding vital signs.');
        }

        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirect($visit, $redirectTo)->with('error', 'Your user account is not linked to a staff profile.');
        }

        $createVitalSign->handle($visit, $request->validated());

        return $this->redirect($visit, $redirectTo)->with('success', 'Vital signs recorded successfully.');
    }

    private function redirect(PatientVisit $visit, string $redirectTo): RedirectResponse
    {
        return $redirectTo === 'triage'
            ? to_route('triage.show', $visit)
            : to_route('visits.show', $visit);
    }
}
