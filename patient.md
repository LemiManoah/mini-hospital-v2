# Patient Module Implementation Review

**Date:** March 13, 2026

---

## Current Implementation Status

### ✅ Implemented

| Component                | Status        | Notes                                                                                 |
| ------------------------ | ------------- | ------------------------------------------------------------------------------------- |
| Patient Model            | ✅ Complete   | Demographics only - contact, next of kin, blood group, occupation, religion           |
| Patient Controller       | ✅ Complete   | CRUD: index, create, store, edit, update, destroy                                     |
| Patient Actions          | ✅ Complete   | CreatePatient, UpdatePatient, DeletePatient                                           |
| Form Requests            | ✅ Complete   | StorePatientRequest, UpdatePatientRequest, DeletePatientRequest                       |
| Frontend Index           | ✅ Complete   | Search, pagination, table with MRN, name, phone, gender                               |
| Frontend Create          | ✅ Complete   | Multi-section form (Bio, Contact, Kin) - billing moved to visit                       |
| Frontend Edit            | ✅ Complete   | Same structure as create                                                              |
| Patient Allergies        | ✅ Complete   | Separate model, controller, routes (`patients.allergies` resource)                    |
| Patient Insurance        | ✅ Deprecated | Moved to visit-level (visit_payers) - patient insurances retained for historical data |
| PayerType Enum           | ✅ Complete   | Supports `cash` and `insurance`                                                       |
| Routes                   | ✅ Complete   | `Route::resource('patients')`                                                         |
| PatientVisit Model       | ✅ Complete   | Encounter tracking with status, clinic, doctor                                        |
| Visit Payer              | ✅ Complete   | Per-visit billing snapshot (visit_payers table)                                       |
| Register + Visit Start   | ✅ Complete   | Single transaction registration + visit creation                                      |
| Visit Status Transitions | ✅ Complete   | TransitionPatientVisitStatus action                                                   |
| One Active Visit Rule    | ✅ Complete   | Prevents multiple active visits per patient                                           |
| Patient Visit History    | ✅ Complete   | Visit history shown on patient profile                                                |

### ❌ Not Implemented

| Component               | Priority | Notes                                                               |
| ----------------------- | -------- | ------------------------------------------------------------------- |
| Patient Search Endpoint | High     | No dedicated search API to check for duplicates before registration |
| Consultation Records    | High     | No consultations table, model, action, controller, or UI yet        |
| Queue/Triage View       | Medium   | No queue page for triaging registered patients                      |
| Visit Status Logs       | Medium   | No immutable audit timeline for visit transitions                   |
| Billing Flow            | Medium   | No visit_charges, visit_billings, payments                          |
| Insurance Claims        | Low      | No claim workflow for insurance companies                           |

---

## Design Pattern Review

### Current Design (Patient Pages)

The patient create/edit pages use:

- `Card` component with `CardHeader` and `CardTitle` for sections
- Form fields in grid layouts
- Basic header with title and back button

### Expected Design (per allergen pattern)

The allergen module uses:

- Header with icon (e.g., `formatIdentifierLabel` helper)
- Form inside bordered container
- Proper label styling (`text-sm font-semibold`)
- Cancel button in footer

**Gap:** Patient create/edit pages do NOT follow the allergen design pattern. Should be updated to match.

---

## Data Model Review

### Patient Model - ✅ Demographics Only

```php
// Fillable fields - demographics only, no billing
$fillable = [
    'tenant_id',
    'patient_number',        // MRN
    'first_name', 'last_name', 'middle_name',
    'date_of_birth', 'age', 'age_units',
    'gender',
    'email', 'phone_number', 'alternative_phone',
    'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_relationship',
    'address_id',
    'marital_status', 'occupation', 'religion',
    'country_id',
    'blood_group',
    'created_by', 'updated_by',
];
```

### Relationships - ✅ Good

- `country()` - BelongsTo
- `address()` - BelongsTo
- `allergies()` - HasMany
- `activeAllergies()` - HasMany (scoped to active)
- `visits()` - HasMany (PatientVisit)
- `activeVisit()` - HasOne (scoped to active visit)
- `insurances()` - HasMany (PatientInsurance - deprecated for new registrations, kept for historical data)

### Implemented Models

