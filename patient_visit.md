# Patient Visit Module - Exhaustive Implementation Plan

**Date:** March 12, 2026
**Status:** Planning only (pre-build)
**Primary Reference:** `hospital_database_schema.md`

---

## 1) Goal and Scope

Build a complete encounter lifecycle for OPD and ER from check-in to closure, with tight integration into triage, consultation, orders (lab/imaging/pharmacy), IPD admission, and billing.

In scope:
- Visit creation (walk-in and from appointment)
- Queue and triage workflow
- Consultation workflow
- Clinical orders linked to visit
- Visit billing and payments
- Visit timeline/history
- Auditability, permissions, and reporting

Out of scope (phase-later):
- Advanced claim adjudication rules engine
- Cross-facility referral network integrations
- Patient portal/self check-in

---

## 2) Current State (Repo)

Implemented now:
- Patient registration module exists (`Patient`, patient pages)
- No visit-related models/controllers/pages yet

Gap:
- `patient_visits` workflow is not implemented in application code

---

## 3) Core Data Model from Schema

Base tables from `hospital_database_schema.md` that power visits:
- `patient_visits`
- `triage_records`
- `vital_signs`
- `consultations`
- `lab_requests`, `lab_request_items`, `lab_specimens`, `lab_results`
- `imaging_requests`, `imaging_studies`, `radiology_reports`
- `prescriptions`, `prescription_items`, `dispensing_records`
- `ipd_admissions`
- `visit_charges`, `visit_billings`, `payments`

Visit status enum reference:
- `registered`, `triaged`, `waiting_consultation`, `in_consultation`, `waiting_lab`, `waiting_imaging`, `waiting_pharmacy`, `admitted`, `discharged`, `cancelled`

---

## 4) Required Schema Corrections Before Build

Fix these inconsistencies first (in migration files, not only docs):
- `patient_visits.visit_type` declaration appears malformed in docs (`enum('visit_type', [new Enum(VisitType::class)])->defalt()`) and should use Laravel enum casting style consistently.
- `patient_visits.status` default is unspecified (`default()` in docs). Set explicit default: `registered`.
- `radiology_reports` has a leading whitespace typo in `' communicated_at'`; correct to `communicated_at`.
- Index note references `visit_charges.department_id`, but table does not define `department_id`; either add column or remove that index.

---

## 5) Additional Fields to Add (Recommended)

To make visits production-ready, extend `patient_visits` and related structures.

### 5.1 patient_visits (add)
- `source_type` enum: `walk_in`, `appointment`, `referral`, `transfer`
- `service_area` enum: `opd`, `emergency`, `specialty`, `telemedicine`
- `queue_number` integer nullable
- `queue_priority` enum: `normal`, `priority`, `emergency`
- `arrival_datetime` timestamp
- `check_in_datetime` timestamp nullable
- `started_consultation_at` timestamp nullable
- `ended_consultation_at` timestamp nullable
- `disposition` enum: `discharged`, `admitted`, `referred`, `transferred`, `left_against_advice`, `deceased` nullable
- `payer_type` enum: `cash`, `insurance`, `corporate`, `waiver`
- `patient_insurance_id` uuid nullable (snapshot pointer used for this visit)
- `authorization_code` string nullable
- `referral_source` string nullable
- `referral_document_path` string nullable
- `cancellation_reason` text nullable
- `clinical_summary` text nullable

Why: supports queue operations, per-visit payer decisions, and lifecycle analytics without joining many tables.

### 5.2 triage_records (add)
- `triage_wait_minutes` integer nullable
- `seen_by_doctor_target_minutes` integer nullable
- `red_flag_symptoms` json nullable
- `infection_screening` json nullable

Why: quality metrics and public-health screening.

### 5.3 consultations (add)
- `consultation_type` enum: `initial`, `review`, `procedure_follow_up`
- `provisional_diagnosis` text nullable
- `final_diagnosis` text nullable
- `requires_follow_up` boolean default false
- `follow_up_date` date nullable

### 5.4 visit_charges (add)
- `department_id` uuid nullable (to match reporting index requirement)
- `source_table` string nullable (e.g., `lab_requests`, `prescription_items`)
- `source_id` uuid nullable

Why: traceability from generated charges to originating clinical actions.

### 5.5 New supporting tables
- `visit_status_logs`
  - `visit_id`, `from_status`, `to_status`, `changed_by`, `changed_at`, `reason`
