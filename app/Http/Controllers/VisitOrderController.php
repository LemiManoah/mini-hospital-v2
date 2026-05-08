<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityServiceOrder;
use App\Actions\CreateImagingOrder;
use App\Actions\CreateLabOrder;
use App\Actions\CreatePrescription;
use App\Actions\DeletePendingFacilityServiceOrder;
use App\Actions\DeletePendingLabOrder;
use App\Actions\DeletePendingLabOrderItem;
use App\Actions\UpdateFacilityServiceOrder;
use App\Actions\UpdateLabOrder;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\LabOrderItemStatus;
use App\Enums\LabOrderStatus;
use App\Http\Requests\StoreConsultationFacilityServiceOrderRequest;
use App\Http\Requests\StoreConsultationImagingOrder;
use App\Http\Requests\StoreConsultationLabOrder;
use App\Http\Requests\StoreConsultationPrescriptionRequest;
use App\Models\FacilityServiceOrder;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
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
                'storeLabOrder',
                'updateLabOrder',
                'destroyLabOrder',
                'destroyLabOrderItem',
                'storeImagingOrder',
                'storePrescription',
                'storeFacilityServiceOrder',
                'updateFacilityServiceOrder',
                'destroyFacilityServiceOrder',
            ]),
        ];
    }

    public function storeLabOrder(
        StoreConsultationLabOrder $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateLabOrder $createLabOrder,
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

        $createLabOrder->handle($visit, $request->createDto(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request created successfully.');
    }

    public function updateLabOrder(
        StoreConsultationLabOrder $request,
        PatientVisit $visit,
        LabOrder $labOrder,
        DoctorConsultationAccess $consultationAccess,
        UpdateLabOrder $updateLabOrder,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        abort_unless($labOrder->visit_id === $visit->id, 404);

        if ($this->consultationIsFinalized($visit)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'This visit already has a finalized consultation and can no longer accept order changes.');
        }

        if (! $this->labOrderIsEditable($labOrder)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Only pending lab orders can be updated.');
        }

        $updateLabOrder->handle($labOrder, $request->updateDto());

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request updated successfully.');
    }

    public function destroyLabOrder(
        Request $request,
        PatientVisit $visit,
        LabOrder $labOrder,
        DoctorConsultationAccess $consultationAccess,
        DeletePendingLabOrder $deletePendingLabOrder,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        abort_unless($labOrder->visit_id === $visit->id, 404);

        if (! $this->labOrderIsEditable($labOrder)) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Only pending lab orders can be removed.');
        }

        $deletePendingLabOrder->handle($labOrder);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory request removed successfully.');
    }

    public function destroyLabOrderItem(
        Request $request,
        PatientVisit $visit,
        LabOrder $labOrder,
        LabOrderItem $labOrderItem,
        DoctorConsultationAccess $consultationAccess,
        DeletePendingLabOrderItem $deletePendingLabOrderItem,
    ): RedirectResponse {
        $staffId = $this->resolveStaffId($visit, $consultationAccess, $request->input('redirect_to'));
        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Clinical lab orders require a linked staff profile for audit tracking.');
        }

        abort_unless($labOrder->visit_id === $visit->id, 404);
        abort_unless($labOrderItem->lab_order_id === $labOrder->id, 404);

        if (! $this->labOrderIsEditable($labOrder) || $labOrderItem->status !== LabOrderItemStatus::PENDING) {
            return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
                ->with('error', 'Only pending lab tests can be removed one by one.');
        }

        $deletePendingLabOrderItem->handle($labOrderItem);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'lab')
            ->with('success', 'Laboratory test removed successfully.');
    }

    public function storeImagingOrder(
        StoreConsultationImagingOrder $request,
        PatientVisit $visit,
        DoctorConsultationAccess $consultationAccess,
        CreateImagingOrder $createImagingOrder,
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

        $createImagingOrder->handle($visit, $request->createDto(), $staffId);

        return $this->redirectWithTab($visit, $request->input('redirect_to'), 'imaging')
            ->with('success', 'Imaging order created successfully.');
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

    private function labOrderIsEditable(LabOrder $labOrder): bool
    {
        return $labOrder->status === LabOrderStatus::REQUESTED
            && $labOrder->items()->where('status', '!=', 'pending')->doesntExist();
    }

    private function redirectWithTab(PatientVisit $visit, mixed $redirectTo, string $tab): RedirectResponse
    {
        return ($redirectTo ?? 'visit') === 'consultation'
            ? to_route('doctors.consultations.show', ['visit' => $visit, 'tab' => $tab])
            : to_route('visits.show', ['visit' => $visit, 'tab' => 'clinical', 'clinical_tab' => $tab]);
    }
}
