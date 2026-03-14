<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateImagingRequest;
use App\Http\Requests\StoreConsultationImagingRequest;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;

final class DoctorConsultationImagingRequestController
{
    public function store(
        StoreConsultationImagingRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateImagingRequest $createImagingRequest,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId();
        $consultationAccess->authorizeVisit($visit, $staffId);

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Start the consultation note before ordering imaging.');
        }

        if ($consultation->isCompleted()) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'This consultation has already been finalized and can no longer accept imaging orders.');
        }

        $createImagingRequest->handle($consultation, $request->validated(), $staffId);

        return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'imaging'])
            ->with('success', 'Imaging request created successfully.');
    }
}
