# Patient Registration & Visit Flow Design

## Objective
Design a clean, real-world flow for:
1. Patient registration
2. Choosing payment mode (cash vs insurance)
3. Visit creation and handoff to triage/consultation

This is aligned with your `hospital_database_schema.md` and `implementation.md` Phase 4/5.

---

## Short Answer: When to Pick Cash vs Insurance

Use a two-step rule:

1. At patient registration: capture insurance profile(s) as optional demographic/coverage data.
2. At visit creation (check-in): choose the payer for that specific encounter (cash or one active insurance policy).

Reason:
- A patient can have insurance on file but still pay cash for a specific visit.
- One patient can have multiple insurance policies over time.
- Billing is visit-based (`visit_billings` links to `visit_id` and optional `insurance_id`), so payer decision belongs to the visit.

---

## Module Boundaries

## Phase 4 (Patient Master Data)
- `patients`
- `patient_addresses` (or `address_id` relation if you keep that design)
- `patient_allergies`
- `past_medical_histories`
- `patient_insurances` (and master insurance tables, see missing data)

## Phase 5 (Encounter/Visit)
- `patient_visits`
- `triage_records`
- `vital_signs`
- `consultations`

## Phase 8 (Finance)
- `visit_charges`
- `visit_billings`
- `payments`

---

## Proposed End-to-End Workflow

## 1) Patient Search First (Avoid Duplicate Registration)
Before showing a full registration form:
- Search by phone number
- Search by patient number (MRN)
- Search by name + date of birth

If found:
- Use existing patient record and proceed to Visit Check-in.

If not found:
- Open New Patient Registration.

---

## 2) New Patient Registration
Create patient profile only (no billing yet).

Minimum required fields:
- `first_name`, `last_name`
- `gender`
- `phone_number`
- `date_of_birth` or `age + age_units`
- `country_id` (optional depending on local workflow)

Recommended at registration:
- Next of kin details
- Address
- Allergies summary flag (`has_allergies`)
- Insurance details (optional section)

Output:
- New `patients` row with generated `patient_number` (tenant-scoped unique).

---

## 3) Optional Insurance Capture During Registration
In registration UI, include:
- `Do you have insurance?` toggle
- If yes, capture policy data and save to `patient_insurances`

Do not mark visit payer yet here.

Why:
- Insurance info is patient master data.
- Payer decision is encounter-specific.

---

## 4) Visit Check-in / Visit Creation (This is where payer is selected)
When patient arrives for care, create `patient_visits` record.

Fields at check-in:
- `patient_id`
- `visit_type` (`opd_consultation`, `emergency`, etc.)
- `clinic_id` (if known at front desk)
- `doctor_id` (optional at desk, can be assigned after triage)
- `is_emergency`
- `appointment_id` if from schedule
- `payer_type` (UI field): `cash` | `insurance`
- If insurance selected: choose active policy from patient insurances

System actions:
- Generate `visit_number` (tenant-scoped unique)
- Set `status = registered`
- Create initial `visit_billings` header for this visit:
  - `status = pending` for cash
  - `status = insurance_pending` for insurance
  - set `insurance_id` if payer type is insurance

---

## 5) Triage and Clinical Flow
After visit creation:
- Queue patient to triage
- Create `triage_records` (1:1 with visit)
- Add `vital_signs`
- Move to consultation

Status transitions (recommended):
- `registered` -> `triaged` -> `waiting_consultation` -> `in_consultation`
- then downstream statuses (`waiting_lab`, `waiting_pharmacy`, etc.)

---

## 6) Billing and Payment Flow

For cash visits:
- Charges accumulate in `visit_charges`
- Cashier collects payment into `payments`
- `visit_billings.status`: `pending` -> `partial_paid` -> `fully_paid`

For insurance visits:
- Charges accumulate same way
- Billing marked `insurance_pending`
- Submit claim (`claim_number`, `claim_submitted_at`)
- Record insurer payment in `payments` using method `insurance`
- Any co-pay by patient recorded separately (cash/mobile/etc.)

---

## Data Model Additions You Should Add Before Building UI

Your docs reference `patient_insurances`, but practical implementation needs two extra masters:

1. `insurance_providers`
- `id`, `tenant_id`, `name`, `code`, `contact_phone`, `contact_email`, `is_active`

2. `insurance_plans`
- `id`, `tenant_id`, `provider_id`, `name`, `plan_code`, `coverage_percent`, `copay_type`, `copay_value`, `is_active`

3. `patient_insurances`
- `id`, `tenant_id`, `patient_id`, `provider_id`, `plan_id`
- `policy_number`, `member_number`
- `valid_from`, `valid_to`
- `status` (`active`, `inactive`, `expired`, `cancelled`)
- `is_primary`

4. `patient_visits` (small extension recommended)
- Add `payer_type` enum: `cash`, `insurance`, `waiver`, `corporate`
- Add `patient_insurance_id` nullable FK for selected encounter coverage

If you do not want to alter `patient_visits`, keep payer on `visit_billings` only, but still collect it at visit check-in UI.

---

## UI Design: Suggested Screens

1. `Patient Search / Quick Register`
- Search bar + recent patients
- `New Patient` button

2. `Patient Registration Form`
- Demographics tab
- Contact/kin tab
- Allergies/history quick capture
- Insurance tab (optional)

3. `Visit Check-in Form`
- Patient summary card
- Visit details (`visit_type`, clinic, doctor)
- Payer selector (`cash` / `insurance`)
- If insurance: policy selector + validity indicator

4. `Triage Queue`
- Pulls visits with status `registered`

---

## Validation Rules (Important)

Patient registration:
- unique `patient_number` per tenant
- prevent duplicate likely matches (same name + dob + phone soft warning)

Insurance selection at visit:
- policy must belong to same patient
- policy must be `active`
- visit date within `valid_from..valid_to`

Visit creation:
- `clinic_id` must belong to same tenant/branch context
- emergency visit can bypass scheduled appointment requirement

Billing:
- if `insurance_id` set, billing cannot be `fully_paid` unless `balance_amount = 0`

---

## Recommended Implementation Order for This Module

1. Migrations
- `patients`
- insurance master tables + `patient_insurances`
- `patient_visits`
- `visit_billings` stub creation logic

2. Backend
- `PatientController` (search, create, update)
- `VisitCheckinController` (create visit + payer selection)
- service class: `CreateVisitWithBillingContext`

3. Frontend
- Search/registration/check-in screens
- Queue page for triage intake

4. Tests
- duplicate prevention
- insurance validity checks
- cash vs insurance visit check-in paths
- branch/tenant isolation on all queries

---

## Practical Decision

If you want least friction:
- Make insurance optional at registration.
- Require payer selection at visit check-in every time.
- Default payer to:
  - last visit payer if returning patient
  - else `cash`
- Allow cashier/frontdesk to change payer before first charge is posted.

This gives a flexible flow without forcing wrong assumptions early.