1. **PatientVisit** ✅ - Encounter tracking with status, clinic, doctor
2. **VisitPayer** ✅ - Per-visit billing snapshot

### Pending Models

3. **Consultation** - For doctor consultations
4. **VisitCharge** - For billing line items
5. **VisitBilling** - For billing header
6. **Payment** - For payment records
7. **VisitStatusLog** - For immutable audit timeline

---

## Workflow Review

### Documented Flow (from patient_visit.md)

1. **Patient Search First** → Check for duplicates by phone/MRN/name+DOB
2. **New Patient Registration + Visit Start** → Create patient + visit + payer in single transaction
3. **Visit Check-in** → Choose payer (cash vs insurance) per visit (visit_payers table)
4. **Triage and Clinical Flow** → Queue, triage, consultation (NOT YET IMPLEMENTED)
5. **Billing and Payment** → Charges, payments, claims (NOT YET IMPLEMENTED)

### Actual Implementation

The current implementation covers **Step 2-3**:

- ✅ Patient registration form exists
- ✅ Single transaction: patient + visit + payer in one screen
- ✅ Per-visit payer selection (cash vs insurance)
- ✅ Insurance selection happens per visit, not per patient
- ✅ Visit status transitions (registered → in_progress → completed)
- ✅ Triage recording exists on visit details
- ✅ Vital signs recording exists after triage
- ✅ One active visit rule enforced
- ❌ No patient search before registration
- ❌ No triage queue
- ❌ No billing flow (charges, billings, payments)
- ❌ No consultation records

### Visit Statuses (Simplified)

- `registered` - Patient checked in, waiting for clinical action
- `in_progress` - Clinical activity started (triage, consultation)
- `awaiting_payment` - Defined but not wired into workflow yet
- `completed` - Visit closed
- `cancelled` - Visit cancelled

---

## Recommendations

### High Priority

1. **Add patient search** before registration to prevent duplicates
2. **Implement Consultation Records** - clinician notes, diagnosis, orders, outcome
3. **Wire consultation into downstream orders** - lab, imaging, prescriptions, facility services

### Medium Priority

4. Add queue/triage view for registered patients
5. Add visit status logs for audit timeline
6. Implement billing and payment flow (visit_charges, visit_billings, payments)

### Low Priority

7. Add insurance claim tracking
8. Add patient portal (future)

---

## Consultation Flow Implementation Plan

### Goal

Implement the doctor consultation stage as the next step after triage/vitals, using the `consultations` table as the single encounter note for the visit and the parent record for downstream clinical orders.

Adopt **Option A** for services:

- keep `charge_masters` as the billing source of truth
- keep `insurance_package_prices` as the insurance pricing source
- add a future operational order layer for general facility services instead of duplicating pricing catalogs

### Schema-Driven Design Notes

From `hospital_database_schema.md`, the consultation flow should be anchored on:

- `consultations` - one-to-one with `patient_visits` via `visit_id` and unique constraint
- `lab_requests` - optional `consultation_id`, plus request header and item rows
- `imaging_requests` - optional `consultation_id`, request metadata and workflow status
- `prescriptions` - required `consultation_id`, with separate `prescription_items`
- `visit_charges` - used later to bill consultation fees and ordered services
- `charge_masters` - master billing catalog for consultation and service charges
- `insurance_package_prices` - unified package pricing for insured visits across billable item types

Key consultation fields to support in phase 1:

- encounter timing: `started_at`, `completed_at`
- subjective history: `chief_complaint`, `history_of_present_illness`, `review_of_systems`
- background summary: `past_medical_history_summary`, `family_history`, `social_history`
- SOAP note fields: `subjective_notes`, `objective_findings`, `assessment`, `plan`
- diagnosis: `primary_diagnosis`, `primary_icd10_code`, `secondary_diagnoses`
- disposition: `outcome`, `follow_up_instructions`, `follow_up_days`
- referral details: `is_referred`, `referred_to_facility`, `referral_reason`

### Current Codebase Fit

The project already has:

- `PatientVisit` as the central encounter
- triage creation and vital-sign capture on the visit details page
- a visit details screen with a clear placeholder for consultation work
- simplified visit statuses: `registered`, `in_progress`, `awaiting_payment`, `completed`, `cancelled`
- unified insurance pricing via `insurance_package_prices`

