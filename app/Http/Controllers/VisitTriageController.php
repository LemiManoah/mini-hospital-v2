<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTriageRecord;
use App\Http\Requests\StoreTriageRecordRequest;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

final readonly class VisitTriageController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:triage.create', only: ['store']),
        ];
    }

    public function store(
        StoreTriageRecordRequest $request,
        PatientVisit $visit,
        CreateTriageRecord $createTriage,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $redirectTo = $request->input('redirect_to') === 'triage' ? 'triage' : 'visit';

        if ($visit->triage()->exists()) {
            return $this->redirect($visit, $redirectTo)->with('error', 'This visit already has a triage record.');
        }

        $staffId = Auth::user()?->staff_id;
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirect($visit, $redirectTo)->with('error', 'Your user account is not linked to a staff profile.');
        }

        $createTriage->handle($visit, $request->createDto());

        return $this->redirect($visit, $redirectTo)->with('success', 'Triage recorded successfully.');
    }

    private function redirect(PatientVisit $visit, string $redirectTo): RedirectResponse
    {
        return $redirectTo === 'triage'
            ? to_route('triage.show', $visit)
            : to_route('visits.show', $visit);
    }
}
