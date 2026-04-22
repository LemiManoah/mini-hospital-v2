# DTO Rollout Plan

## Goal

Move business-action inputs away from loose `array` payloads and toward explicit DTOs so request data is defined, normalized, and statically analyzable.

From this point forward:

- new business workflows should use DTOs
- any touched complex request-to-action boundary should be migrated to DTOs
- validation remains in `FormRequest`
- normalization and casting happen before the DTO is created
- actions should accept DTOs instead of raw `validated()` arrays

## Conventions

### Namespace and location

- DTOs live in `app/Data`
- group nested DTOs by domain, for example:
  - `App\Data\Inventory\CreateGoodsReceiptDTO`
  - `App\Data\Inventory\CreateGoodsReceiptItemDTO`

### Construction rules

- use `final readonly class`
- top-level request DTOs should expose a named constructor such as `fromRequest()`
- nested child DTOs should expose payload-oriented constructors such as `fromPayload()`
- normalize blank strings to `null` where the domain expects nullable text
- use nested DTO collections for nested payloads instead of nested arrays
- create and edit payloads must use separate classes
- prefer explicit names such as `CreatePrescriptionDTO` and `UpdatePrescriptionDTO`

### Request rules

- `FormRequest` stays responsible for:
  - validation
  - `prepareForValidation()`
  - custom validator hooks
- each complex request should expose an operation-specific helper such as `createDto()` or `updateDto()` that returns the DTO
- controllers should pass DTOs to actions instead of using `validated()` directly

### Action rules

- actions should accept a DTO as the main payload argument
- actions may still derive authenticated user, tenant, branch, or route-model context outside the DTO when that context is not request-owned
- actions should use DTO properties directly, not array offsets

## Implemented

- Goods receipts
  - `app/Http/Requests/StoreGoodsReceiptRequest.php`
  - `app/Http/Controllers/GoodsReceiptController.php`
  - `app/Actions/CreateGoodsReceipt.php`
  - `app/Data/Inventory/CreateGoodsReceiptDTO.php`
  - `app/Data/Inventory/CreateGoodsReceiptItemDTO.php`
- Prescriptions
  - `app/Http/Requests/StoreConsultationPrescriptionRequest.php`
  - `app/Http/Controllers/DoctorConsultationPrescriptionController.php`
  - `app/Http/Controllers/VisitOrderController.php`
  - `app/Actions/CreatePrescription.php`
  - `app/Data/Clinical/CreatePrescriptionDTO.php`
  - `app/Data/Clinical/CreatePrescriptionItemDTO.php`
- Lab requests
  - `app/Http/Requests/StoreConsultationLabRequest.php`
  - `app/Http/Controllers/DoctorConsultationLabRequestController.php`
  - `app/Http/Controllers/VisitOrderController.php`
  - `app/Actions/CreateLabRequest.php`
  - `app/Actions/UpdateLabRequest.php`
  - `app/Data/Clinical/CreateLabRequestDTO.php`
  - `app/Data/Clinical/UpdateLabRequestDTO.php`
- Imaging requests
  - `app/Http/Requests/StoreConsultationImagingRequest.php`
  - `app/Http/Controllers/DoctorConsultationImagingRequestController.php`
  - `app/Http/Controllers/VisitOrderController.php`
  - `app/Actions/CreateImagingRequest.php`
  - `app/Data/Clinical/CreateImagingRequestDTO.php`
- Facility service orders
  - `app/Http/Requests/StoreConsultationFacilityServiceOrderRequest.php`
  - `app/Http/Controllers/DoctorConsultationFacilityServiceOrderController.php`
  - `app/Http/Controllers/VisitOrderController.php`
  - `app/Actions/CreateFacilityServiceOrder.php`
  - `app/Actions/UpdateFacilityServiceOrder.php`
  - `app/Data/Clinical/CreateFacilityServiceOrderDTO.php`
  - `app/Data/Clinical/UpdateFacilityServiceOrderDTO.php`
- Pharmacy / dispensing
  - `app/Http/Requests/StoreDispenseRequest.php`
  - `app/Http/Requests/PostDispenseRequest.php`
  - `app/Http/Requests/DispensePrescriptionRequest.php`
  - `app/Http/Controllers/DispensingController.php`
  - `app/Actions/CreateDispensingRecord.php`
  - `app/Actions/PostDispense.php`
  - `app/Actions/DispensePrescription.php`
  - `app/Data/Pharmacy/CreateDispensingRecordDTO.php`
  - `app/Data/Pharmacy/CreateDispensingRecordItemDTO.php`
  - `app/Data/Pharmacy/PostDispenseDTO.php`
  - `app/Data/Pharmacy/PostDispenseItemDTO.php`
  - `app/Data/Pharmacy/PostDispenseAllocationDTO.php`
  - `app/Data/Pharmacy/DispensePrescriptionDTO.php`
  - `app/Data/Pharmacy/DispensePrescriptionItemDTO.php`
