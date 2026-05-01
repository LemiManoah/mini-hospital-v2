# Inpatient Module Plan

## Purpose

The inpatient module should manage the full ward admission lifecycle for patients who need bed-based care after OPD, emergency, direct admission, transfer, or day-care escalation.

It should answer:

- who is admitted right now
- where the patient is located
- which doctor and ward team own the admission
- what bed, ward, and care level they occupy
- what clinical work is pending
- what nursing care has been delivered
- what charges are accumulating
- whether the patient is clinically and financially ready for discharge

## Current App Foundations

The app already has useful building blocks:

- `PatientVisit` is the shared clinical and billing spine.
- `VisitType::INPATIENT` already exists.
- `VisitStatus` currently supports `registered`, `in_progress`, `awaiting_payment`, `completed`, and `cancelled`.
- Visit billing already supports visit-level charges, payments, payer snapshots, and recalculation.
- Consultation can create lab, imaging, prescription, and facility service orders.
- Triage and vitals already exist and can be reused for admission context.
- `FacilityServiceCategory::NURSING`, `BillableItemType::BED_DAY`, and `InventoryLocationType::WARD_STORE` already point toward ward workflows.
- The app uses tenant and branch scoping, action classes, Inertia pages, permission middleware, and audit activity.

The inpatient module should build on these instead of creating a separate patient journey.

## Product Scope

### In Scope

- inpatient admission from an existing visit
- direct admission for registered patients
- ward and bed management
- ward board / census
- admission notes and admitting diagnosis
- attending doctor assignment
- bed transfers
- inpatient clinical overview
- nursing care integration
- medication administration record
- inpatient orders using existing lab, imaging, prescription, and facility service flows
- bed-day and ward service billing
- discharge planning and discharge summary
- final discharge workflow with bed release

### Out Of Scope For First Release

- ICU-specific acuity scoring
- surgical theatre workflow
- maternity partograph
- insurance pre-authorization workflow
- claims batching by admission
- diet kitchen workflow
- housekeeping task board
- external referral receiving workflow

These can be added after the core admission lifecycle is stable.

## Key Product Decisions

1. `patient_visits` should remain the billing and clinical anchor.
2. An inpatient stay should be represented by an `inpatient_admissions` record linked to one visit.
3. A patient should not have more than one active admission in the same tenant.
4. Bed occupancy should be derived from active admissions and bed transfers, not manually edited counters alone.
5. Bed-day charges should be generated from admission occupancy, then synced into existing visit charges.
6. Discharge should not complete the visit until pending orders and billing rules pass the existing completion checks.
7. Nurses should document care in the admission workspace, while finance continues collecting money in the finance module.

## Core Workflows

### 1. Admission Decision

Entry points:

- doctor consultation outcome: `admit`
- triage emergency escalation
- direct admission from patient profile
- transfer from another facility
- day-care conversion to inpatient

Required fields:

- patient
- source visit or new visit
- admission source
- admission type
- admitting doctor
- ward
- bed
- admitting diagnosis
- admission notes
- payer snapshot

Recommended behavior:

- If admission comes from an active OPD visit, convert or attach that visit to the admission.
- If admission is direct, create a new visit with `visit_type = inpatient`.
- Create the admission and first bed occupancy inside one database transaction.
- Mark the visit `in_progress`.
- Create initial bed-day charge if the facility charges on admission.
- Audit the admission event.

### 2. Ward Board

The ward board is the main inpatient landing page.

It should show:

- occupied beds grouped by ward
- available, reserved, cleaning, maintenance, and blocked beds
- patient name, MRN, admission number, and admission age
- attending doctor
- primary diagnosis
- payer type
- pending orders
- unpaid balance warning
- discharge planned flag
- isolation or priority flags

Filters:

- ward
- doctor
- payer type
- admission source
- status
- search by patient, MRN, phone, admission number, or bed

### 3. Admission Workspace

The workspace should be available at `/inpatient/admissions/{admission}`.

Tabs:

- Overview
- Clinical
- Orders
- Nursing Care
- Medication Administration
- Bed & Transfers
- Billing
- Discharge
- Audit

The workspace should reuse visit components where possible, especially orders, billing summaries, allergies, vitals, consultation snapshot, and audit timeline.

### 4. Bed Transfers

A transfer should capture:

- admission
- from ward and bed
- to ward and bed
- reason
- requested by
- approved by, when needed
- transferred at
- notes

Rules:

- only one active bed assignment per admission
- destination bed must be available or reserved for the admission
- previous bed becomes cleaning or available based on facility setting
- transfer may change bed-day rate from the next billing period
- all transfers are audited

