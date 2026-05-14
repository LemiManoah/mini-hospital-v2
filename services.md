# Hospital Services Design Analysis

## Overview

This application handles clinical services through several domain-specific tracks rather than one unified service catalog. The main tracks are consultation, laboratory, imaging, facility services, and pharmacy. Most tracks eventually create or update `VisitCharge` rows through shared billing actions, but the depth of workflow and billing support is uneven.

The overall design is understandable and mostly consistent with the app's Action pattern. The strongest areas are consultation, laboratory, facility service orders, pharmacy dispensing, and the shared billing pipeline. The weakest area is imaging, which currently behaves like a clinical order placeholder without tenant scoping, pricing, billing, reports, or a complete operational workflow.

Note: this document uses the current code names. Earlier drafts used `LabRequest` and `ImagingRequest`; the implementation uses `LabOrder` and `ImagingOrder`.

---

## The Five Service Tracks

### 1. Consultation (`Consultation`)

A doctor sees a patient. This is the primary clinical entry point for most OPD workflows.

**Key models:**

- `Consultation` - the encounter record: chief complaint, history, findings, assessment, plan, diagnosis, outcome, referral, and completion fields.
- `ConsultationType` - new, follow-up, review, OPD, emergency, telemedicine, general.
- `ConsultationOutcome` - discharged, admitted, referred, follow-up required, and related outcomes.

**Billing:**

`SyncConsultationCharge` resolves the matching consultation `FacilityService`, reads its linked `ChargeMaster`, resolves insurance or cash pricing with `ResolveVisitChargeAmount`, then calls `UpsertVisitCharge`.

**Important detail:**

Consultation fees are represented as normal `FacilityService` rows with `is_consultation = true`, such as General OPD Consultation or Review Consultation. The billable type used for consultations is `BillableItemType::SERVICE`.

---

### 2. Laboratory (`LabOrder` -> `LabOrderItem`)

A doctor requests one or more lab tests in a single lab order. Each test is represented by a separate order item and can move through its own specimen/result lifecycle.

**Key models:**

- `LabOrder` - the batch order: visit, consultation, branch, requester, priority, STAT flag, billing status, and rollup status.
- `LabOrderItem` - one test per item: status, price, actual cost, specimen/result timestamps, and computed workflow stage.
- `LabSpecimen` - specimen collection and rejection tracking.
- `LabResultEntry` - result entry, review, approval, release, and correction lifecycle.
- `LabResultValue` - individual result values.
- `LabTestCatalog` - test catalog: code, name, category, base price, specimen types, and result type.
- `LabTestCategory`, `SpecimenType`, `LabResultType` - lookup/reference models.
- `LabOrderItemConsumable` - consumables used per test.

**Workflow per item:**

```text
pending -> sample_collected -> result_entered -> reviewed -> approved/completed
              |
              v
          rejected
```

`LabOrderItem::workflowStage` derives a readable stage from specimen state and timestamps. `SyncLabOrderProgress` rolls item states up to the parent `LabOrder`.

**Billing:**

`SyncLabOrderCharge` resolves pricing per `LabOrderItem`, including insurance policy pricing, but still creates one `VisitCharge` for the whole `LabOrder`. The description is `Lab order: N tests`, the quantity is `1`, and the charge code is the hard-coded `LAB-REQUEST`.

**Deletion/update behavior:**

Pending lab orders and pending lab order items can be removed. Deleting a pending lab order removes its matching `VisitCharge` and recalculates billing. Removing one pending item resyncs the single lab-order charge.

---

### 3. Imaging (`ImagingOrder`)

A doctor creates a radiology/imaging order.

**Key models/enums:**

- `ImagingOrder` - the order record: modality, body part, laterality, clinical history, indication, priority, scheduled date, contrast/pregnancy fields, and radiation dose.
- `ImagingModality` - xray, CT, MRI, ultrasound, mammography, fluoroscopy, PET-CT.
- `ImagingOrderStatus` - requested, scheduled, in progress, completed, cancelled.

**Billing:**

None currently. `BillableItemType::IMAGING` exists but no `SyncImagingCharge` action uses it.

**Results/reporting:**

None currently. There is no `ImagingReport`, `ImagingFinding`, radiologist report action, or structured findings/impression model.

**Tenant scoping:**

Missing. `ImagingOrder` does not use `BelongsToTenant`, and the `imaging_orders` table has no `tenant_id` column.

