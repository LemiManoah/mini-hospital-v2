<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityServiceOrder;
use App\Actions\CreateImagingRequest;
use App\Actions\CreateLabRequest;
use App\Actions\CreatePrescription;
use App\Actions\DeletePendingFacilityServiceOrder;
use App\Enums\FacilityServiceOrderStatus;
use App\Http\Requests\StoreConsultationFacilityServiceOrderRequest;
use App\Http\Requests\StoreConsultationImagingRequest;
use App\Http\Requests\StoreConsultationLabRequest;
use App\Http\Requests\StoreConsultationPrescriptionRequest;
use App\Models\FacilityServiceOrder;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

final class VisitOrderController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:consultations.update', only: [
                'storeLabRequest',
                'storeImagingRequest',
                'storePrescription',
                'storeFacilityServiceOrder',
                'destroyFacilityServiceOrder',
            ]),
        ];
    }

    public function storeLabRequest(
        StoreConsultationLabRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateLabRequest $createLabRequest,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept new orders.');
        }

        $createLabRequest->handle($visit, $request->validated(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request created successfully.');
    }

    public function storeImagingRequest(
        StoreConsultationImagingRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateImagingRequest $createImagingRequest,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'imaging')
                ->with('error', 'Clinical imaging orders require a linked staff profile for audit tracking.');
        }

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'imaging')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept new orders.');
        }

        $createImagingRequest->handle($visit, $request->validated(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'imaging')
            ->with('success', 'Imaging request created successfully.');
    }

    public function storePrescription(
        StoreConsultationPrescriptionRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreatePrescription $createPrescription,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'prescriptions')
                ->with('error', 'Clinical prescriptions require a linked staff profile for audit tracking.');
        }

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'prescriptions')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept new orders.');
        }

        $createPrescription->handle($visit, $request->validated(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'prescriptions')
            ->with('success', 'Prescription created successfully.');
    }

    public function storeFacilityServiceOrder(
        StoreConsultationFacilityServiceOrderRequest $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateFacilityServiceOrder $createFacilityServiceOrder,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
                ->with('error', 'Clinical service orders require a linked staff profile for audit tracking.');
        }

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept new orders.');
        }

        $createFacilityServiceOrder->handle($visit, $request->validated(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
            ->with('success', 'Facility service order created successfully.');
    }

    public function destroyFacilityServiceOrder(
        Request $request,
        PatientVisit $visit,
        FacilityServiceOrder $facilityServiceOrder,
        DoctorConsultationAccess $consultationAccess,
        DeletePendingFacilityServiceOrder $deletePendingFacilityServiceOrder,
    ): RedirectResponse {
        $this->validateRedirectTarget($request->input('redirect_to'));

        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId, requireTriage: false);

        abort_unless($facilityServiceOrder->visit_id === $visit->id, 404);

        if ($facilityServiceOrder->status !== FacilityServiceOrderStatus::PENDING) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
                ->with('error', 'Only pending facility service orders can be removed.');
        }

        $deletePendingFacilityServiceOrder->handle($facilityServiceOrder);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
            ->with('success', 'Facility service order removed successfully.');
    }

    private function resolveStaffId(
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        mixed $redirectTo,
    ): ?string {
        $this->validateRedirectTarget($redirectTo);

        $staffId = $consultationAccess->resolveStaffId(allowPrivilegedWithoutStaff: true);
        $consultationAccess->authorizeVisit($visit, $staffId, requireTriage: false);

        return $staffId;
    }

    private function consultationIsFinalized(PatientVisit $visit): bool
    {
        return $visit->consultation?->isCompleted() ?? false;
    }

    private function validateRedirectTarget(mixed $redirectTo): void
    {
        validator(
            ['redirect_to' => $redirectTo ?? 'visit'],
            ['redirect_to' => ['nullable', Rule::in(['visit', 'consultation'])]],
        )->validate();
    }

    private function redirectWithTab(PatientVisit $visit, mixed $redirectTo, string $tab): RedirectResponse
    {
        return ($redirectTo ?? 'visit') === 'consultation'
            ? to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => $tab])
            : to_route('visits.show', ['visit' => $visit, 'tab' => 'clinical', 'clinical_tab' => $tab]);
    }
}
