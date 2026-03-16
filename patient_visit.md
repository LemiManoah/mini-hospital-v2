# Patient Visit Module - Implementation Status

**Date:** March 13, 2026
**Status:** Phase 1 partially implemented
**Primary Reference:** `hospital_database_schema.md`

---

## 1) Goal

Keep patient demographics on `patients`, keep payer choice on the visit, and make front-desk registration a single transaction that creates:
- patient
- visit
- visit payer snapshot

The visit workflow now targets these statuses:
- `registered`
- `in_progress`
- `awaiting_payment` (defined, not yet used in flow)
- `completed`
- `cancelled`

---

## 2) What Was Done

### 2.1 Registration flow
Implemented a single registration flow that creates patient + visit + payer in one DB transaction.

Done:
- Added `RegisterPatientAndStartVisit` action
- Patient create screen now includes visit fields and billing type
- Registration success message now reflects both patient creation and visit start

Current behavior:
- Front desk can register a new patient and immediately start a visit from one screen
- If any part fails, the transaction rolls back

### 2.2 Payer ownership moved from patient to visit
The design was changed so payer data is no longer stored on the patient record.

Done:
- Removed patient-level default payer from `patients` migration
- Replaced patient insurance persistence with visit-level payer persistence
- Added `visit_payers` table with:
  - `patient_visit_id`
  - `billing_type`
  - `insurance_company_id`
  - `insurance_package_id`
- Left out verification fields as requested:
  - `member_id`
  - `verification_status`
  - `approval_reference`
  - `verified_at`
  - `verified_by`

Current principle now implemented:
- patient = demographics only
- visit = workflow record
- visit payer = billing snapshot for that visit

### 2.3 Visit model and status defaults
Done:
- Added `facility_branch_id` to visit
- Renamed timing semantics to `registered_at` and `registered_by`
- Simplified `VisitStatus` enum to:
  - `registered`
  - `in_progress`
  - `awaiting_payment`
  - `completed`
  - `cancelled`
- Added `TransitionPatientVisitStatus` action
- Implemented automatic `started_at` stamping when moving to `in_progress`

Practical defaults implemented:
- one active non-terminal visit per patient
- no payment gating enforced yet
- payer snapshot is frozen on visit creation
- `awaiting_payment` exists but is not wired into the workflow yet

### 2.4 Existing patient visit start flow updated
Done:
- Existing patients can still start new visits from the patient profile
- Starting a visit now requires selecting a per-visit billing type
- Insurance selection now happens per visit instead of per patient

### 2.5 Frontend updates
Done:
- Reworked patient registration page into `Register Patient & Start Visit`
- Removed billing section from patient edit page
- Updated patient profile page to display payer details per visit
- Simplified patient list page to stop showing patient-owned insurance state
- Updated TypeScript patient/visit types to match the new model

---

## 3) What Still Needs To Be Done

### 3.1 Clinical workflow
Not done yet:
- triage records
- vital signs
- consultation records
- automatic status promotion from actual clinical activity

Needed next:
- create `triage_records`
- create `consultations`
- wire status transitions so any first clinical action moves visit from `registered` to `in_progress`

### 3.2 Queue workflow
Not done yet:
- triage queue entries
- clinic queue entries
- queue dashboard
- queue numbering and priority management

Needed next:
- queue table or event table
- registration-time optional queue insertion
- visit list / queue page for staff

### 3.3 Visit detail module
Current patient profile only shows summary cards and visit history.

Still needed:
- dedicated visit details page
- visit timeline
- clinician-facing visit workspace
- structured encounter notes

### 3.4 Billing workflow
Only payer snapshot is in place. Full billing is not.

Still needed:
- `visit_charges`
- `visit_billings`
- `payments`
- price freeze on charge creation
- invoice and payment summaries
- optional payment-gating logic before care continues

### 3.5 Insurance workflow
Minimal insurance selection is implemented, but claim workflow is not.

Still needed:
- package eligibility rules
- package-based pricing resolution
- claim/invoice generation for insurance companies

### 3.6 Audit and timeline support
Still needed:
- `visit_status_logs`
- immutable visit activity timeline
- actor and reason capture for transitions

### 3.7 Permissions and policies
Still needed:
- visit-specific permissions
- triage permissions
- billing/payment permissions
- close/cancel visit authorization rules

### 3.8 Testing
Not done yet for this refactor:
- feature tests for single-transaction registration
- feature tests for starting a visit on an existing patient
- guard test for one active visit rule
- status transition tests for `TransitionPatientVisitStatus`

---

## 4) Current Gaps / Known Follow-up Items

### 4.1 Migration naming cleanup
The former patient insurance migration file was reused to create `visit_payers`.

Follow-up:
- rename the migration file for clarity if the team wants filenames to match table names exactly

### 4.2 Documentation alignment
Still needs updating in broader docs:
- `patient.md`
- `hospital_database_schema.md`
- any onboarding notes that still mention patient-owned insurance

### 4.3 Verification status intentionally omitted
This was intentionally not implemented in this pass.

If needed later, add a second pass for:
- member/policy identifier
- verification outcome
- insurer authorization reference
- who verified and when

---

## 5) Recommended Next Build Buckets

### Bucket 1: Triage foundations
- create `triage_records`
- build triage form/page
- move visit to `in_progress` when triage starts

### Bucket 2: Consultation foundations
- create consultation model/table
- clinician note workflow
- complete visit from clinician action

### Bucket 3: Queue operations
- triage queue entry model/table
- registration-time queue option
- queue dashboard for active visits

### Bucket 4: Billing foundations
- create `visit_charges`
- freeze price at charge time
- add `visit_billings` and `payments`

### Bucket 5: Visit timeline and audit
- add `visit_status_logs`
- add timeline UI on visit details page
- capture actor/reason on transitions

### Bucket 6: Tests and stabilization
- feature tests for new registration flow
- regression tests for patient pages
- status transition tests

---

## 6) Definition of Done for the Current Phase

This phase should be considered complete when all of the following are true:
- front desk can register patient + visit + payer in one transaction
- payer is no longer patient-owned in active application flow
- existing patients can start new visits with per-visit payer selection
- only one active visit is allowed at a time
- visit status model is simplified and usable for future workflow automation
- tests cover the registration and visit creation happy paths

---

## 7) Summary

### Implemented now
- single-screen patient registration plus visit start
- visit-owned payer snapshot
- simplified visit statuses
- one active visit rule
- patient pages updated to stop treating insurance as patient-owned

### Remaining major work
- triage
- consultation
- queueing
- charges and payments
- audit timeline
- tests

This document is now a post-implementation checkpoint for the patient visit module, not just a planning draft.
