<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordVisitPayment;
use App\Http\Requests\StoreVisitPaymentRequest;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class VisitPaymentController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments.create', only: ['store']),
        ];
    }

    public function store(
        StoreVisitPaymentRequest $request,
        PatientVisit $visit,
        RecordVisitPayment $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $action->handle($visit, $request->createDto());

        return to_route('visits.show', $visit)->with('success', 'Payment recorded successfully.');
    }
}