- `visit_queue_events`
  - `visit_id`, `event_type`, `event_time`, `actor_id`, `notes`

Why: immutable timeline and audit-friendly operations.

---

## 6) Domain Rules and Lifecycle

### 6.1 Visit lifecycle (state machine)
1. `registered`
2. `triaged`
3. `waiting_consultation`
4. `in_consultation`
5. branching to `waiting_lab` / `waiting_imaging` / `waiting_pharmacy`
6. terminal: `discharged` or `admitted` or `cancelled`

### 6.2 Hard business rules
- One active visit per patient per service area (configurable override for ER).
- Triage required before non-emergency consultation.
- Cannot close visit with pending critical orders unless override reason captured.
- Admission requires consultation outcome `admitted`.
- Insurance visit requires active patient insurance and validity covering `visit_date`.
- Payments cannot exceed outstanding balance unless refund flow is triggered.

### 6.3 Soft rules
- Auto-prioritize queue for red/yellow triage grades.
- Auto-transition statuses when major actions complete.

---

## 7) Backend Implementation Plan (Laravel)

### 7.1 Enums
Create/confirm enums:
- `VisitType`
- `VisitStatus`
- `VisitSourceType`
- `QueuePriority`
- `DispositionType`
- `ConsultationOutcome`

### 7.2 Migrations
Create in order:
1. `create_patient_visits_table`
2. `create_visit_status_logs_table`
3. `create_visit_queue_events_table`
4. `create_triage_records_table`
5. `create_vital_signs_table`
6. `create_consultations_table`
7. `create_visit_charges_table`
8. `create_visit_billings_table`
9. `create_payments_table`
10. Alter tables for added fields/indexes

### 7.3 Models and relationships
Create models:
- `PatientVisit`, `VisitStatusLog`, `VisitQueueEvent`, `TriageRecord`, `VitalSign`, `Consultation`, `VisitCharge`, `VisitBilling`, `Payment`

Relationship essentials:
- `Patient hasMany PatientVisit`
- `PatientVisit belongsTo Patient, Clinic, Doctor, Appointment`
- `PatientVisit hasOne TriageRecord, Consultation, VisitBilling`
- `PatientVisit hasMany VisitCharge, VisitStatusLog, VisitQueueEvent`
- `VisitBilling hasMany Payment`

Apply tenant/branch global scopes where applicable.

### 7.4 Services / Actions (recommended pattern)
Create actions:
- `CreateVisit`
- `CheckInVisit`
- `TriageVisit`
- `StartConsultation`
- `CompleteConsultation`
- `TransitionVisitStatus`
- `GenerateVisitCharge`
- `FinalizeVisitBilling`
- `RecordVisitPayment`
- `CloseVisit`

### 7.5 Validation requests
Create request classes:
- `StorePatientVisitRequest`
- `UpdatePatientVisitRequest`
- `StoreTriageRecordRequest`
- `StoreConsultationRequest`
- `StoreVisitChargeRequest`
- `StorePaymentRequest`
- `CloseVisitRequest`

### 7.6 Controllers
- `PatientVisitController` (CRUD + queue endpoints)
- `VisitTriageController`
- `VisitConsultationController`
- `VisitBillingController`
- `VisitPaymentController`
- `PatientVisitTimelineController`

### 7.7 Route design
Add grouped routes:
- `/visits`
- `/visits/{visit}/triage`
- `/visits/{visit}/consultation`
- `/visits/{visit}/charges`
- `/visits/{visit}/billing`
- `/visits/{visit}/payments`
- `/visits/{visit}/timeline`

### 7.8 Policies and permissions
Add permissions:
- `visit.view`, `visit.create`, `visit.update`, `visit.close`
- `triage.perform`
- `consultation.perform`
- `billing.manage`, `payment.receive`

Enforce at controller and UI level.

### 7.9 Events and listeners
Events:
- `VisitCreated`
- `VisitTriaged`
- `ConsultationCompleted`
- `VisitAdmitted`
- `VisitClosed`
- `PaymentRecorded`

Listeners:
- queue notification updates
- automatic billing recalculation
- audit log creation

---

## 8) Frontend Implementation Plan

### 8.1 Type definitions
Create TS types in `resources/js/types/visit.ts`:
- `PatientVisit`
- `TriageRecord`
- `Consultation`
- `VisitBilling`
- `VisitTimelineEvent`