- Triage and vital signs
  - `app/Http/Requests/StoreTriageRecordRequest.php`
  - `app/Http/Requests/StoreVitalSignRequest.php`
  - `app/Http/Controllers/VisitTriageController.php`
  - `app/Http/Controllers/VisitVitalSignController.php`
  - `app/Actions/CreateTriageRecord.php`
  - `app/Actions/CreateVitalSign.php`
  - `app/Data/Clinical/CreateTriageRecordDTO.php`
  - `app/Data/Clinical/CreateVitalSignDTO.php`
- Consultations
  - `app/Http/Requests/StoreConsultationRequest.php`
  - `app/Http/Requests/UpdateConsultationRequest.php`
  - `app/Http/Controllers/DoctorConsultationController.php`
  - `app/Actions/CreateConsultation.php`
  - `app/Actions/UpdateConsultation.php`
  - `app/Actions/CompleteConsultation.php`
  - `app/Data/Clinical/CreateConsultationDTO.php`
  - `app/Data/Clinical/UpdateConsultationDTO.php`
  - `app/Data/Clinical/CompleteConsultationDTO.php`
- Onboarding primary branch
  - `app/Http/Requests/StoreOnboardingBranchRequest.php`
  - `app/Http/Controllers/OnboardingController.php`
  - `app/Actions/CreateOnboardingPrimaryBranch.php`
  - `app/Data/Onboarding/CreateOnboardingPrimaryBranchDTO.php`
- Onboarding departments and staff
  - `app/Http/Requests/StoreOnboardingDepartmentsRequest.php`
  - `app/Http/Requests/StoreOnboardingStaffRequest.php`
  - `app/Http/Controllers/OnboardingController.php`
  - `app/Actions/BootstrapOnboardingDepartments.php`
  - `app/Actions/BootstrapOnboardingStaffMember.php`
  - `app/Data/Onboarding/CreateOnboardingDepartmentsDTO.php`
  - `app/Data/Onboarding/CreateOnboardingDepartmentDTO.php`
  - `app/Data/Onboarding/CreateOnboardingStaffMemberDTO.php`

## Priority 1: Complex Nested Inventory Flows

These already normalize nested `items` arrays and will benefit immediately from DTOs.

### Goods receipts

- `app/Http/Requests/StoreGoodsReceiptRequest.php`
- `app/Http/Controllers/GoodsReceiptController.php`
- `app/Actions/CreateGoodsReceipt.php`

DTOs:

- `CreateGoodsReceiptDTO`
- `CreateGoodsReceiptItemDTO`

### Inventory requisitions

- `app/Http/Requests/StoreInventoryRequisitionRequest.php`
- `app/Http/Controllers/InventoryRequisitionController.php`
- `app/Actions/CreateInventoryRequisition.php`
- `app/Actions/ApproveInventoryRequisition.php`
- `app/Actions/IssueInventoryRequisition.php`

DTOs:

- `CreateInventoryRequisitionDTO`
- `CreateInventoryRequisitionItemDTO`
- `ApproveInventoryRequisitionDTO`
- `IssueInventoryRequisitionDTO`

### Inventory reconciliations

- `app/Http/Requests/StoreInventoryReconciliationRequest.php`
- `app/Http/Controllers/InventoryReconciliationController.php`
- `app/Actions/CreateInventoryReconciliation.php`
- `app/Actions/ReviewInventoryReconciliation.php`
- `app/Actions/ApproveInventoryReconciliation.php`
- `app/Actions/RejectInventoryReconciliation.php`
- `app/Actions\PostInventoryReconciliation.php`

DTOs:

- `CreateInventoryReconciliationDTO`
- `CreateInventoryReconciliationItemDTO`
- workflow DTOs for review, approval, rejection, and posting inputs

### Purchase orders

- `app/Http/Requests/StorePurchaseOrderRequest.php`
- `app/Http/Requests/UpdatePurchaseOrderRequest.php`
- `app/Http/Controllers/PurchaseOrderController.php`
- `app/Actions/CreatePurchaseOrder.php`
- `app/Actions/UpdatePurchaseOrder.php`

DTOs:

- `CreatePurchaseOrderDTO`
- `CreatePurchaseOrderItemDTO`
- `UpdatePurchaseOrderDTO`
- `UpdatePurchaseOrderItemDTO`

## Priority 2: Consultation Order Flows

These are the next-best candidates because they already carry nested medical order payloads across multiple entry points.

### Prescriptions

- `app/Http/Requests/StoreConsultationPrescriptionRequest.php`
- `app/Http/Controllers/DoctorConsultationPrescriptionController.php`
- `app/Http/Controllers/VisitOrderController.php`
- `app/Actions/CreatePrescription.php`
- `tests/Unit/Actions/CreateConsultationOrdersTest.php`

