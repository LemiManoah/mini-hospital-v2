<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityServiceOrder;
use App\Actions\CreateImagingRequest;
use App\Actions\CreateLabRequest;
use App\Actions\CreatePrescription;
use App\Actions\DeletePendingFacilityServiceOrder;
use App\Actions\DeletePendingLabRequest;
use App\Actions\DeletePendingLabRequestItem;
use App\Actions\UpdateFacilityServiceOrder;
use App\Actions\UpdateLabRequest;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\LabRequestItemStatus;
use App\Enums\LabRequestStatus;
use App\Http\Requests\StoreConsultationFacilityServiceOrderRequest;
use App\Http\Requests\StoreConsultationImagingRequest;
use App\Http\Requests\StoreConsultationLabRequest;
use App\Http\Requests\StoreConsultationPrescriptionRequest;
use App\Models\FacilityServiceOrder;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\PatientVisit;
use App\Support\DoctorConsultationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

final readonly class VisitOrderController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:consultations.update', only: [
                'storeLabRequest',
                'updateLabRequest',
                'destroyLabRequest',
                'destroyLabRequestItem',
                'storeImagingRequest',
                'storePrescription',
                'storeFacilityServiceOrder',
                'updateFacilityServiceOrder',
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

        $createLabRequest->handle($visit, $request->createDto(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request created successfully.');
    }

    public function updateLabRequest(
        StoreConsultationLabRequest $request,
        PatientVisit $visit,
        LabRequest $labRequest,
        DoctorConsultationAccess $consultationAccess,
        UpdateLabRequest $updateLabRequest,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        abort_unless($labRequest->visit_id === $visit->id, 404);

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept order changes.');
        }

        if (! $this->labRequestIsEditable($labRequest)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Only pending lab requests can be updated.');
        }

        $updateLabRequest->handle($labRequest, $request->updateDto());

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request updated successfully.');
    }

    public function destroyLabRequest(
        Request $request,
        PatientVisit $visit,
        LabRequest $labRequest,
        DoctorConsultationAccess $consultationAccess,
        DeletePendingLabRequest $deletePendingLabRequest,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        abort_unless($labRequest->visit_id === $visit->id, 404);

        if (! $this->labRequestIsEditable($labRequest)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Only pending lab requests can be removed.');
        }

        $deletePendingLabRequest->handle($labRequest);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request removed successfully.');
    }

    public function destroyLabRequestItem(
        Request $request,
        PatientVisit $visit,
        LabRequest $labRequest,
        LabRequestItem $labRequestItem,
        DoctorConsultationAccess $consultationAccess,
        DeletePendingLabRequestItem $deletePendingLabRequestItem,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        abort_unless($labRequest->visit_id === $visit->id, 404);
        abort_unless($labRequestItem->request_id === $labRequest->id, 404);

        if (! $this->labRequestIsEditable($labRequest) || $labRequestItem->status !== LabRequestItemStatus::PENDING) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Only pending lab tests can be removed one by one.');
        }

        $deletePendingLabRequestItem->handle($labRequestItem);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory test removed successfully.');
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

        $createImagingRequest->handle($visit, $request->createDto(), $staffId);

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

        $createPrescription->handle($visit, $request->createDto(), $staffId);

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

        $createFacilityServiceOrder->handle($visit, $request->createDto(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
            ->with('success', 'Facility service order created successfully.');
    }

    public function updateFacilityServiceOrder(
        StoreConsultationFacilityServiceOrderRequest $request,
        PatientVisit $visit,
        FacilityServiceOrder $facilityServiceOrder,
        DoctorConsultationAccess $consultationAccess,
        UpdateFacilityServiceOrder $updateFacilityServiceOrder,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
                ->with('error', 'Clinical service orders require a linked staff profile for audit tracking.');
        }

        abort_unless($facilityServiceOrder->visit_id === $visit->id, 404);

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept order changes.');
        }

        if ($facilityServiceOrder->status !== FacilityServiceOrderStatus::PENDING) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
                ->with('error', 'Only pending facility service orders can be updated.');
        }

        $updateFacilityServiceOrder->handle($facilityServiceOrder, $request->updateDto());

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'services')
            ->with('success', 'Facility service order updated successfully.');
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

    private function labRequestIsEditable(LabRequest $labRequest): bool
    {
        return $labRequest->status === LabRequestStatus::REQUESTED
            && $labRequest->items()->where('status', '!=', 'pending')->doesntExist();
    }

    private function redirectWithTab(PatientVisit $visit, mixed $redirectTo, string $tab): RedirectResponse
    {
        return ($redirectTo ?? 'visit') === 'consultation'
            ? to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => $tab])
            : to_route('visits.show', ['visit' => $visit, 'tab' => 'clinical', 'clinical_tab' => $tab]);
    }
}
