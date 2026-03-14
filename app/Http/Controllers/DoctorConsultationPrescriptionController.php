<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePrescription;
use App\Http\Requests\StoreConsultationPrescriptionRequest;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;

final class DoctorConsultationPrescriptionController
{
    public function store(
        StoreConsultationPrescriptionRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreatePrescription $createPrescription,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId();
        $consultationAccess->authorizeVisit($visit, $staffId);

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'Start the consultation note before writing prescriptions.');
        }

        if ($consultation->isCompleted()) {
            return to_route('doctors.consultations.show', $visit)->with('error', 'This consultation has already been finalized and can no longer accept prescriptions.');
        }

        $createPrescription->handle($consultation, $request->validated(), $staffId);

        return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'prescriptions'])
            ->with('success', 'Prescription created successfully.');
    }
}