Because of that, consultation should fit into the existing `visit show` workflow instead of introducing a separate standalone module first.

### Recommended Phases

#### Phase 1: Consultation Core

Build the minimum doctor workflow that lets a clinician open a visit, review triage/vitals, document the consultation, and save a single consultation record.

Deliverables:

- migration for `consultations`
- `Consultation` model with `visit()` and `doctor()` relations
- `PatientVisit::consultation()` one-to-one relation
- `StoreConsultationRequest` and `UpdateConsultationRequest`
- `CreateConsultation` and `UpdateConsultation` actions
- `VisitConsultationController@store` and `@update`
- load consultation data in `PatientVisitController@show`
- consultation panel on `resources/js/pages/visit/show.tsx`

Business rules:

- only one consultation per visit
- triage should exist before consultation is created
- `doctor_id` should default to the assigned visit doctor, with fallback to authenticated clinician
- saving the first consultation should keep the visit in `in_progress`
- completing the consultation should only mark the note complete, not automatically close the visit

#### Phase 2: Consultation Completion and Disposition

After the core note exists, add clinician disposition so the visit can move toward discharge, referral, admission, or follow-up.

Deliverables:

- consultation completion action that stamps `completed_at`
- outcome/disposition section in the consultation form
- guard rails for required fields before completion:
  - `chief_complaint`
  - `primary_diagnosis`
  - `assessment` or `plan`
  - `outcome` when the clinician marks consultation complete

Visit status handling:

- keep using `in_progress` during consultation to match the current enum
- if consultation outcome leads to more work (lab, imaging, pharmacy, billing), keep the visit open
- only allow visit completion once consultation is complete and there are no pending downstream blockers
- reuse `AssessPatientVisitCompletion` instead of duplicating visit-closing logic

#### Phase 3: Orders from Consultation

Use consultation as the origin point for downstream orders.

Deliverables:

- lab request creation from consultation
- imaging request creation from consultation
- prescription creation from consultation
- facility service order creation for non-lab, non-imaging, non-pharmacy services

Implementation notes:

- always persist `visit_id` and `consultation_id` on downstream records
- allow multiple lab and imaging requests per consultation if needed
- for prescriptions, create one prescription header per consultation save flow, then attach multiple `prescription_items`
- include diagnosis and clinician notes where schema supports them
- keep lab, imaging, and pharmacy as dedicated modules, not generic service orders

#### Phase 3A: Facility Services via Option A

Introduce a general facility services workflow for services that are operationally orderable but do not need dedicated lab/imaging/pharmacy pipelines.

Scope examples:

- dressings
- physiotherapy
- minor outpatient procedures
- dental services
- nursing procedures
- ambulance transport

Recommended design:

- add `facility_services` as an operational catalog if the team needs service metadata beyond pricing
- add `facility_service_orders` as the work-tracking table
- do **not** use `facility_services` as the pricing source of truth
- resolve all billable amounts through `charge_masters`
- resolve insurance prices through `insurance_package_prices`

Suggested `facility_service_orders` responsibilities:

- record who ordered the service and for which visit
- optionally link the order back to `consultation_id`
- track fulfillment status such as `pending`, `in_progress`, `completed`, `cancelled`
- capture department/service notes, performer, and completion time
- trigger or sync a corresponding `visit_charge`

Pricing rule under Option A:

- when a facility service order is placed, map it to the correct `charge_master`
- freeze the applied amount in `visit_charges`
- if the visit payer is insurance, resolve package pricing through `insurance_package_prices`
- if no package price exists, fall back to the standard charge master/base price rule defined by the billing module

This keeps:

- billing centralized
- insurance logic unified
- service fulfillment operationally trackable
- historical invoices protected from later price edits

#### Phase 4: Billing Hooks

Once consultation and orders are in place, connect them to finance without blocking clinical work.

Deliverables:

- add consultation charge to `visit_charges`
- add charges for lab/imaging/facility service orders when they are placed
- defer payment collection to the later billing module

Important rule:

- clinical save must not depend on immediate payment success
- facility service orders should be allowed to continue operationally even if billing settlement happens later

### UI Plan

Update the visit details page to become the clinician workbench.

Suggested section order:

1. patient snapshot
2. triage summary
3. latest vitals + vitals history
4. consultation note
5. orders tabs or cards:
   - lab
   - imaging
   - prescriptions
   - facility services
6. disposition and next steps

Consultation UX details:

- prefill chief complaint and HPI context from triage where helpful
- show allergies and past medical history in the consultation panel sidebar or summary cards
- support draft saving before final completion
- clearly separate `Save Draft` from `Complete Consultation`

### Validation and Guard Rails

Add these checks in requests/actions:

- cannot create consultation if one already exists for the visit
- cannot prescribe without an existing consultation
- cannot complete consultation with missing diagnosis/outcome data
- cannot place a facility service order without a mapped billing item if the service is configured as billable
- if `is_referred = true`, require destination and referral reason
- if `follow_up_days` is set, require `follow_up_instructions`
- if outcome is `admitted`, prevent visit completion and hand off to future IPD admission flow

For facility services:

- enforce that lab, imaging, and prescriptions continue through their dedicated tables
- validate the selected service is active for the current branch/tenant
- require `charge_master_id` mapping or equivalent billable mapping before auto-charging
- prevent duplicate active orders for the same visit/service when business rules require one-time fulfillment

### Open Design Decision

The schema documentation mentions richer visit states like `waiting_consultation` and `in_consultation`, but the current codebase only uses `registered`, `in_progress`, `awaiting_payment`, `completed`, and `cancelled`.

Recommendation:

- do not expand visit statuses in this consultation implementation
- treat consultation as a sub-workflow inside `in_progress`
- revisit richer queue statuses later when queue and department dashboards are implemented

For service workflow:

- do not introduce separate pricing tables that compete with `charge_masters`
- do not reintroduce service-only insurance tables; keep package pricing in `insurance_package_prices`
- use facility service orders as an operational layer, not as a replacement for specialized clinical modules

### Suggested Build Order

1. Add `consultations` migration and model relations
2. Expose consultation data on visit show page
3. Build create/update consultation actions and controller
4. Add consultation form UI to visit details page
5. Add completion/disposition workflow
6. Add prescriptions from consultation
7. Add lab/imaging ordering from consultation
8. Design `facility_service_orders` around Option A
9. Add consultation and order billing hooks

### Definition of Done

Consultation flow is ready when:

- a triaged visit can receive one consultation record
- the clinician can save draft notes and complete the consultation
- diagnosis and disposition are captured
- prescriptions can be created from the consultation
- lab/imaging requests can reference the consultation
- non-lab/non-imaging/non-pharmacy services can be ordered through a facility service order workflow
- facility service orders price through `charge_masters` and insurance resolution through `insurance_package_prices`
- the visit remains open until downstream work and billing rules allow completion

---

## Code Locations

| Component               | Path                                                                                 |
| ----------------------- | ------------------------------------------------------------------------------------ |
| Patient Model           | `app/Models/Patient.php`                                                             |
| Patient Controller      | `app/Http/Controllers/PatientController.php`                                         |
| Patient Actions         | `app/Actions/{Create,Update,Delete}Patient.php`                                      |
| Patient Requests        | `app/Http/Requests/{Store,Update,Delete}PatientRequest.php`                          |
| Patient Index           | `resources/js/pages/patient/index.tsx`                                               |
| Patient Create          | `resources/js/pages/patient/create.tsx`                                              |
| Patient Profile         | `resources/js/pages/patient/show.tsx`                                                |
| Patient Edit            | `resources/js/pages/patient/edit.tsx`                                                |
| Patient Types           | `resources/js/types/patient.ts`                                                      |
| Patient Allergies       | `app/Models/PatientAllergy.php`, `app/Http/Controllers/PatientAllergyController.php` |
| PatientVisit Model      | `app/Models/PatientVisit.php`                                                        |
| PatientVisit Controller | `app/Http/Controllers/PatientVisitController.php`                                    |
| Visit Payer Model       | `app/Models/VisitPayer.php`                                                          |
| PayerType Enum          | `app/Enums/PayerType.php`                                                            |
| VisitType Enum          | `app/Enums/VisitType.php`                                                            |
| VisitStatus Enum        | `app/Enums/VisitStatus.php`                                                          |
| Routes                  | `routes/web.php`                                                                     |