### 5. Daily Care

Daily inpatient activity should include:

- repeat vitals and observation charting
- nursing care plans
- nursing interventions
- intake and output
- handover notes
- medication administration
- lab, imaging, prescription, and facility service orders
- progress notes by doctors

The first version can reuse the existing visit clinical and order surfaces, then add inpatient-specific nursing and MAR tables.

### 6. Billing

Billing should stay visit-based.

Inpatient billing adds charge sources for:

- bed days
- admission fee
- ward procedures
- nursing services
- consumables
- discharge medication
- inpatient facility services

Recommended billing actions:

- `SyncInpatientBedDayCharges`
- `SyncInpatientAdmissionCharge`
- `SyncInpatientTransferCharge`
- `AssessInpatientDischargeReadiness`

Rules:

- bed-day charges should be idempotent
- charges should store applied price at the time of generation
- bed-day charge periods should be explicit, not inferred from mutable timestamps only
- cancelled or reversed charges should remain auditable
- finance collects payments through existing visit billing screens

### 7. Discharge

Discharge should have two parts:

1. Clinical discharge planning
2. Final discharge and bed release

Clinical discharge fields:

- discharge diagnosis
- discharge condition
- discharge type
- discharge summary
- procedures performed
- significant investigations
- medication on discharge
- follow-up instructions
- follow-up date
- doctor completing discharge
- nurse discharge checklist

Final discharge checks:

- no pending lab, imaging, prescription, or service orders
- MAR has no unresolved critical medication tasks
- discharge summary is completed
- bed-day charges are synced through discharge date
- payment warnings are visible
- user has discharge permission

On final discharge:

- set admission status to discharged
- set `discharged_at`
- release or mark bed for cleaning
- optionally complete the visit if all visit completion checks pass
- audit the discharge event

## Suggested Domain Model

### `wards`

- `id`
- `tenant_id`
- `facility_branch_id`
- `department_id`
- `code`
- `name`
- `type`
- `gender_policy`
- `capacity`
- `is_active`
- `created_by`
- `updated_by`
- timestamps
- soft deletes

Indexes:

- unique `tenant_id`, `facility_branch_id`, `code`
- index `facility_branch_id`, `is_active`

### `beds`

- `id`
- `tenant_id`
- `facility_branch_id`
- `ward_id`
- `bed_number`
- `type`
- `status`
- `daily_rate`
- `facility_service_id`
- `is_active`
- `created_by`
- `updated_by`
- timestamps
- soft deletes

Indexes:

- unique `ward_id`, `bed_number`
- index `status`
- index `facility_branch_id`, `status`

### `inpatient_admissions`

- `id`
- `tenant_id`
- `facility_branch_id`
- `patient_id`
- `patient_visit_id`
- `admission_number`
- `admission_source`
- `admission_type`
- `status`
- `admitted_at`
- `admitting_doctor_id`
- `attending_doctor_id`
- `current_ward_id`
- `current_bed_id`
- `admitting_diagnosis`
- `admission_notes`
- `priority_level`
- `is_isolation_required`
- `discharge_planned_at`
- `discharged_at`
- `discharged_by`
- `discharge_type`
- `discharge_diagnosis`
- `discharge_summary`
- `follow_up_date`
- `created_by`
- `updated_by`
- timestamps
- soft deletes

Indexes:

- unique `tenant_id`, `admission_number`
- unique active admission rule enforced in application logic
- index `facility_branch_id`, `status`, `admitted_at`
- index `current_ward_id`, `current_bed_id`
- index `patient_visit_id`

### `inpatient_bed_assignments`

- `id`
- `tenant_id`
- `facility_branch_id`
- `inpatient_admission_id`
- `ward_id`
- `bed_id`
- `assigned_at`
- `released_at`
- `assignment_type`
- `reason`
- `assigned_by`
- `released_by`
- `notes`
- timestamps

Indexes:

- index `inpatient_admission_id`, `assigned_at`
- index `bed_id`, `released_at`

### `inpatient_bed_day_charges`

- `id`
- `tenant_id`
- `facility_branch_id`
- `inpatient_admission_id`
- `patient_visit_id`
- `bed_id`
- `ward_id`
- `visit_charge_id`
- `charge_date`
- `quantity`
- `unit_price`
- `status`
- `generated_at`
- `reversed_at`
- `notes`
- timestamps

Indexes:

- unique `inpatient_admission_id`, `bed_id`, `charge_date`
- index `patient_visit_id`
- index `visit_charge_id`

### `inpatient_progress_notes`