DTOs:

- `CreatePrescriptionDTO`
- `CreatePrescriptionItemDTO`

### Lab requests

- `app/Http/Requests/StoreConsultationLabRequest.php`
- `app/Http/Controllers/DoctorConsultationLabRequestController.php`
- `app/Http/Controllers/VisitOrderController.php`
- `app/Actions/CreateLabRequest.php`
- `app/Actions/UpdateLabRequest.php`

DTOs:

- `CreateLabRequestDTO`
- `UpdateLabRequestDTO`

### Imaging requests

- `app/Http/Requests/StoreConsultationImagingRequest.php`
- `app/Http/Controllers/DoctorConsultationImagingRequestController.php`
- `app/Http/Controllers/VisitOrderController.php`
- `app/Actions/CreateImagingRequest.php`

DTOs:

- `CreateImagingRequestDTO`

### Facility service orders

- `app/Http/Requests/StoreConsultationFacilityServiceOrderRequest.php`
- `app/Http/Controllers/DoctorConsultationFacilityServiceOrderController.php`
- `app/Http/Controllers/VisitOrderController.php`
- `app/Actions/CreateFacilityServiceOrder.php`
- `app/Actions/UpdateFacilityServiceOrder.php`

DTOs:

- `CreateFacilityServiceOrderDTO`
- `UpdateFacilityServiceOrderDTO`

## Priority 3: Dispensing and Pharmacy Fulfillment

These payloads are already doing manual shape normalization and are strong DTO candidates.

- `app/Http/Requests/StoreDispenseRequest.php`
- `app/Http/Requests/PostDispenseRequest.php`
- `app/Http/Controllers/DispensingController.php`
- `app/Actions/CreateDispensingRecord.php`
- `app/Actions/DispensePrescription.php`
- `app/Actions\PostDispense.php`

DTOs:

- `CreateDispensingRecordDTO`
- `CreateDispensingRecordItemDTO`
- `PostDispenseDTO`
- `PostDispenseItemDTO`
- `PostDispenseAllocationDTO`

## Priority 4: Consultation and Visit Clinical Capture

This phase is now implemented for:

- `app/Http/Requests/StoreTriageRecordRequest.php`
- `app/Http/Requests/StoreVitalSignRequest.php`
- `app/Http/Controllers/VisitTriageController.php`
- `app/Http/Controllers/VisitVitalSignController.php`
- `app/Actions/CreateTriageRecord.php`
- `app/Actions/CreateVitalSign.php`
- `app/Http/Requests/StoreConsultationRequest.php`
- `app/Http/Requests/UpdateConsultationRequest.php`
- `app/Http/Controllers/DoctorConsultationController.php`
- `app/Actions/CreateConsultation.php`
- `app/Actions/UpdateConsultation.php`
- `app/Actions/CompleteConsultation.php`

DTOs:

- `CreateTriageRecordDTO`
- `CreateVitalSignDTO`
- `CreateConsultationDTO`
- `UpdateConsultationDTO`
- `CompleteConsultationDTO`

## Priority 5: Onboarding, Staff, Patient, and Auth Inputs

These can use DTOs too, but they are lower-risk because many are flatter CRUD-style forms.

Started:

- `app/Http/Requests/StoreOnboardingBranchRequest.php`
- `app/Http/Requests/StoreOnboardingDepartmentsRequest.php`
- `app/Http/Requests/StoreOnboardingStaffRequest.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Actions/CreateOnboardingPrimaryBranch.php`
- `app/Actions/BootstrapOnboardingDepartments.php`
- `app/Actions/BootstrapOnboardingStaffMember.php`
- `app/Data/Onboarding/CreateOnboardingPrimaryBranchDTO.php`
- `app/Data/Onboarding/CreateOnboardingDepartmentsDTO.php`
- `app/Data/Onboarding/CreateOnboardingDepartmentDTO.php`
- `app/Data/Onboarding/CreateOnboardingStaffMemberDTO.php`

Still remaining:

- `app/Http/Requests/StorePatientRequest.php`
- `app/Http/Requests/UpdatePatientRequest.php`
- `app/Actions/UpdatePatient.php`
- `app/Http/Requests/CreateUserRequest.php`
- `app/Http/Requests/UpdateUserRequest.php`
- `app/Http/Requests/CreateUserPasswordRequest.php`
- `app/Http/Requests/UpdateUserPasswordRequest.php`
- `app/Actions/CreateUser.php`
- `app/Actions/UpdateUser.php`
- `app/Actions/CreateUserPassword.php`
- `app/Actions/UpdateUserPassword.php`

DTOs:

- onboarding DTOs
- patient DTOs
- user DTOs
- password credential DTOs

## Working Rule

If a controller is about to do this:

```php
$action->handle($request->validated());
```

and the payload represents domain data rather than a trivial flat CRUD form, it should become a DTO boundary.