**Operational workflow:**

The model has statuses and scheduling fields, but the only clear Action found is `CreateImagingOrder`. There is no matching action for scheduling, starting, completing, cancelling, documenting, or billing the order.

---

### 4. Facility Services (`FacilityService` -> `FacilityServiceOrder`)

Facility services cover non-lab, non-imaging, non-drug services such as dressings, physiotherapy, procedures, dental, nursing, transport, and other general services.

**Key models/enums:**

- `FacilityService` - service catalog entry: service code, name, category, selling price, cost price, billable flag, consultation flag, active flag, and `charge_master_id`.
- `FacilityServiceOrder` - an ordered service for a visit/consultation: ordered by, performed by, ordered/completed timestamps, and status.
- `FacilityServiceCategory` - consultation, dressing, physiotherapy, procedure, dental, nursing, transport, other.
- `FacilityServiceOrderStatus` - pending, in progress, completed, cancelled.

**Billing:**

`SyncFacilityServiceOrderCharge` checks whether the linked service is billable, resolves insurance/cash pricing via `ResolveVisitChargeAmount`, then calls `UpsertVisitCharge`.

**Important detail:**

`FacilityService` is also the pricing target for consultations. A consultation fee is simply a facility service marked as a consultation service.

---

### 5. Pharmacy / Drugs (`Prescription` -> `PrescriptionItem` -> `DispensingRecord`)

A doctor prescribes medications. Pharmacy fulfillment is handled through dispensing records.

**Key models/support classes:**

- `Prescription` - prescription header: visit, consultation, prescriber, status, discharge/long-term flags.
- `PrescriptionItem` - each drug line: inventory item, quantity, dose/frequency instructions, external pharmacy flag, and item status.
- `DispensingRecord` / `DispensingRecordItem` - pharmacy fulfillment draft/post workflow.
- `DispensingRecordItemAllocation` - batch allocations when batch tracking is enabled.
- `InventoryItem`, `InventoryBatch`, `StockMovement` - inventory pricing, stock, batch, and ledger integration.
- `PrescriptionDispenseProgress`, `PrescriptionDispenseStatusResolver` - status/progress calculation.

**Billing:**

`SyncPrescriptionCharge` runs when the prescription is created. It totals all non-external prescription items using `ResolveVisitChargeAmount` with `BillableItemType::DRUG`, then posts one `VisitCharge` per prescription.

**Dispensing:**

`DispensePrescription` creates a dispensing record and posts it. `PostDispense` handles stock movement, batch allocations, external outcomes, and prescription status synchronization. It does not currently resync prescription billing after actual dispensing outcomes are known.

---

## Shared Billing Pipeline

Most billable tracks feed into the same pipeline:

```text
Service/order created or updated
        |
        v
Sync*Charge action
        |
        v
ResolveVisitChargeAmount
        |
        |-- insured patient: look up active InsurancePolicyItem
        |-- cash/unmatched: use provided fallback price
        v
UpsertVisitCharge
        |
        |-- EnsureVisitBilling
        |-- RecalculateVisitBilling
        v
VisitBilling totals updated
```

**Key models in the pipeline:**

- `VisitCharge` - one charge line per service event, with polymorphic `source_type` and `source_id`.
- `VisitBilling` - bill summary per visit: gross, discount, paid, balance, and status.
- `InsurancePolicy` / `InsurancePolicyItem` - insurance package policy pricing by item type and item id.
- `VisitPayer` - identifies whether the visit is cash or insurance and which package applies.
- `ChargeMaster` - central price list used most clearly by `FacilityService`, but not yet the universal source of pricing truth.

**Important nuance:**

The app has both `ChargeMaster` and direct model-level/fallback prices. Many billing actions pass a fallback amount directly into `ResolveVisitChargeAmount`, so `ChargeMaster` is not yet the single source of truth for every billable service.

---

## What Is Good About This Design

### 1. Action pattern is used consistently

Most business operations are captured in dedicated Actions such as `CreateConsultation`, `CreateLabOrder`, `SyncLabOrderCharge`, `SyncFacilityServiceOrderCharge`, `SyncPrescriptionCharge`, and `UpsertVisitCharge`. This keeps controllers thinner and makes business logic more reusable.

### 2. Polymorphic charge source is flexible

`VisitCharge` uses a polymorphic source. A consultation, lab order, facility service order, or prescription can all create a charge without adding more columns to `visit_charges`.

