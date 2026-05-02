# Hospital Services Design Analysis

## Overview

The system handles all clinical services a hospital provides through **five parallel, domain-specific tracks**, each with its own models, workflows, and billing integration. There is no single unified "service catalog" — instead, each service type (lab, imaging, consultation, facility services, pharmacy) has a dedicated set of models.

---

## The Five Service Tracks

### 1. Consultation (`Consultation`)

A doctor sees a patient. This is the primary clinical entry point for most workflows.

**Key models:**
- `Consultation` — the encounter record (chief complaint, history, assessment, plan, diagnosis, outcome)
- `ConsultationTariff` — maps `ConsultationType × VisitType × Branch` to a `FacilityService` for pricing
- `ConsultationType` — new, follow-up, review, OPD, emergency, telemedicine, general
- `ConsultationOutcome` — discharged, admitted, referred, follow-up required, etc.

**Billing:** `SyncConsultationCharge` resolves the right `ConsultationTariff` → reads its linked `FacilityService` → calls `UpsertVisitCharge`.

**Notable:** A consultation fee is not priced directly. It must be set up as a `FacilityService` record and then linked via `ConsultationTariff`. There is no `price` field on `ConsultationTariff` itself.

---

### 2. Laboratory (`LabRequest` → `LabRequestItem`)

A doctor requests one or more lab tests in a single batch request. Each test has its own lifecycle.

**Key models:**
- `LabRequest` — the batch (visit, consultation, priority, is_stat, billing_status)
- `LabRequestItem` — one test per item (status, timestamps, price, workflow_stage computed attribute)
- `LabSpecimen` — specimen for each item (collection, rejection tracking)
- `LabResultEntry` — the result record (with enter → review → approve → release → correct lifecycle)
- `LabResultValue` — individual values within a result
- `LabTestCatalog` — the test directory (code, name, category, base_price, specimen types, result type)
- `LabTestCategory`, `SpecimenType`, `LabResultType` — supporting lookup tables
- `LabRequestItemConsumable` — consumables used per test

**Workflow per item (tracked via `workflowStage` computed attribute on `LabRequestItem`):**
```
pending → sample_collected → result_entered → reviewed → approved
                  ↓
             (specimen rejected)
```

**Billing:** `SyncLabRequestCharge` sums the price of all items into a single `VisitCharge` per `LabRequest`. The charge description is "Lab request: N tests".

**Status rollup:** `SyncLabRequestProgress` derives `LabRequest.status` from the aggregate state of its items.

---

### 3. Imaging (`ImagingRequest`)

A doctor requests a radiology study.

**Key models:**
- `ImagingRequest` — the request record (modality, body_part, laterality, clinical_history, indication, priority, pregnancy_status, requires_contrast, radiation_dose_msv)
- `ImagingModality` enum — xray, ct, mri, ultrasound, mammography, fluoroscopy, pet_ct
- `ImagingRequestStatus` enum — requested, scheduled, in_progress, completed, cancelled

**Billing:** None. There is no `SyncImagingCharge` action. `BillableItemType::IMAGING` is defined in the enum but is never referenced in any action.

**Results:** None. There is no `ImagingReport` or `ImagingFinding` model. The request reaches `COMPLETED` status with no structured place to document radiologist findings.

**Tenant scoping:** None. `ImagingRequest` does not use the `BelongsToTenant` trait and has no `tenant_id` column.

---

### 4. Facility Services (`FacilityService` → `FacilityServiceOrder`)

General hospital services that don't fit into lab/imaging — dressings, physiotherapy, procedures, dental, nursing, transport.

**Key models:**
- `FacilityService` — the service catalog entry (service_code, name, category, selling_price, cost_price, is_billable, charge_master_id)
- `FacilityServiceOrder` — an order to perform the service for a specific visit/consultation (ordered_by, performed_by, ordered_at, completed_at, status)
- `FacilityServiceCategory` enum — dressing, physiotherapy, procedure, dental, nursing, transport, other
- `FacilityServiceOrderStatus` enum — pending, in_progress, completed, cancelled

**Billing:** `SyncFacilityServiceOrderCharge` → checks `is_billable` → calls `ResolveVisitChargeAmount` → `UpsertVisitCharge`.

**Notable:** `FacilityService` is also what `ConsultationTariff` points to. A consultation fee is just a special facility service.

---

### 5. Pharmacy / Drugs (`Prescription` → `PrescriptionItem`)

A doctor prescribes medications. Dispensing and charging are handled separately.

**Key models:**
- `Prescription` — the prescription header (visit, consultation, prescribed_by, is_discharge_medication, is_long_term)
- `PrescriptionItem` — each drug line (quantity, is_external_pharmacy)
- `DispensingRecord` / `DispensingRecordItem` — pharmacy fulfillment
- `InventoryItem` — links to drug inventory for pricing

