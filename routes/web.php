<?php

declare(strict_types=1);

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AllergenController;
use App\Http\Controllers\AppointmentCategoryController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentModeController;
use App\Http\Controllers\BranchSwitcherController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CurrencyExchangeRateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DispensingController;
use App\Http\Controllers\DispensingHistoryController;
use App\Http\Controllers\DoctorConsultationController;
use App\Http\Controllers\DoctorConsultationFacilityServiceOrderController;
use App\Http\Controllers\DoctorConsultationImagingRequestController;
use App\Http\Controllers\DoctorConsultationLabRequestController;
use App\Http\Controllers\DoctorConsultationPrescriptionController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\DoctorScheduleExceptionController;
use App\Http\Controllers\FacilityBranchController;
use App\Http\Controllers\FacilityImpersonationController;
use App\Http\Controllers\FacilityManagerController;
use App\Http\Controllers\FacilityServiceController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\InsurancePackageController;
use App\Http\Controllers\InventoryDashboardController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryLocationController;
use App\Http\Controllers\InventoryMovementReportController;
use App\Http\Controllers\InventoryReconciliationController;
use App\Http\Controllers\InventoryRequisitionController;
use App\Http\Controllers\InventoryStockByLocationController;
use App\Http\Controllers\LaboratoryDashboardController;
use App\Http\Controllers\LaboratoryManagementController;
use App\Http\Controllers\LaboratoryQueueController;
use App\Http\Controllers\LaboratoryStockManagementController;
use App\Http\Controllers\LaboratoryWorklistController;
use App\Http\Controllers\LabRequestItemConsumableController;
use App\Http\Controllers\LabResultTypeController;
use App\Http\Controllers\LabResultWorkflowController;
use App\Http\Controllers\LabTestCatalogController;
use App\Http\Controllers\LabTestCategoryController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PatientAllergyController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientVisitController;
use App\Http\Controllers\PharmacyPosCartController;
use App\Http\Controllers\PharmacyPosCartHoldController;
use App\Http\Controllers\PharmacyPosController;
use App\Http\Controllers\PharmacyPosPaymentController;
use App\Http\Controllers\PharmacyPosSaleController;
use App\Http\Controllers\PharmacyPosSaleRefundController;
use App\Http\Controllers\PharmacyPosSaleVoidController;
use App\Http\Controllers\PharmacyPrescriptionController;
use App\Http\Controllers\PharmacyQueueController;
use App\Http\Controllers\Print\DispensingRecordPrintController;
use App\Http\Controllers\Print\GoodsReceiptPrintController;
use App\Http\Controllers\Print\InventoryRequisitionPrintController;
use App\Http\Controllers\Print\LabResultPrintController;
use App\Http\Controllers\Print\PharmacyPosSalePrintController;
use App\Http\Controllers\Print\PrescriptionPrintController;
use App\Http\Controllers\Print\VisitPaymentPrintController;
use App\Http\Controllers\Print\VisitSummaryPrintController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SpecimenTypeController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffPositionController;
use App\Http\Controllers\SubscriptionActivationController;
use App\Http\Controllers\SubscriptionPackageController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use App\Http\Controllers\VisitOrderController;
use App\Http\Controllers\VisitPaymentController;
use App\Http\Controllers\VisitTriageController;
use App\Http\Controllers\VisitVitalSignController;
use App\Http\Controllers\WorkspaceRegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user?->tenant !== null && ! $user->tenant->isOnboardingComplete()) {
            return to_route('onboarding.show');
        }

        return Inertia::render('modules');
    }

    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'ensure.active.branch'])->group(function (): void {
    Route::get('modules', fn () => Inertia::render('modules'))
        ->middleware('permission:dashboard.view')
        ->name('modules');
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::middleware('support.only')
        ->prefix('facility-manager')
        ->name('facility-manager.')
        ->group(function (): void {
            Route::get('impersonation', [FacilityImpersonationController::class, 'index'])->name('impersonation.index');
            Route::post('impersonation/users/{user}', [FacilityImpersonationController::class, 'start'])->name('impersonation.start');
            Route::get('dashboard', [FacilityManagerController::class, 'dashboard'])->name('dashboard');
            Route::get('facilities', [FacilityManagerController::class, 'index'])->name('facilities.index');
            Route::get('facilities/{tenant}', [FacilityManagerController::class, 'show'])->name('facilities.show');
            Route::get('facilities/{tenant}/branches', [FacilityManagerController::class, 'branches'])->name('facilities.branches');
            Route::get('facilities/{tenant}/users', [FacilityManagerController::class, 'users'])->name('facilities.users');
            Route::get('facilities/{tenant}/subscriptions', [FacilityManagerController::class, 'subscriptions'])->name('facilities.subscriptions');
            Route::get('facilities/{tenant}/activity', [FacilityManagerController::class, 'activity'])->name('facilities.activity');
            Route::get('facilities/{tenant}/support-notes', [FacilityManagerController::class, 'notes'])->name('facilities.notes');
            Route::post('facilities/{tenant}/support-notes', [FacilityManagerController::class, 'storeNote'])->name('facilities.notes.store');
            Route::post('facilities/{tenant}/activate-subscription', [FacilityManagerController::class, 'activateSubscription'])->name('facilities.activate-subscription');
            Route::post('facilities/{tenant}/mark-subscription-past-due', [FacilityManagerController::class, 'markSubscriptionPastDue'])->name('facilities.mark-subscription-past-due');
            Route::post('facilities/{tenant}/complete-onboarding', [FacilityManagerController::class, 'completeOnboarding'])->name('facilities.complete-onboarding');
            Route::post('facilities/{tenant}/reopen-onboarding', [FacilityManagerController::class, 'reopenOnboarding'])->name('facilities.reopen-onboarding');
        });

    Route::get('branch-switcher', [BranchSwitcherController::class, 'index'])->name('branch-switcher.index');
    Route::post('branch-switcher/{branchId}', [BranchSwitcherController::class, 'switch'])->name('branch-switcher.switch');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('allergens', AllergenController::class)->except(['show']);
    Route::resource('appointment-categories', AppointmentCategoryController::class)->except(['show']);
    Route::resource('appointment-modes', AppointmentModeController::class)->except(['show']);
    Route::resource('appointments/schedules', DoctorScheduleController::class)
        ->except(['show'])
        ->names('appointments.schedules');
    Route::resource('appointments/exceptions', DoctorScheduleExceptionController::class)
        ->except(['show'])
        ->names('appointments.exceptions');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/my', [AppointmentController::class, 'myAppointments'])->name('appointments.my');
    Route::get('appointments/queue', [AppointmentController::class, 'queue'])->name('appointments.queue');
    Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'markNoShow'])->name('appointments.no-show');
    Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::resource('addresses', AddressController::class)->except(['show']);
    Route::get('administration/general-settings', [AdministrationController::class, 'generalSettings'])->name('administration.general-settings');
    Route::patch('administration/general-settings', [AdministrationController::class, 'updateGeneralSettings'])->name('administration.general-settings.update');
    Route::get('administration/insurance-setup', [AdministrationController::class, 'insuranceSetup'])->name('administration.insurance-setup');
    Route::get('administration/master-data', [AdministrationController::class, 'masterData'])->name('administration.master-data');
    Route::get('administration/platform', [AdministrationController::class, 'platform'])->name('administration.platform');
    Route::resource('currencies', CurrencyController::class)->except(['show']);
    Route::get('currency-exchange-rates', [CurrencyExchangeRateController::class, 'index'])->name('currency-exchange-rates.index');
    Route::post('currency-exchange-rates', [CurrencyExchangeRateController::class, 'store'])->name('currency-exchange-rates.store');
    Route::delete('currency-exchange-rates/{currencyExchangeRate}', [CurrencyExchangeRateController::class, 'destroy'])->name('currency-exchange-rates.destroy');
    Route::resource('subscription-packages', SubscriptionPackageController::class)->except(['show']);
    Route::resource('staff-positions', StaffPositionController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('facility-branches', FacilityBranchController::class)->except(['show']);
    Route::resource('clinics', ClinicController::class)->except(['show']);
    Route::resource('insurance-companies', InsuranceCompanyController::class)->except(['show']);
    Route::resource('insurance-packages', InsurancePackageController::class)->except(['show']);
    Route::resource('units', UnitController::class)->except(['show']);
    Route::get('patients/returning', [PatientController::class, 'returning'])->name('patients.returning');
    Route::get('visits', [PatientVisitController::class, 'index'])->name('visits.index');
    Route::get('visits/{visit}', [PatientVisitController::class, 'show'])->name('visits.show');
    Route::get('visits/{visit}/summary/print', [VisitSummaryPrintController::class, 'show'])->name('visits.summary.print');
    Route::post('visits/{visit}/payments', [VisitPaymentController::class, 'store'])->name('visits.payments.store');
    Route::get('visits/{visit}/payments/{payment}/print', [VisitPaymentPrintController::class, 'show'])->name('visits.payments.print');
    Route::get('prescriptions/{prescription}/print', [PrescriptionPrintController::class, 'show'])->name('prescriptions.print');
    Route::post('visits/{visit}/lab-requests', [VisitOrderController::class, 'storeLabRequest'])->name('visits.lab-requests.store');
    Route::patch('visits/{visit}/lab-requests/{labRequest}', [VisitOrderController::class, 'updateLabRequest'])->name('visits.lab-requests.update');
    Route::delete('visits/{visit}/lab-requests/{labRequest}', [VisitOrderController::class, 'destroyLabRequest'])->name('visits.lab-requests.destroy');
    Route::delete('visits/{visit}/lab-requests/{labRequest}/items/{labRequestItem}', [VisitOrderController::class, 'destroyLabRequestItem'])->name('visits.lab-requests.items.destroy');
    Route::post('visits/{visit}/imaging-requests', [VisitOrderController::class, 'storeImagingRequest'])->name('visits.imaging-requests.store');
    Route::post('visits/{visit}/prescriptions', [VisitOrderController::class, 'storePrescription'])->name('visits.prescriptions.store');
    Route::post('visits/{visit}/facility-service-orders', [VisitOrderController::class, 'storeFacilityServiceOrder'])->name('visits.facility-service-orders.store');
    Route::patch('visits/{visit}/facility-service-orders/{facilityServiceOrder}', [VisitOrderController::class, 'updateFacilityServiceOrder'])->name('visits.facility-service-orders.update');
    Route::delete('visits/{visit}/facility-service-orders/{facilityServiceOrder}', [VisitOrderController::class, 'destroyFacilityServiceOrder'])->name('visits.facility-service-orders.destroy');
    Route::patch('visits/{visit}/status', [PatientVisitController::class, 'updateStatus'])->name('visits.update-status');
    Route::get('doctors/consultations', [DoctorConsultationController::class, 'index'])->name('doctors.consultations.index');
    Route::get('doctors/consultations/{visit}', [DoctorConsultationController::class, 'show'])->name('doctors.consultations.show');
    Route::post('doctors/consultations/{visit}', [DoctorConsultationController::class, 'store'])->name('doctors.consultations.store');
    Route::put('doctors/consultations/{visit}', [DoctorConsultationController::class, 'update'])->name('doctors.consultations.update');
    Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
    Route::get('triage/{visit}', [TriageController::class, 'show'])->name('triage.show');
    Route::post('doctors/consultations/{visit}/lab-requests', [DoctorConsultationLabRequestController::class, 'store'])->name('doctors.consultations.lab-requests.store');
    Route::post('doctors/consultations/{visit}/imaging-requests', [DoctorConsultationImagingRequestController::class, 'store'])->name('doctors.consultations.imaging-requests.store');
    Route::post('doctors/consultations/{visit}/prescriptions', [DoctorConsultationPrescriptionController::class, 'store'])->name('doctors.consultations.prescriptions.store');
    Route::post('doctors/consultations/{visit}/facility-service-orders', [DoctorConsultationFacilityServiceOrderController::class, 'store'])->name('doctors.consultations.facility-service-orders.store');
    Route::delete('doctors/consultations/{visit}/facility-service-orders/{facilityServiceOrder}', [DoctorConsultationFacilityServiceOrderController::class, 'destroy'])->name('doctors.consultations.facility-service-orders.destroy');
    Route::post('visits/{visit}/triage', [VisitTriageController::class, 'store'])->name('visits.triage.store');
    Route::post('visits/{visit}/vitals', [VisitVitalSignController::class, 'store'])->name('visits.vitals.store');
    Route::resource('patients', PatientController::class);
    Route::resource('staff', StaffController::class)->except(['show']);
    Route::get('inventory/dashboard', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard.index');
    Route::get('inventory/stock-by-location', [InventoryStockByLocationController::class, 'index'])->name('inventory.stock-by-location.index');
    Route::get('inventory/reports/movements', [InventoryMovementReportController::class, 'index'])->name('inventory.reports.movements.index');
    Route::resource('inventory-items', InventoryItemController::class);
    Route::resource('inventory-locations', InventoryLocationController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['destroy']);
    Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::resource('goods-receipts', GoodsReceiptController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('goods-receipts/{goods_receipt}/print', [GoodsReceiptPrintController::class, 'show'])->name('goods-receipts.print');
    Route::post('goods-receipts/{goods_receipt}/post', [GoodsReceiptController::class, 'post'])->name('goods-receipts.post');
    Route::resource('inventory-requisitions', InventoryRequisitionController::class)
        ->parameters(['inventory-requisitions' => 'requisition'])
        ->only(['index', 'show']);
    Route::get('inventory-requisitions/{requisition}/print', [InventoryRequisitionPrintController::class, 'show'])->name('inventory-requisitions.print');
    Route::post('inventory-requisitions/{requisition}/approve', [InventoryRequisitionController::class, 'approve'])->name('inventory-requisitions.approve');
    Route::post('inventory-requisitions/{requisition}/reject', [InventoryRequisitionController::class, 'reject'])->name('inventory-requisitions.reject');
    Route::post('inventory-requisitions/{requisition}/issue', [InventoryRequisitionController::class, 'issue'])->name('inventory-requisitions.issue');
    Route::get('stock-counts', fn () => to_route('reconciliations.index'));
    Route::get('stock-adjustments', fn () => to_route('reconciliations.index'));
    Route::resource('reconciliations', InventoryReconciliationController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('reconciliations/{reconciliation}/submit', [InventoryReconciliationController::class, 'submit'])->name('reconciliations.submit');
    Route::post('reconciliations/{reconciliation}/review', [InventoryReconciliationController::class, 'review'])->name('reconciliations.review');
    Route::post('reconciliations/{reconciliation}/approve', [InventoryReconciliationController::class, 'approve'])->name('reconciliations.approve');
    Route::post('reconciliations/{reconciliation}/reject', [InventoryReconciliationController::class, 'reject'])->name('reconciliations.reject');
    Route::post('reconciliations/{reconciliation}/post', [InventoryReconciliationController::class, 'post'])->name('reconciliations.post');
    Route::resource('facility-services', FacilityServiceController::class)->except(['show']);
    Route::resource('lab-test-categories', LabTestCategoryController::class)->except(['show']);
    Route::resource('specimen-types', SpecimenTypeController::class)->except(['show']);
    Route::resource('result-types', LabResultTypeController::class)->except(['show']);
    Route::resource('lab-test-catalogs', LabTestCatalogController::class)->except(['show']);
    Route::get('laboratory/dashboard', [LaboratoryDashboardController::class, 'index'])->name('laboratory.dashboard.index');
    Route::get('laboratory/stock-management', [LaboratoryStockManagementController::class, 'index'])->name('laboratory.stock-management.index');
    Route::get('laboratory/stock', [InventoryStockByLocationController::class, 'index'])->name('laboratory.stock.index');
    Route::get('laboratory/requisitions', [InventoryRequisitionController::class, 'index'])->name('laboratory.requisitions.index');
    Route::get('laboratory/requisitions/create', [InventoryRequisitionController::class, 'create'])->name('laboratory.requisitions.create');
    Route::post('laboratory/requisitions', [InventoryRequisitionController::class, 'store'])->name('laboratory.requisitions.store');
    Route::get('laboratory/requisitions/{requisition}', [InventoryRequisitionController::class, 'show'])->name('laboratory.requisitions.show');
    Route::get('laboratory/requisitions/{requisition}/print', [InventoryRequisitionPrintController::class, 'show'])->name('laboratory.requisitions.print');
    Route::post('laboratory/requisitions/{requisition}/submit', [InventoryRequisitionController::class, 'submit'])->name('laboratory.requisitions.submit');
    Route::post('laboratory/requisitions/{requisition}/cancel', [InventoryRequisitionController::class, 'cancel'])->name('laboratory.requisitions.cancel');
    Route::get('laboratory/movements', [InventoryMovementReportController::class, 'index'])->name('laboratory.movements.index');
    Route::get('laboratory/receipts', [GoodsReceiptController::class, 'index'])->name('laboratory.receipts.index');
    Route::get('laboratory/receipts/create', [GoodsReceiptController::class, 'create'])->name('laboratory.receipts.create');
    Route::post('laboratory/receipts', [GoodsReceiptController::class, 'store'])->name('laboratory.receipts.store');
    Route::get('laboratory/receipts/{goods_receipt}', [GoodsReceiptController::class, 'show'])->name('laboratory.receipts.show');
    Route::get('laboratory/receipts/{goods_receipt}/print', [GoodsReceiptPrintController::class, 'show'])->name('laboratory.receipts.print');
    Route::post('laboratory/receipts/{goods_receipt}/post', [GoodsReceiptController::class, 'post'])->name('laboratory.receipts.post');
    Route::get('laboratory/incoming-investigations', [LaboratoryQueueController::class, 'incoming'])->name('laboratory.incoming.index');
    Route::get('laboratory/enter-results', [LaboratoryQueueController::class, 'enterResults'])->name('laboratory.enter-results.index');
    Route::get('laboratory/review-results', [LaboratoryQueueController::class, 'reviewResults'])->name('laboratory.review-results.index');
    Route::get('laboratory/view-results', [LaboratoryQueueController::class, 'viewResults'])->name('laboratory.view-results.index');
    Route::get('laboratory/management', [LaboratoryManagementController::class, 'index'])->name('laboratory.management.index');
    Route::get('laboratory/worklist', [LaboratoryWorklistController::class, 'index'])->name('laboratory.worklist.index');
    Route::get('laboratory/request-items/{labRequestItem}', [LaboratoryWorklistController::class, 'show'])->name('laboratory.request-items.show');
    Route::post('laboratory/request-items/{labRequestItem}/collect-sample', [LabResultWorkflowController::class, 'collectSample'])->name('laboratory.request-items.collect-sample');
    Route::post('laboratory/request-items/{labRequestItem}/receive', [LabResultWorkflowController::class, 'receive'])->name('laboratory.request-items.receive');
    Route::post('laboratory/request-items/{labRequestItem}/results', [LabResultWorkflowController::class, 'store'])->name('laboratory.request-items.results.store');
    Route::post('laboratory/request-items/{labRequestItem}/correct', [LabResultWorkflowController::class, 'correct'])->name('laboratory.request-items.correct');
    Route::post('laboratory/request-items/{labRequestItem}/review', [LabResultWorkflowController::class, 'review'])->name('laboratory.request-items.review');
    Route::post('laboratory/request-items/{labRequestItem}/approve', [LabResultWorkflowController::class, 'approve'])->name('laboratory.request-items.approve');
    Route::get('laboratory/request-items/{labRequestItem}/print', [LabResultPrintController::class, 'show'])->name('laboratory.request-items.print');
    Route::get('laboratory/request-items/{labRequestItem}/consumables', [LabRequestItemConsumableController::class, 'show'])->name('laboratory.request-items.consumables.show');
    Route::post('laboratory/request-items/{labRequestItem}/consumables', [LabRequestItemConsumableController::class, 'store'])->name('laboratory.request-items.consumables.store');
    Route::delete('laboratory/request-items/{labRequestItem}/consumables/{labRequestItemConsumable}', [LabRequestItemConsumableController::class, 'destroy'])->name('laboratory.request-items.consumables.destroy');
    Route::get('pharmacy/stock', [InventoryStockByLocationController::class, 'index'])->name('pharmacy.stock.index');
    Route::get('pharmacy/queue', [PharmacyQueueController::class, 'index'])->name('pharmacy.queue.index');
    Route::get('pharmacy/prescriptions/{prescription}', [PharmacyPrescriptionController::class, 'show'])->name('pharmacy.prescriptions.show');
    Route::post('pharmacy/prescriptions/{prescription}/dispense', [DispensingController::class, 'dispense'])->name('pharmacy.prescriptions.dispense');
    Route::get('pharmacy/prescriptions/{prescription}/dispenses/create', [DispensingController::class, 'create'])->name('pharmacy.dispenses.create');
    Route::post('pharmacy/prescriptions/{prescription}/dispenses', [DispensingController::class, 'store'])->name('pharmacy.dispenses.store');
    Route::get('pharmacy/dispenses', [DispensingHistoryController::class, 'index'])->name('pharmacy.dispenses.index');
    Route::get('pharmacy/dispenses/export', [DispensingHistoryController::class, 'export'])->name('pharmacy.dispenses.export');
    Route::get('pharmacy/dispenses/{dispensingRecord}', [DispensingController::class, 'show'])->name('pharmacy.dispenses.show');
    Route::post('pharmacy/dispenses/{dispensingRecord}/post', [DispensingController::class, 'post'])->name('pharmacy.dispenses.post');
    Route::get('pharmacy/dispenses/{dispensingRecord}/print', [DispensingRecordPrintController::class, 'show'])->name('pharmacy.dispenses.print');
    Route::get('pharmacy/requisitions', [InventoryRequisitionController::class, 'index'])->name('pharmacy.requisitions.index');
    Route::get('pharmacy/requisitions/create', [InventoryRequisitionController::class, 'create'])->name('pharmacy.requisitions.create');
    Route::post('pharmacy/requisitions', [InventoryRequisitionController::class, 'store'])->name('pharmacy.requisitions.store');
    Route::get('pharmacy/requisitions/{requisition}', [InventoryRequisitionController::class, 'show'])->name('pharmacy.requisitions.show');
    Route::get('pharmacy/requisitions/{requisition}/print', [InventoryRequisitionPrintController::class, 'show'])->name('pharmacy.requisitions.print');
    Route::post('pharmacy/requisitions/{requisition}/submit', [InventoryRequisitionController::class, 'submit'])->name('pharmacy.requisitions.submit');
    Route::post('pharmacy/requisitions/{requisition}/cancel', [InventoryRequisitionController::class, 'cancel'])->name('pharmacy.requisitions.cancel');
    Route::get('pharmacy/movements', [InventoryMovementReportController::class, 'index'])->name('pharmacy.movements.index');
    Route::get('pharmacy/receipts', [GoodsReceiptController::class, 'index'])->name('pharmacy.receipts.index');
    Route::get('pharmacy/receipts/create', [GoodsReceiptController::class, 'create'])->name('pharmacy.receipts.create');
    Route::post('pharmacy/receipts', [GoodsReceiptController::class, 'store'])->name('pharmacy.receipts.store');
    Route::get('pharmacy/receipts/{goods_receipt}', [GoodsReceiptController::class, 'show'])->name('pharmacy.receipts.show');
    Route::get('pharmacy/receipts/{goods_receipt}/print', [GoodsReceiptPrintController::class, 'show'])->name('pharmacy.receipts.print');
    Route::post('pharmacy/receipts/{goods_receipt}/post', [GoodsReceiptController::class, 'post'])->name('pharmacy.receipts.post');
    Route::get('pharmacy/pos', [PharmacyPosController::class, 'index'])->name('pharmacy.pos.index');
    Route::post('pharmacy/pos', [PharmacyPosController::class, 'store'])->name('pharmacy.pos.store');
    Route::get('pharmacy/pos/history', [PharmacyPosSaleController::class, 'index'])->name('pharmacy.pos.history');
    Route::post('pharmacy/pos/carts/{cart}/items', [PharmacyPosCartController::class, 'store'])->name('pharmacy.pos.carts.items.store');
    Route::put('pharmacy/pos/carts/{cart}/items/{item}', [PharmacyPosCartController::class, 'update'])->name('pharmacy.pos.carts.items.update');
    Route::delete('pharmacy/pos/carts/{cart}/items/{item}', [PharmacyPosCartController::class, 'destroy'])->name('pharmacy.pos.carts.items.destroy');
    Route::post('pharmacy/pos/carts/{cart}/hold', [PharmacyPosCartHoldController::class, 'store'])->name('pharmacy.pos.carts.hold');
    Route::delete('pharmacy/pos/carts/{cart}/hold', [PharmacyPosCartHoldController::class, 'destroy'])->name('pharmacy.pos.carts.resume');
    Route::get('pharmacy/pos/carts/{cart}/checkout', [PharmacyPosSaleController::class, 'checkout'])->name('pharmacy.pos.carts.checkout');
    Route::post('pharmacy/pos/carts/{cart}/finalize', [PharmacyPosSaleController::class, 'store'])->name('pharmacy.pos.carts.finalize');
    Route::get('pharmacy/pos/sales/{sale}', [PharmacyPosSaleController::class, 'show'])->name('pharmacy.pos.sales.show');
    Route::get('pharmacy/pos/sales/{sale}/print', [PharmacyPosSalePrintController::class, 'show'])->name('pharmacy.pos.sales.print');
    Route::post('pharmacy/pos/sales/{sale}/void', [PharmacyPosSaleVoidController::class, 'store'])->name('pharmacy.pos.sales.void');
    Route::post('pharmacy/pos/sales/{sale}/refund', [PharmacyPosSaleRefundController::class, 'store'])->name('pharmacy.pos.sales.refund');
    Route::post('pharmacy/pos/sales/{sale}/payments', [PharmacyPosPaymentController::class, 'store'])->name('pharmacy.pos.sales.payments.store');
    Route::resource('patients.allergies', PatientAllergyController::class);
    Route::post('patients/{patient}/visits', [PatientVisitController::class, 'store'])->name('patients.visits.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('facility-manager/impersonation/stop', [FacilityImpersonationController::class, 'stop'])
        ->name('facility-manager.impersonation.stop');
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::patch('onboarding/profile', [OnboardingController::class, 'updateProfile'])->name('onboarding.profile.update');
    Route::post('onboarding/branch', [OnboardingController::class, 'storeBranch'])->name('onboarding.branch.store');
    Route::post('onboarding/departments', [OnboardingController::class, 'storeDepartments'])->name('onboarding.departments.store');
    Route::post('onboarding/staff', [OnboardingController::class, 'storeStaff'])->name('onboarding.staff.store');
    Route::get('subscription/activate', [SubscriptionActivationController::class, 'show'])->name('subscription.activate.show');
    Route::post('subscription/activate', [SubscriptionActivationController::class, 'store'])->name('subscription.activate.store');
    Route::get('subscription/checkout', [SubscriptionActivationController::class, 'checkout'])->name('subscription.checkout.show');
    Route::post('subscription/checkout/success', [SubscriptionActivationController::class, 'success'])->name('subscription.checkout.success');
    Route::post('subscription/checkout/failure', [SubscriptionActivationController::class, 'failure'])->name('subscription.checkout.failure');
    Route::delete('user-account', [UserController::class, 'destroyCurrentUser'])->name('user.destroy-account');
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    Route::get('create-workspace', [WorkspaceRegistrationController::class, 'create'])->name('workspace-register.create');
    Route::post('create-workspace', [WorkspaceRegistrationController::class, 'store'])->name('workspace-register.store');
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