### 3. Laboratory workflow is comparatively strong

Lab has specimen collection, result entry, review, approval, result visibility, correction support, consumables, and rollup status. It is much more complete than imaging.

### 4. Insurance pricing is centralized

`ResolveVisitChargeAmount` centralizes the lookup of insurance policy item prices and copay calculation. The individual service tracks do not need to implement insurance rules themselves.

### 5. Consultation tariffs are configurable

Consultation pricing can vary by consultation type, visit type, and branch. The tariff resolver can fall back from visit-type-specific tariffs to generic tariffs.

### 6. Duplicate prevention exists for common pending orders

Lab and facility service creation/update check for pending duplicates, reducing accidental duplicate billing from repeated UI submissions.

### 7. Pending order deletion keeps billing in sync

Pending lab orders, lab order items, and facility service orders remove or update their matching visit charges when deleted.

---

## Weak Or Incomplete Parts

### 1. Imaging is the largest service gap

Imaging has no tenant scope, no catalog, no price, no billing action, no report model, and no complete operational workflow. It can block visit completion because pending imaging orders are counted, but there is no clear Action pathway for moving an imaging order through scheduling, completion, cancellation, or reporting.

### 2. There is no unified service catalog

To answer "what services does this facility offer?", the app must query several unrelated sources:

- Lab tests: `LabTestCatalog`
- Facility services: `FacilityService`
- Consultations: `FacilityService` rows marked `is_consultation`
- Drugs: `InventoryItem`
- Imaging: `ImagingModality` enum and free-text body part, with no study catalog

This makes service search, price management, insurance negotiation, reporting, and imports harder than they need to be.

### 3. Pricing sources are fragmented

`FacilityService.selling_price`, `ChargeMaster.unit_price`, `LabTestCatalog.base_price`, `LabOrderItem.price`, `InventoryItem.default_selling_price`, and `InsurancePolicyItem.price` all participate in pricing. Some are catalogs, some are snapshots, and some are fallbacks. The source-of-truth rules are implicit rather than explicit.

### 4. `ChargeMaster` is not yet a universal charge master

`FacilityService` syncs to `ChargeMaster`, but lab and pharmacy billing do not appear to use `ChargeMaster` as their primary source. Lab charges use item/test pricing and a hard-coded charge code. Prescription charges use inventory item fallback prices and do not pass a charge master id.

### 5. Consultation pricing is indirect

Consultation pricing now uses the same facility service catalog as other services. Creating or changing a consultation price means creating or updating a consultation `FacilityService` and its linked `ChargeMaster`.

### 6. Lab billing is too coarse for invoices and claims

Lab pricing is resolved per test, but the result is posted as one aggregate `VisitCharge` for the whole lab order. That means invoices and insurance claims cannot naturally show separate test lines, test codes, or per-test coverage decisions.

### 7. Lab item price snapshotting is undocumented

`LabOrderItem.price` is copied from `LabTestCatalog.base_price` when the order is created or updated. That may be intentional price locking, but the design does not clearly state whether pending orders should keep old prices or follow catalog changes.

### 8. Updating a lab order deletes and recreates items

`UpdateLabOrder` replaces the order items wholesale. This is acceptable only while the order is still editable/pending, but it is a sharp edge because item-level specimen/result history would be lost if this action were ever exposed beyond the pending state.

### 9. Prescription billing happens before fulfillment

The current billing line is based on prescribed quantity, not posted dispensing records. If a drug is partially dispensed, not dispensed, substituted, or moved to external pharmacy during dispensing, the original prescription charge can become stale because `PostDispense` does not call `SyncPrescriptionCharge`.

### 10. Prescription billing is also coarse-grained

Like lab, prescription billing creates one charge line for the whole prescription. This hides individual drug lines, quantities, item codes, and per-drug insurance handling on the final bill.

### 11. Facility service orders lack clinical documentation fields

`FacilityServiceOrder` tracks what was ordered, who ordered it, who performed it, status, and timestamps. It does not have structured result, notes, findings, outcome, or procedure documentation fields.

### 12. Facility service workflow is thin

The model has `pending`, `in_progress`, `completed`, and `cancelled`, but the main update DTO only changes `facility_service_id`. A dedicated perform/complete/cancel workflow with authorization, audit, notes, and billing behavior would make this track more clinically useful.

### 13. Procedure categorization overlaps