**Billing:** `SyncPrescriptionCharge` totals all non-external items using `ResolveVisitChargeAmount` (with `BillableItemType::DRUG`) and posts one `VisitCharge` per prescription.

---

## The Billing Pipeline (Shared by All Tracks Except Imaging)

Every service type feeds into the same pipeline:

```
Service created / updated
       ↓
Sync*Charge Action
       ↓
ResolveVisitChargeAmount
  → insured patient? → look up InsurancePackagePrice (billable_type + billable_id + package_id)
  → else: use base price / selling_price
       ↓
UpsertVisitCharge (polymorphic source: MorphTo)
  → EnsureVisitBilling (creates VisitBilling if missing)
  → RecalculateVisitBilling (recomputes gross/paid/balance/status)
  → SyncInsuredVisitClaim
```

**Key models in the pipeline:**
- `ChargeMaster` — central price list (billable_type, billable_id, unit_price, effective dates)
- `VisitCharge` — one charge line per service event, with polymorphic `source` (morphTo)
- `VisitBilling` — the bill summary per visit (gross, discount, paid, balance, status)
- `InsurancePackagePrice` — insurance-specific prices keyed by billable_type + billable_id
- `VisitPayer` — identifies whether the payer is cash or insured (with which package)

---

## What's Good About This Design

### 1. Action pattern with composable Sync actions
Each service type has a dedicated `Sync*Charge` action that is called from multiple places (create, update, complete). Business logic is in single-responsibility, injectable, testable classes. This is correct.

### 2. Polymorphic charge source
`VisitCharge.source` is a `MorphTo` relationship. Any model (Consultation, LabRequest, FacilityServiceOrder, Prescription) can be a charge source without changing the `visit_charges` table schema. Adding a new service type only requires a new `Sync*Charge` action.

### 3. Lab workflow is realistic and well-modeled
The lab track has specimen collection, result entry, review, and approval — each with timestamps and responsible staff. The `workflowStage` computed attribute on `LabRequestItem` derives readable state from the timestamp trail. `SyncLabRequestProgress` rolls up item statuses to the request level. This is clinically accurate.

### 4. Insurance pricing is centrally resolved
`ResolveVisitChargeAmount` is called by every `Sync*Charge` action with the same signature. It checks for an active `InsurancePackagePrice` record, then falls back to the base price. No service type needs its own insurance logic.

### 5. `ConsultationTariff` separates pricing from encounter
The tariff lookup matches `ConsultationType × VisitType × Branch`, with a fallback when `visit_type` is null. Pricing is a configuration concern, not a clinical concern. The ordering (specific > generic) via `orderByRaw(CASE WHEN ...)` is sound.

### 6. Enum-driven statuses throughout
All statuses, categories, modalities, and outcomes are typed PHP 8.1 enums with `label()` methods. No magic strings scattered in the application code.

### 7. Duplicate prevention at order creation
Both `CreateLabRequest` and `CreateFacilityServiceOrder` query for pending duplicates before inserting, preventing double-ordering from the UI.

---

## What's Problematic

### 1. Imaging has no billing, no results, and no tenant scope
This is the largest gap. `BillableItemType::IMAGING` exists but is never applied. `ImagingRequest` has no `tenant_id`, no `BelongsToTenant` trait, no `SyncImagingCharge` action, and no `ImagingReport` model. An imaging service is trackable in status but invisible to billing and cross-tenant queries.

### 2. There is no unified service catalog
To see every service the facility offers:
- Labs: query `LabTestCatalog`
- Imaging: look at the `ImagingModality` enum (no catalog table)
- Facility services: query `FacilityService`
- Consultation: look at `ConsultationTariff → FacilityService`
- Drugs: query `InventoryItem`

There is no answer to "list all billable services" without joining five different sources. Reporting, pricing management, and insurance negotiations all become harder.

### 3. Consultation pricing is indirect and confusing
A consultation fee requires creating a `FacilityService` record (category: whatever fits), then creating a `ConsultationTariff` that points to it. The `FacilityService` then gets a `ChargeMaster` entry via `SyncFacilityServiceChargeMaster`. Three hops to price a consultation. `ConsultationTariff` has no `unit_price` field of its own.

### 4. Lab billing is coarse-grained
`SyncLabRequestCharge` creates one `VisitCharge` for the entire `LabRequest` with a description of "Lab request: N tests". Each `LabRequestItem` has its own `price` field, but billing ignores that granularity. On the invoice a patient sees one line item, not per-test charges. This breaks down for insurance claims where specific test codes may be covered differently.

### 5. Prescription billing fires at prescription creation, not dispensing
`SyncPrescriptionCharge` is called when the prescription is created/updated. If a drug is not dispensed (stock issue, patient refusal), or is partially dispensed, the charge remains. The `DispensingRecord` exists for actual fulfillment but doesn't feed back into billing.

