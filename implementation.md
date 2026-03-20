# Implementation Status & Gap Analysis

**Date:** March 17, 2026  
**Status:** Core OPD and administration modules are substantially implemented; billing, inventory, IPD, and result/dispensing workflows are still incomplete.  
**Primary References:** `patient_visit.md`, `hospital_database_schema.md`

---

## 1) Executive Summary

The project is no longer in a pure planning stage. A large portion of the administration, patient registration, appointment, visit, triage, consultation, and consultation-order workflows now exist in code with routes, controllers, models, pages, and some automated tests.

What is clearly implemented today:
- foundation master data such as countries, currencies, allergens, subscription packages, departments, clinics, staff positions, roles, users, insurance companies, insurance packages, drugs, and facility services
- multi-tenant and branch-aware application structure with tenant/branch scopes, branch switching, and branch isolation tests
- patient registration and returning-patient flows
- visit creation with per-visit payer snapshot
- active visit list and visit detail workspace
- triage workspace with triage record capture and repeated vital sign capture
- doctor consultation workspace
- consultation-linked orders for lab requests, imaging requests, prescriptions, and facility service orders
- appointment scheduling, queueing, rescheduling, confirmation, cancellation, no-show, and check-in to visit

What is only partial:
- tenant and facility branch administration is present architecturally, but not yet exposed as a complete CRUD/admin module in the current route surface
- queue workflow exists as operational pages, but not as dedicated queue tables/events as originally imagined
- insurance is implemented for visit payer snapshots and insurance master data, but not yet for claims/adjudication/invoicing against visits
- lab, imaging, and pharmacy ordering exist, but downstream execution and result/dispensing workflows are missing
- visit completion rules exist, but full audit/timeline/billing gating is not wired

What is still largely missing:
- billing and payments
- inventory and procurement
- inpatient admissions, wards, beds, and nursing operations
- lab results, radiology reports, and dispensing records
- client onboarding / SaaS self-service
- full compliance-oriented audit timeline

---

## 2) Current Reality vs Older Planning Docs

Two important project realities should drive future planning:

### 2.1 Patient visit work has moved far beyond the older draft

`patient_visit.md` is now closer to the truth than the older build-order assumptions. Triage, vitals, consultation, and queue pages are no longer "next"; they already exist.

### 2.2 The live app has intentionally diverged from parts of `hospital_database_schema.md`

Examples:
- patient payer ownership has been moved from patient-level concepts to `visit_payers`
- visit status handling in the app has been simplified compared with the more granular status ideas in the schema doc
- queue behavior currently relies on visit/appointment-driven screens rather than dedicated queue tables
- some schema-described domains like `wards`, `beds`, `charge_masters`, `visit_billings`, and `payments` are not yet implemented in the app

This means future work should use the codebase and `patient_visit.md` as the source of truth, then update `hospital_database_schema.md` afterward for alignment.

---

## 3) Phase-by-Phase Status

## Phase 0: Client Onboarding / SaaS Layer

### Done
- authentication, email verification, password reset, profile settings, and two-factor settings are present
- subscription packages exist as data and admin CRUD

### Partial
- support-only facility switching exists
- branch switching exists for active operational context

### Not Done
- self-service tenant signup
- onboarding wizard for first-time tenant setup
- subscription checkout and activation flow
- tenant-facing billing/subscription lifecycle

## Phase 1: Foundation & Base Helper Modules

### Done
- countries as seeded reference data for dropdowns and onboarding flows
- currencies
- addresses
- allergens
- subscription packages
- units

### Partial
- not every foundation entity appears to have the same level of polished admin UX, but the domain objects and CRUD patterns are broadly present
- countries are intentionally not exposed as day-to-day CRUD because they function as seeded lookup data rather than tenant-managed records

### Not Done
- no major foundation-table gap stands out here relative to the active app surface

## Phase 1.5: Roles & Permissions

### Done
- roles and permissions via Spatie
- role CRUD
- user CRUD
- browser coverage exists for role permissions