- `id`
- `tenant_id`
- `facility_branch_id`
- `inpatient_admission_id`
- `patient_visit_id`
- `author_id`
- `note_type`
- `recorded_at`
- `subjective`
- `objective`
- `assessment`
- `plan`
- `notes`
- timestamps
- soft deletes

### `inpatient_discharge_checklists`

- `id`
- `tenant_id`
- `facility_branch_id`
- `inpatient_admission_id`
- `patient_visit_id`
- `summary_completed`
- `medications_reconciled`
- `follow_up_recorded`
- `patient_education_done`
- `nursing_clearance_done`
- `billing_reviewed`
- `completed_by`
- `completed_at`
- timestamps

## Enums

Suggested enum classes:

- `InpatientAdmissionStatus`: `Admitted`, `TransferPending`, `DischargePlanned`, `Discharged`, `TransferredOut`, `Absconded`, `Expired`, `Cancelled`
- `AdmissionSource`: `Opd`, `Emergency`, `Direct`, `ExternalTransfer`, `DayCare`
- `AdmissionType`: `Emergency`, `Elective`, `DayCase`, `Transfer`
- `WardType`: `General`, `Private`, `Pediatric`, `Maternity`, `Surgical`, `Medical`, `Icu`, `Isolation`
- `BedStatus`: `Available`, `Occupied`, `Reserved`, `Cleaning`, `Maintenance`, `Blocked`
- `BedAssignmentType`: `Admission`, `Transfer`, `Reservation`
- `DischargeType`: `Home`, `Transfer`, `Referred`, `LeftAgainstMedicalAdvice`, `Expired`

Use TitleCase enum keys in PHP and string values for database storage.

## Actions

Create business logic in `app/Actions`:

- `CreateInpatientAdmission`
- `TransferInpatientBed`
- `ReserveInpatientBed`
- `ReleaseInpatientBed`
- `PlanInpatientDischarge`
- `FinalizeInpatientDischarge`
- `SyncInpatientBedDayCharges`
- `AssessInpatientDischargeReadiness`
- `CreateInpatientProgressNote`
- `CreateInpatientDischargeChecklist`

Important action rules:

- Use `DB::transaction()` for admission, transfer, charge sync, and final discharge.
- Keep controllers thin.
- Reuse `TransitionPatientVisitStatus`, `EnsureVisitBilling`, `RecalculateVisitBilling`, and visit order actions where possible.
- Audit important workflow events.

## Controllers And Routes

Suggested controllers:

- `InpatientDashboardController`
- `WardController`
- `BedController`
- `InpatientAdmissionController`
- `InpatientBedTransferController`
- `InpatientProgressNoteController`
- `InpatientDischargeController`

Suggested routes:

- `GET /inpatient/dashboard`
- `GET /inpatient/wards`
- `GET /inpatient/beds`
- `GET /inpatient/admissions`
- `GET /inpatient/admissions/create`
- `POST /inpatient/admissions`
- `GET /inpatient/admissions/{admission}`
- `POST /inpatient/admissions/{admission}/transfers`
- `POST /inpatient/admissions/{admission}/progress-notes`
- `PATCH /inpatient/admissions/{admission}/discharge-plan`
- `POST /inpatient/admissions/{admission}/finalize-discharge`
- `POST /inpatient/admissions/{admission}/sync-bed-day-charges`

Use Wayfinder-generated route helpers from the frontend instead of hardcoded URLs when implementing.

## Inertia Pages

Suggested pages:

- `resources/js/pages/inpatient/dashboard.tsx`
- `resources/js/pages/inpatient/admissions/index.tsx`
- `resources/js/pages/inpatient/admissions/create.tsx`
- `resources/js/pages/inpatient/admissions/show.tsx`
- `resources/js/pages/inpatient/wards/index.tsx`
- `resources/js/pages/inpatient/beds/index.tsx`

Suggested components:

- ward board
- bed map
- admission summary header
- admission status strip
- discharge readiness panel
- bed transfer dialog
- bed-day charge summary
- inpatient progress note form
- inpatient orders panel
- inpatient nursing panel
- MAR panel

## Permissions

Suggested permission groups:

- `inpatient.view`
- `inpatient.create`
- `inpatient.update`
- `inpatient.discharge`
- `inpatient.transfer_beds`
- `wards.view`
- `wards.create`
- `wards.update`
- `wards.delete`
- `beds.view`
- `beds.create`
- `beds.update`
- `beds.delete`

Suggested access:

