<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityServiceOrder;
use App\Http\Requests\StoreConsultationFacilityServiceOrderRequest;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;

final class DoctorConsultationFacilityServiceOrderController
{
    public function store(
        StoreConsultationFacilityServiceOrderRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateFacilityServiceOrder $createFacilityServiceOrder,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId();
        $consultationAccess->authorizeVisit($visit, $staffId);

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Start the consultation note before ordering facility services.');
        }

        if ($consultation->isCompleted()) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'This consultation has already been finalized and can no longer accept facility service orders.');
        }

        $createFacilityServiceOrder->handle($consultation, $request->validated(), $staffId);

        return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
            ->with('success', 'Facility service order created successfully.');
    }
}