### 6. `FacilityServiceOrder` has no outcome or clinical notes field
For a physiotherapy session or a procedure, there is no field to document what was performed, any observations, or a result. The order goes to `completed` status with only a `performed_by` and `completed_at`. This limits clinical documentation.

### 7. `FacilityService.selling_price` and `ChargeMaster.unit_price` are redundant
Both hold a price for the same thing. `SyncFacilityServiceChargeMaster` keeps them in sync, but it's a maintenance burden. The source of truth is ambiguous — `ResolveVisitChargeAmount` reads from `InsurancePackagePrice` first, then falls back to `FacilityService.selling_price` passed in by the caller, but `ChargeMaster.unit_price` is a third value that could diverge.

### 8. `LabRequestItem.price` is snapshotted at creation time
The base price from `LabTestCatalog` is copied onto `LabRequestItem.price` at the moment the order is created. If the test's price changes, pending items do not update. This is sometimes intentional (price locking), but there is no explicit design decision documented — `actual_cost` exists alongside `price`, suggesting intended differentiation that isn't fully implemented.

### 9. `FacilityServiceCategory` and `BillableItemType` overlap inconsistently
`FacilityServiceCategory` has `PROCEDURE` and `DENTAL`. `BillableItemType` has `PROCEDURE`. A procedure done as a facility service is billed as `BillableItemType::SERVICE`, not `BillableItemType::PROCEDURE`. The `PROCEDURE` billing type is never used by any `Sync*Charge` action.

---

## Suggested Improvements

### A. Fix imaging immediately (critical gap)

1. Add `tenant_id` to `imaging_requests` and apply `BelongsToTenant`.
2. Create an `ImagingStudyCatalog` table (code, name, modality, base_price) that functions like `LabTestCatalog` does for lab.
3. Add a `SyncImagingCharge` action that reads the study price and calls `UpsertVisitCharge` with `BillableItemType::IMAGING`.
4. Add an `ImagingReport` model (linked to `ImagingRequest`) with fields: `findings`, `impression`, `radiologist_id`, `reported_at`.

### B. Introduce a unified service catalog

Create a `ServiceDefinition` table that all billable items reference:

```
service_definitions
  id, tenant_id, code, name, category (enum covering all types), 
  base_price, is_billable, is_active
```

`LabTestCatalog`, `FacilityService`, and any future service type would either extend or reference this. `ChargeMaster` could point to `service_definitions` instead of managing separate `billable_type + billable_id` logic. Insurance pricing (`InsurancePackagePrice`) would reference `service_definition_id` directly.

This is a significant refactor but makes pricing management, insurance negotiation, and audit reporting far simpler.

### C. Move consultation pricing onto `ConsultationTariff` directly

Add a `unit_price` field to `ConsultationTariff`. Remove the mandatory dependency on `FacilityService`. Optionally keep the link for cases where a consultation is also a trackable facility service, but make it optional rather than required.

### D. Charge per lab test item, not per request

Change `SyncLabRequestCharge` to call `UpsertVisitCharge` once per `LabRequestItem` (not once per `LabRequest`). Use the item's own `price` as the unit amount. This:
- Gives per-test line items on the invoice
- Allows insurance to apply different coverage rules per test
- Simplifies removing a charge when a single test is cancelled

### E. Sync prescription charge at dispensing, not at prescription creation

Move `SyncPrescriptionCharge` to be called from the dispensing workflow (`DispensingRecord` completion) rather than from the prescription create/update actions. This aligns the charge with the actual service rendered.

### F. Add clinical notes to `FacilityServiceOrder`

Add a `notes` text field (for procedure details/observations) and optionally an `outcome` field to `FacilityServiceOrder`. This makes it useful for clinical documentation, not just workflow tracking.

### G. Remove the `selling_price` / `ChargeMaster` duplication

Pick one source of truth. Since `ChargeMaster` already exists as the price catalog and is linked from `FacilityService`, remove `FacilityService.selling_price` and derive the price from `ChargeMaster` at billing time. This eliminates the sync action requirement and removes divergence risk.

---

## Entity Relationship Summary

```
PatientVisit
  ├── Consultation
  │     ├── LabRequest → LabRequestItem → LabSpecimen
  │     │                              └── LabResultEntry → LabResultValue
  │     ├── ImagingRequest                (no billing, no result model)
  │     ├── Prescription → PrescriptionItem → DispensingRecord
  │     └── FacilityServiceOrder → FacilityService
  ├── VisitBilling
  │     └── VisitCharge (source: MorphTo → any of the above)
  └── VisitPayer → InsurancePackage
```

```
ChargeMaster (price catalog)
  └── referenced by FacilityService.charge_master_id

InsurancePackagePrice (insurance overrides)
  └── keyed by: insurance_package_id + billable_type + billable_id
```
