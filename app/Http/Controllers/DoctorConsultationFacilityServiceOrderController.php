<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityServiceOrder;
use App\Actions\DeletePendingFacilityServiceOrder;
use App\Enums\FacilityServiceOrderStatus;
use App\Http\Requests\StoreConsultationFacilityServiceOrderRequest;
use App\Models\FacilityServiceOrder;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final class DoctorConsultationFacilityServiceOrderController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:consultations.update', only: ['store', 'destroy']),
        ];
    }

    public function store(
        StoreConsultationFacilityServiceOrderRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateFacilityServiceOrder $createFacilityServiceOrder,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId);

        if ($staffId === null) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
                ->with('error', 'Clinical service orders require a linked staff profile for audit tracking.');
        }

        $consultation = $visit->consultation;

        if ($consultation === null) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
                ->with('error', 'Start the consultation note before ordering facility services.');
        }

        if ($consultation->isCompleted()) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
                ->with('error', 'This consultation has already been finalized and can no longer accept facility service orders.');
        }

        $createFacilityServiceOrder->handle($consultation, $request->createDto(), $staffId);

        return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
            ->with('success', 'Facility service order created successfully.');
    }

    public function destroy(
        PatientVisit $visit,
        FacilityServiceOrder $facilityServiceOrder,
        DoctorConsultationAccess $consultationAccess,
        DeletePendingFacilityServiceOrder $deletePendingFacilityServiceOrder,
    ): RedirectResponse {
        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId);

        abort_unless($facilityServiceOrder->visit_id === $visit->id, 404);

        if ($facilityServiceOrder->status !== FacilityServiceOrderStatus::PENDING) {
            return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
                ->with('error', 'Only pending facility service orders can be removed.');
        }

        $deletePendingFacilityServiceOrder->handle($facilityServiceOrder);

        return to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services'])
            ->with('success', 'Facility service order removed successfully.');
    }
}