- doctors: view admissions, create progress notes, plan discharge
- nurses: view admissions, update nursing care, transfer requests, discharge checklist
- facility admin: manage wards and beds
- finance: view billing and payment state
- support: tenant-scoped support access only

## Navigation

Add an `Inpatient` module card to the modules page when the user has `inpatient.view`.

Add sidebar entries:

- Ward Board
- Admissions
- Beds
- Wards

Keep OPD, doctors, lab, pharmacy, and finance as separate modules, but link back to the inpatient admission when a visit belongs to an active admission.

## Integration With Existing Modules

### OPD And Consultation

- Add consultation outcome for admission if product confirms it.
- Allow admission from finalized or active consultation.
- Preserve existing consultation notes as admission context.

### Triage

- High-acuity triage can create an admission request.
- Emergency direct admission should still record triage when possible.

### Laboratory, Imaging, Pharmacy, Facility Services

- Keep existing order tables linked to the visit.
- Add admission context in queue displays when the visit has an active admission.
- Show ward and bed in lab/pharmacy work queues.

### Nursing

- The inpatient module should consume the planned nursing care model from `nursing.md`.
- Inpatient nursing should be admission-first, while OPD nursing can remain visit-first.
- Medication administration should use dispensed prescription items where possible.

### Billing

- Bed-day charges should become normal visit charges.
- Finance should not need a separate inpatient payment screen for the first release.
- Discharge readiness should show unpaid balance warnings but not necessarily block discharge unless product requires that.

### Reports

First reports:

- current bed occupancy
- admission census
- discharge count
- average length of stay
- ward revenue from bed days
- bed utilization by ward

## Delivery Phases

### Phase 1: Foundations

- create wards and beds
- seed basic ward and bed data
- add permissions
- add inpatient module card
- create admission model and migration
- admit patient from existing visit or direct admission
- show admission index and admission workspace shell
- set bed occupied on admission
- release bed on cancelled admission
- tests for tenant and branch isolation

### Phase 2: Ward Board And Transfers

- ward board grouped by ward
- bed availability filters
- bed transfer workflow
- bed assignment history
- audit admission and transfer events
- show ward/bed on visit and order queues
- tests for bed occupancy and transfer rules

### Phase 3: Billing

- bed-day charge generation
- idempotent charge sync
- admission and bed transfer charge support
- integration with existing visit billing recalculation
- discharge readiness billing panel
- tests for charge generation and recalculation

### Phase 4: Clinical Inpatient Workspace

- inpatient overview tab
- progress notes
- inpatient orders tab using existing order components
- nursing care tab
- repeat vitals and observation summaries
- MAR foundation
- tests for permissions and clinical writes

### Phase 5: Discharge

- discharge planning
- discharge checklist
- discharge summary
- discharge medication visibility
- final discharge action
- bed release or cleaning status
- optional visit completion
- printable discharge summary
- tests for discharge readiness and finalization

### Phase 6: Reporting And Polish

- occupancy dashboard
- admission and discharge reports
- length-of-stay reporting
- revenue by ward
- alerts for long stay and pending discharge
- UX refinements after staff feedback

## Testing Plan

Use Pest feature and unit tests.

Core tests:

- ward and bed CRUD permissions
- tenant and branch isolation for wards, beds, admissions, and transfers
- admission creation from an existing visit
- direct admission creates an inpatient visit
- patient cannot have two active admissions
- unavailable bed cannot be assigned
- transfer releases old bed and occupies new bed
- bed-day charge sync is idempotent
- discharge is blocked when required clinical fields are missing
- final discharge releases the bed and audits the event
- module pages require correct permissions

Run focused tests with:

```bash
php artisan test --compact tests/Feature/Controllers/InpatientAdmissionControllerTest.php
```

## Open Product Questions

- Should admission convert an OPD visit into an inpatient visit, or should it create a new inpatient visit linked to the OPD visit?
- Should unpaid balance block final discharge, or only warn?
- Should bed release go straight to available, or always to cleaning?
- Should nurses be allowed to transfer beds without approval?
- Should admission require a completed consultation, or can triage admit directly?
- Should bed-day billing happen on admission, daily schedule, discharge, or all three with idempotency?
- Should inpatient discharge automatically complete the visit?
- Should a readmission within 24 hours reopen the prior admission or create a new admission?

## Recommended First Build

Start with a small but solid vertical slice:

1. Wards and beds master data.
2. Create inpatient admission from an active visit.
3. Ward board showing occupied and available beds.
4. Bed transfer action.
5. Bed-day charge sync.
6. Final discharge with bed release.

This gives the app a real inpatient module without waiting for every nursing, MAR, and reporting detail to be finished first.
