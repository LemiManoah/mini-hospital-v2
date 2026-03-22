<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordVisitPayment;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        Request $request,
        PatientVisit $visit,
        RecordVisitPayment $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $action->handle($visit, $validated);

        return to_route('visits.show', $visit)->with('success', 'Payment recorded successfully.');
    }
}