### Partial
- some module-specific policies/permission matrices still appear lightweight or implicit rather than deeply enforced everywhere

### Not Done
- fine-grained clinical/billing authorization model described in later planning notes

## Phase 2: Multi-Tenant Architecture & Security

### Done
- tenant-aware and branch-aware traits/scopes exist
- branch context support exists
- active branch middleware exists
- staff, staff positions, departments, clinics, users, and branch switching are implemented
- branch isolation test coverage exists
- facility branch administration exists as a tenant-admin surface
- active branch guard rails are now applied to the main authenticated operational routes
- branch isolation is implemented across visits, triage, consultations, appointments, clinics, staff, and doctor scheduling surfaces

### Partial
- some branch-owned supporting reference modules still need the same active-branch isolation pattern
- support switching is operational, but onboarding/provisioning still looks developer/admin driven

### Not Done
- end-user tenant onboarding flows
- polished tenant management dashboard

## Phase 3: Hospital Infrastructure & Service Catalogs

### Done
- clinics
- doctor schedules and schedule exceptions
- facility services
- drugs
- lab test catalog model usage in consultation ordering

### Partial
- lab test catalogs are used by doctor consultation order entry, but there is no complete visible lab-catalog admin module in the current route surface
- infrastructure for chargeable service ordering exists through facility service orders, but full billing catalog implementation does not

### Not Done
- wards
- beds
- charge masters
- medication stock / inventory catalog beyond the drug master

## Phase 4: Patient Registration & Demographics

### Done
- patient CRUD
- returning patient flow
- patient allergy management
- patient registration integrated with visit creation
- patient profile with visit context
- visit payer snapshot owned by the visit

### Partial
- demographics are well covered, but broader longitudinal history areas from the schema are not fully surfaced
- the schema doc still mentions concepts that the app has intentionally replaced

### Not Done
- past medical histories module
- patient portal/accounts
- file/document management for patient attachments

## Phase 5: Scheduling & OPD Workflow

### Done
- appointment creation and editing
- appointment confirmation, cancellation, no-show, rescheduling, and check-in
- appointment queue page
- patient visit creation for new and existing patients
- active visit list and visit detail page
- simplified visit status transitions
- triage queue page
- triage workspace
- vital sign capture
- doctor consultation index and detail workspace
- consultation drafting and completion
- visit completion assessment rules

### Partial
- queue workflow is implemented as pages and operational lists, but not through dedicated queue tables/events
- visit status progression is functional, but not as rich as the original schema vision
- visit completion is guarded by clinical logic, but not yet by payment/billing logic
- some workflow semantics are intentionally simplified compared with the original schema

### Not Done
- visit timeline / immutable activity feed
- richer queue prioritization/event tracking model
- clinician encounter note timeline outside the current consultation record shape

## Phase 6: Clinical Support Services

### Done
- lab request creation from consultation
- imaging request creation from consultation
- prescription creation from consultation
- facility service order creation from consultation

### Partial
- these domains currently support ordering, but not the full downstream lifecycle
- doctor consultation page already acts as the ordering hub, which is a strong foundation for later module expansion

### Not Done
- lab specimen workflow
- lab result entry and verification
- imaging scheduling workflow beyond request capture
- imaging studies
- radiology reports
- dispensing records
- pharmacy fulfillment workflow
- procedure requests / theatre workflow

## Phase 7: Inpatient Operations (IPD)

### Done
- no clear IPD implementation was found in the active route/controller surface

### Partial
- none of significance

### Not Done
- wards
- beds
- admissions
- nursing care
- medication administrations
- discharge workflow

## Phase 8: Billing, Finance & Auditing

### Done
- per-visit payer snapshot
- insurance companies and insurance packages admin
- insurance package pricing request/action layer exists
- billing-related enums and insurance invoice models exist in the domain layer

### Partial
- insurance master data is in place, but operational billing is not
- there are model-level pieces around insurance invoicing, but no visible completed workflow in routes/pages for visit billing and payments

