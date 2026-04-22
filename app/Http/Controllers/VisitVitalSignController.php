<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateVitalSign;
use App\Http\Requests\StoreVitalSignRequest;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

final readonly class VisitVitalSignController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:triage.update', only: ['store']),
        ];
    }

    public function store(
        StoreVitalSignRequest $request,
        PatientVisit $visit,
        CreateVitalSign $createVitalSign,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $redirectTo = $request->input('redirect_to') === 'triage' ? 'triage' : 'visit';
        $triage = $visit->triage;
        if ($triage === null) {
            return $this->redirect($visit, $redirectTo)->with('error', 'Record triage before adding vital signs.');
        }

        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirect($visit, $redirectTo)->with('error', 'Your user account is not linked to a staff profile.');
        }

        $createVitalSign->handle($visit, $request->createDto());

        return $this->redirect($visit, $redirectTo)->with('success', 'Vital signs recorded successfully.');
    }

    private function redirect(PatientVisit $visit, string $redirectTo): RedirectResponse
    {
        return $redirectTo === 'triage'
            ? to_route('triage.show', $visit)
            : to_route('visits.show', $visit);
    }
}
