<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateLabOrder;
use App\Http\Requests\StoreConsultationLabOrder;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final class DoctorConsultationLabOrderController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:consultations.update', only: ['store']),
        ];
    }

    public function store(
        StoreConsultationLabOrder $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateLabOrder $createLabOrder,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId);

        if ($staffId === null) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'lab'])
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'lab'])
                ->with('error', 'Start the consultation note before ordering laboratory tests.');
        }

        if ($consultation->isCompleted()) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'lab'])
                ->with('error', 'This consultation has already been finalized and can no longer accept lab orders.');
        }

        $createLabOrder->handle($consultation, $request->createDto(), $staffId);

        return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'lab'])
            ->with('success', 'Laboratory request created successfully.');
    }
}