### 8.2 Pages
Create pages:
- `resources/js/pages/visits/index.tsx` (queue/list)
- `resources/js/pages/visits/create.tsx` (check-in)
- `resources/js/pages/visits/show.tsx` (timeline)
- `resources/js/pages/visits/triage.tsx`
- `resources/js/pages/visits/consultation.tsx`
- `resources/js/pages/visits/billing.tsx`

### 8.3 UI sections
Check-in form sections:
- Patient and payer selection
- Visit context (type, clinic, doctor, source)
- Reason and complaint
- Queue metadata

Visit detail sections:
- Status timeline
- Triage snapshot + latest vitals
- Consultation notes and diagnosis
- Orders summary (lab/imaging/pharmacy)
- Financial summary and payments

### 8.4 UX workflow
- From patient index: `Start Visit`
- On create success: redirect to visit detail
- Triage card appears if status is `registered`
- Consultation card locked until triage complete unless emergency override
- Billing tab always visible with role-based actions

---

## 9) Billing Integration Plan

- Charge generation points:
  - consultation completion
  - each lab/imaging/procedure request item
  - dispensed medication
- Billing recalculation strategy:
  - synchronous per transaction for totals
  - fallback nightly reconciliation command
- Insurance handling:
  - split patient vs insurance responsibility at visit billing level
  - persist authorization and claim metadata on billing

---

## 10) Reporting and Analytics

Add visit KPIs:
- Daily visits by clinic/doctor/type
- Waiting time: check-in to triage, triage to doctor
- Disposition rates (discharged vs admitted)
- Revenue per visit and collection rate
- Cancellation and no-consultation rates

---

## 11) Testing Plan

### 11.1 Unit tests
- Status transition guard rules
- Billing math and payment allocation
- Insurance validity checks

### 11.2 Feature tests
- Walk-in visit creation
- Appointment to visit conversion
- Triage then consultation happy path
- Admission path
- Discharge with full payment and with partial payment
- Permission denial paths

### 11.3 Regression tests
- Patient module unaffected
- Existing appointment workflows unaffected

---

## 12) Phased Delivery Plan

### Phase 1 - Foundations
- enums, migrations, models, relationships, factories, seeders
- visit create/list/show endpoints

### Phase 2 - Clinical workflow
- triage + vitals + consultation flows
- status transition automation

### Phase 3 - Financial workflow
- charges, billing, payments, invoice generation

### Phase 4 - UX hardening
- queue dashboard, timelines, filters, role-specific UX

### Phase 5 - Reporting and stabilization
- KPI dashboards, audits, performance tuning, load checks

---

## 13) Acceptance Criteria (Definition of Done)

- A staff member can create a visit for walk-in or appointment patient.
- Queue and triage can be completed and logged with timestamps.
- Consultation can be documented and outcome updates visit status.
- Orders and medication can generate traceable visit charges.
- Billing totals are correct and payments update balance/status.
- Visit can be closed only when validation rules pass.
- Full timeline is visible (status logs, clinical events, financial events).
- Tests cover critical happy paths and guard rails.

---

## 14) Risks and Mitigations

- Risk: status drift between modules.
  - Mitigation: central `TransitionVisitStatus` action + log table.
- Risk: duplicate or missing charges.
  - Mitigation: `source_table/source_id` idempotency guard.
- Risk: queue bottlenecks at peak times.
  - Mitigation: indexes on `(clinic_id, status, created_at)` and `(doctor_id, status)`.
- Risk: insurance disputes.
  - Mitigation: per-visit payer snapshot and authorization capture.

---

## 15) Build Checklist (Execution Order)

1. Correct schema inconsistencies listed in Section 4.
2. Implement added fields and supporting tables from Section 5.
3. Create enums + models + relationships.
4. Implement visit lifecycle actions/services.
5. Add controllers and routes.
6. Build frontend pages and navigation entry points.
7. Implement billing auto-charge hooks.
8. Add tests (unit + feature).
9. Run migration + seed smoke checks.
10. UAT with OPD and ER workflows.
11. Deploy behind feature flag.

---

## 16) Recommended Next Build Ticket Split

- Ticket A: Visit foundations (schema + model + CRUD)
- Ticket B: Queue and triage
- Ticket C: Consultation + status machine
- Ticket D: Charges, billing, payments
- Ticket E: Timeline + reports + hardening

This document is the implementation baseline for patient visit module development.