`FacilityServiceCategory` has `PROCEDURE`, while `BillableItemType` also has `PROCEDURE`. Facility-service procedures are currently billed as `BillableItemType::SERVICE`, so `BillableItemType::PROCEDURE` appears unused in this service pipeline.

### 14. Historical pricing/versioning needs clearer rules

Charges store unit price and line total at the time of upsert, which is good. But catalog price changes, charge master changes, insurance policy item changes, and resync actions can all affect future upserts. The system should define when existing unpaid charges should be recalculated versus preserved.

### 15. Imaging and facility services need stronger tenant and branch consistency checks

Many Actions set tenant/branch from the visit, which is good. But imaging has no tenant field at all, and service/catalog lookups should consistently ensure that the selected service or test belongs to the same tenant/branch context as the visit.

---

## Suggested Improvements

### A. Fix imaging first

1. Add `tenant_id` and `facility_branch_id` to `imaging_orders` where appropriate, and apply `BelongsToTenant`.
2. Add an imaging study catalog, for example `ImagingStudyCatalog`, with code, name, modality, body part/body system, base price, active flag, and tenant/branch scope.
3. Add `SyncImagingCharge` using `BillableItemType::IMAGING`.
4. Add `ImagingReport` with findings, impression, radiologist, reported timestamp, and optional attachments.
5. Add Actions for scheduling, starting, completing, cancelling, and reporting imaging orders.
6. Make visit completion rules align with the imaging workflow so orders do not become permanent blockers.

### B. Decide the pricing source of truth

Pick one clear model for default pricing. The cleanest direction is to make `ChargeMaster` or a new `ServiceDefinition` model the canonical pricing surface, then have lab tests, facility services, consultation services, imaging studies, and drugs reference it consistently.

### C. Consider a unified service definition layer

A future `ServiceDefinition` could hold shared fields:

```text
service_definitions
  id
  tenant_id
  facility_branch_id nullable
  code
  name
  service_type
  base_price
  is_billable
  is_active
```

Domain-specific models could still exist for clinical workflow details, but pricing, insurance mapping, imports, and reporting would have one consistent service identity.

### D. Make lab and pharmacy charges line-level

Create one `VisitCharge` per `LabOrderItem` and one per chargeable `PrescriptionItem` or posted `DispensingRecordItem`. This would improve invoice clarity, insurance claim accuracy, cancellation handling, and reporting.

### E. Move pharmacy billing to dispensing/posting

Billing should follow posted dispense outcomes rather than initial prescription intent. `PostDispense` is the natural place to create or adjust medication charges because that action knows actual dispensed quantity, external pharmacy outcomes, substitutions, and batch allocations.

### F. Add clinical documentation to facility service orders

Add fields such as `notes`, `findings`, `outcome`, or `performed_summary`, then introduce dedicated Actions for starting, completing, and cancelling service orders.

### G. Clarify price snapshot rules

Document when prices are locked:

- At order creation?
- At service completion?
- At invoice generation?
- Until payment?
- Until insurance claim submission?

Then encode that rule consistently in the sync actions.

### H. Clean up unused or overlapping billing types

Either use `BillableItemType::PROCEDURE`, `IMAGING`, `BED_DAY`, and `OTHER` in real workflows, or remove/park them until they are supported. This will reduce confusion for insurance policy item configuration.

---

## Entity Relationship Summary

```text
PatientVisit
  |-- Consultation
  |     |-- LabOrder -> LabOrderItem -> LabSpecimen
  |     |                         |-- LabResultEntry -> LabResultValue
  |     |-- ImagingOrder          (currently no billing/report/catalog)
  |     |-- Prescription -> PrescriptionItem -> DispensingRecord
  |     |-- FacilityServiceOrder -> FacilityService
  |
  |-- VisitBilling
  |     |-- VisitCharge (source: morphTo -> service event)
  |
  |-- VisitPayer -> InsurancePackage
```

```text
FacilityService
  |-- ChargeMaster

InsurancePolicy
  |-- InsurancePolicyItem
        keyed by item_type + item_id
```

---

## Bottom Line

The service design makes sense as an incremental hospital workflow system, but it is not yet a fully unified service/pricing platform. The immediate priority should be imaging, because it is currently the least complete track and can affect visit completion without producing billing or clinical results. After imaging, the biggest architectural improvement would be clarifying pricing ownership and moving lab/pharmacy billing from aggregate headers to item-level charge lines.