### Not Done
- visit charges
- visit billings
- payments
- invoice generation for patient visits
- cashier workflow
- payment-gating before progression of care
- claims submission/adjudication workflow
- audit logs / access logs / timeline UI

---

## 4) Module Status Snapshot

## Clearly Implemented Modules

- Authentication and user settings
- Roles and permissions
- Countries, currencies, allergens, subscription packages, units
- Departments, staff positions, staff, clinics
- Insurance companies and insurance packages
- Doctor schedules and appointment support tables
- Patients and patient allergies
- Appointments
- Patient visits
- Triage and vital signs
- Doctor consultations
- Consultation-linked lab requests
- Consultation-linked imaging requests
- Consultation-linked prescriptions
- Consultation-linked facility service orders

## Implemented But Still Partial

- Multi-tenant and branch architecture
- Facility/service catalog layer
- Insurance domain
- Queue workflow
- Visit lifecycle automation
- Testing coverage

## Mostly Not Implemented Yet

- Billing and payments
- Inventory and procurement
- Lab result workflow
- Radiology reporting workflow
- Pharmacy dispensing workflow
- Inpatient operations
- Audit timeline / compliance reporting
- SaaS onboarding

---

## 5) Testing Status

### Present
- unit tests for creating vital signs
- unit tests for creating consultations
- unit tests for consultation orders
- unit tests for visit completion assessment
- feature coverage for branch isolation
- browser coverage for role/permission behavior

### Partial
- core OPD workflows have some meaningful coverage, but not enough end-to-end feature coverage yet

### Still Needed
- feature tests for patient registration plus visit start
- feature tests for starting visits on existing patients
- triage feature tests
- consultation feature tests
- appointment lifecycle feature tests
- consultation order feature tests
- billing and cashier tests when those modules exist

---

## 6) Highest-Impact Remaining Work

The codebase suggests this is the most logical remaining build order:

### 6.1 Billing Foundations
- create `visit_charges`
- create `visit_billings`
- create `payments`
- freeze prices when charges are generated
- connect consultation orders and service execution to charges

### 6.2 Execution Workflows For Existing Orders
- lab specimen and result workflow
- imaging scheduling/report workflow
- prescription fulfillment / dispensing workflow
- facility service completion workflow

### 6.3 Inventory & Pharmacy Operations
- stock items
- suppliers
- purchases
- stock adjustments
- dispense validation against stock

### 6.4 Audit & Timeline
- visit status/activity logs
- actor/reason tracking
- immutable visit timeline on the visit page

### 6.5 IPD
- wards and beds
- admissions
- nursing care
- medication administration

---

## 7) Recommended Definition Of "Current Phase Complete"

The current OPD phase should be considered reasonably complete when all of the following are true:
- patient registration, appointment check-in, and direct visit start are stable
- triage and vital capture are stable
- doctor consultation is stable
- consultation orders can be created reliably
- visit completion rules are enforced consistently
- visit timeline/audit basics exist
- tests cover the main OPD happy paths and critical guards

Billing, inventory, and IPD should be treated as subsequent phases rather than blockers to calling the current OPD foundation substantially implemented.

---

## 8) Bottom Line

This project already has a strong outpatient/admin core. The biggest shift needed in documentation is to stop describing triage, consultations, and queues as future work, because they are already in the application. The biggest actual gaps now are billing, order-fulfillment execution, inventory, IPD, and audit/timeline support.

---

## 9) Next Concrete Slice

The current active slice is **Phase 2.2: Branch-Owned Reference & Operational Surface Completion**.

Why this is the right next slice:
- Phase 2.1 is now substantially implemented, so the remaining risk is consistency rather than missing architecture
- the major workflow surfaces are branch-isolated, but a few supporting branch-owned admin modules still need to be brought into the same rule set
- finishing those surfaces gives a cleaner handoff into billing and execution workflows

This slice should focus on:
- appointment categories and similar branch-owned supporting references
- any remaining admin pages that still query tenant-wide despite active branch context
- final Phase 2 document cleanup so the next development target is unambiguous

See [phase2.md](c:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\phase2.md) for the full breakdown and definition of done.
