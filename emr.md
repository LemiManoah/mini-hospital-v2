# EMR Module Status Review

## Purpose

This document is a practical review of the current EMR state in this repository.

It focuses on:

- what modules exist
- what looks complete
- what is partial
- what is not done yet
- weak workflows that still need attention
- code quality suggestions
- the best next module to build

This review is based on the current Laravel routes, Inertia pages, tests, existing project notes, and the overall architecture visible in the codebase as of April 26, 2026.

## How to read the status labels

### Complete

A module is marked `complete` when it has a real working surface across most of these areas:

- routes
- controllers and actions
- frontend pages
- domain model
- at least some meaningful tests

This does not mean it is perfect. It means the module is operational and coherent enough to be treated as a real implemented part of the EMR.

### Partial

A module is marked `partial` when:

- the core workflow exists
- parts of the loop are missing
- the module is operational but not mature
- coverage, reporting, permissions, lifecycle rules, or downstream workflows are still incomplete

### Not done yet

A module is marked `not done yet` when:

- the business concept exists in the data model or code hints
- there is no proper operational workspace yet
- only fragments exist
- or the module is missing entirely

## High-level read

This is already a serious outpatient EMR, not a starter project.

The strongest implemented areas are:

- authentication and workspace setup
- patient registration and visit start
- triage
- doctor consultation and order entry
- laboratory workflow
- pharmacy workflow
- inventory foundations
- administration and master data
- subscription and facility-manager support workflows

The main pattern I see is this:

- core outpatient care flow is largely in place
- several operational modules are broad and usable
- the biggest gaps are now at the workflow edges, not the CRUD center

In other words, the app is no longer missing basic modules. It is mostly missing the last-mile workflows that make modules feel fully closed-loop.

## Module-by-module status

## 1. Authentication, users, roles, settings

**Status:** complete

What is present:

- login, logout, forgot password, reset password
- email verification
- two-factor authentication
- profile and password settings
- users and roles CRUD
- permission enforcement tests

Why this looks complete:

- the route surface is broad
- dedicated controllers and pages exist
- tests cover several auth and permission paths

Notes:

- this area looks comparatively mature
- policies are still underused in parts of the app, but the module itself is solid

## 2. SaaS onboarding, subscriptions, and facility manager

**Status:** complete

What is present:

- workspace registration
- onboarding flow
- subscription activation and checkout callbacks
- subscription package management
- facility manager dashboard and facility support workflows
- impersonation
- support notes and onboarding completion flows

Why this looks complete:

- the route set is substantial
- pages and actions exist
- facility manager has dedicated tests and documentation

Notes:

- this is one of the more product-complete non-clinical areas
- caching would help some repeated option payloads here

## 3. Administration and master data

**Status:** complete

What is present:

- general settings
- insurance setup surface
- departments
- clinics
- facility branches
- staff positions
- currencies and exchange rates
- units
- allergens
- referral facilities
- insurance companies and insurance packages
- appointment categories and modes
- subscription packages

Why this looks complete:

- many CRUD resources are wired end to end
- this layer clearly supports the clinical modules
- there is good breadth across admin foundations

Weakness:

- some master-data modules are complete as CRUD, but not always fully connected to downstream reporting or validation logic

## 4. Patient registration and patient management

**Status:** complete

What is present:

- patient CRUD pages
- returning patient flow
- patient allergies
- visit start from patient context
- patient show and edit surfaces
- print surfaces linked through visit flow

Why this looks complete:

- the routes and pages are established
- the module is clearly part of the real OPD flow
- tests exist around patient registration

Weakness:

- patient history longitudinal views still look thinner than the encounter-driven workflow itself

## 5. Visits, OPD workflow, and triage

**Status:** complete

What is present:

- visit listing and visit detail
- visit status update
- visit payments recording
- triage list and triage workspace
- vital signs capture
- visit summary and payment prints

Why this looks complete:

- this is a core clinical backbone of the app
- multiple downstream modules depend on it
- print and workflow guards exist

Weakness:

- visit completion and progression rules are becoming more complex, so this layer would benefit from even stronger workflow centralization

## 6. Consultation and doctor workspace

**Status:** partial, but strong

What is present:

- doctor consultations queue
- consultation workspace
- consultation start, save, update, and complete flows
- lab, imaging, prescription, and facility-service ordering from consultation
- referral facility support data

Why it is not marked complete:

- consultation lifecycle is still inferred more than explicitly modeled
- referral handling stops at documentation, not a closed loop
- follow-up capture exists, but follow-up execution workflow does not
- some product semantics still depend on shared understanding instead of a dedicated status model

What is good:

- this is already a meaningful doctor workbench
- the module is clinically central and clearly active

## 7. Appointments and scheduling

**Status:** partial

What is present:

- appointment pages
- queue and "my appointments" views
- doctor schedules and schedule exceptions
- appointment categories and modes
- appointment confirmation, cancellation, check-in, no-show, and reschedule routes

Why it is partial:

- the older docs still describe this as a planned module, which suggests the implementation has moved faster than the documentation
- test coverage appears thinner than other mature modules
- I do not yet see the same level of module-specific hardening that pharmacy, lab, and inventory already have

Current read:

- the module exists and is real
- it is probably usable
- but it still looks less battle-tested than the strongest clinical modules

## 8. Laboratory

**Status:** partial, leaning strong

What is present:

- laboratory dashboard
- management page
- incoming, results entry, review, and view queues
- worklist
- request item detail
- sample collection, receive, result entry, correction, review, and approval actions
- consumables logging
- result printing
- laboratory stock, requisitions, receipts, and movement pages

Why it is not marked fully complete:

- the workflow is broad, but still feels like it needs final operational hardening
- reporting and alerts are still limited
- broader lab administration and throughput reporting appear thin

What is good:

- this is clearly no longer a stub
- lab has one of the better end-to-end operational surfaces in the app

## 9. Pharmacy

**Status:** partial, but broad and operational

What is present:

- pharmacy queue
- prescription review
- dispense create, post, history, show, and print
- pharmacy stock, requisitions, receipts, and movements
- pharmacy POS with cart, checkout, sales history, payments, void, refund, and print

Why it is partial:

- authorization is improving but not yet fully clean across every pharmacy-adjacent route
- status and serializer consistency still need a final pass
- the module needs one more hardening cycle to be called mature

What is good:

- this is one of the broadest implemented modules in the codebase
- it is already operationally meaningful

## 10. Inventory and supply chain

**Status:** partial

What is present:

- inventory items and locations
- suppliers
- purchase orders
- goods receipts
- stock batches
- stock movements
- stock by location
- reconciliations
- requisitions
- pharmacy and laboratory inventory entry points

Why it is partial:

- inter-store transfers are still pending
- alerts and richer reports are still pending
- some specialized reconciliation workflows are still pending
- overall inventory hardening is still ongoing

Current read:

- the foundation is strong
- the core procurement and stock-ledger direction is correct
- this module is already useful, but not finished

## 11. Billing and payments

**Status:** partial

What is present:

- visit billing model
- visit charges
- payment recording
- billing recalculation
- print views for visit payment and visit summary
- billing-aware visit completion rules
- insurance-aware payer structures

Why it is partial:

- there is billing logic, but not yet a clearly expressed first-class billing module in the same way pharmacy or lab have operational workspaces
- insurance claims and revenue workflows appear foundational rather than fully surfaced
- reporting looks limited

Current read:

- billing exists as domain logic
- billing does not yet feel like a finished operational module

## 12. Reporting

**Status:** partial

What is present:

- daily revenue report
- stock level report
- appointment schedule report
- multiple print views for core records

Why it is partial:

- there is no broad reporting center for clinical, operational, and financial analytics
- module-specific reporting is still thin in consultation, lab, pharmacy, and administration

## 13. Imaging / Radiology

**Status:** not done yet

What is present:

- imaging orders can be created from visits and consultations
- imaging enums, DTOs, actions, and types exist
- visit summary can display imaging request counts

What is missing:

- no dedicated imaging or radiology module route group
- no imaging worklist or execution workspace
- no result capture workflow
- no imaging reporting or print surface comparable to lab

Current read:

- order entry exists
- operational fulfillment does not

This is one of the clearest "next real module" opportunities in the app.

## 14. Referral management

**Status:** not done yet

What is present:

- referral facilities CRUD
- referral fields inside consultation outcomes

What is missing:

- no referral tracking module
- no referral lifecycle
- no acceptance, completion, or feedback loop
- no internal vs external referral workflow distinction

Current read:

- referral data capture exists
- referral operations do not

## 15. Inpatient / admissions / wards

**Status:** not done yet

What is present:

- enums hint at inpatient concepts

What is missing:

- no inpatient route group
- no bed management
- no admission workflow
- no ward medication administration flow
- no discharge module

Current read:

- this project is still fundamentally outpatient-first

## 16. Audit trail, notifications, integrations, API

**Status:** not done yet

What is present:

- some prints
- some workflow protections

What is missing:

- dedicated audit log system
- notifications and reminders
- mobile/API surface
- real-time event flows
- integration-ready module boundaries for external systems

These are not small omissions. They are the kinds of cross-cutting capabilities that become important as the EMR matures.

## Best summary by status

### Complete

- Authentication and user security
- SaaS onboarding, subscriptions, and facility manager
- Administration and master data
- Patient registration and patient management
- Visits, OPD workflow, and triage

### Partial but meaningful

- Consultation
- Appointments and scheduling
- Laboratory
- Pharmacy
- Inventory and supply chain
- Billing and payments
- Reporting

### Not done yet

- Imaging / radiology operations
- Referral tracking workflow
- Inpatient / admissions / wards
- Audit trail
- Notifications and reminders
- Public API / mobile integration layer

## Weak flows I would call out

## 1. Consultation to referral is not closed-loop

The doctor can document a referral and choose a destination, but the system does not yet manage the referral as an operational object after that point.

Why this matters:

- no referral tracking
- no outcome visibility
- no handoff accountability

## 2. Consultation to imaging is one-sided

Imaging order creation exists, but there is no imaging department module to receive, schedule, perform, interpret, and complete those requests.

Why this matters:

- it leaves a visible hole in the clinical order chain
- lab has a downstream workspace, imaging does not

## 3. Billing exists more as engine than as full workspace

The billing domain logic is there, but the operational billing experience is still thin compared with the clinical modules.

Why this matters:

- difficult to reason about claim lifecycle
- thin finance-facing workflow surface
- reporting is limited

## 4. Appointment maturity looks behind its surface area

Appointments have routes and pages, but the module still feels newer and less proven than its size suggests.

Why this matters:

- documentation still trails implementation
- thinner visible tests
- likely higher risk of edge-case regressions

## 5. Module discoverability is weaker than module breadth

The main modules page shows only a small subset of the system even though the route surface is much broader.

Why this matters:

- the app has more capability than the landing experience communicates
- some modules may feel hidden or unevenly presented

## 6. Inventory and pharmacy boundaries are still settling

The overall direction is strong, but the inventory, requisition, dispense, and POS areas still need consistency work.

Why this matters:

- status semantics can drift
- payload shaping is duplicated
- authorization edges can become hard to reason about

## Suggested next module

## Best next module: Imaging / Radiology

If I were choosing the next major module to build, I would pick `Imaging / Radiology`.

Why this is the best next move:

- the clinical workflow already creates imaging orders
- the app already has imaging enums, DTOs, and order-entry support
- there is a clear hole after order creation
- lab already provides a reference pattern for what a downstream diagnostic module can look like

What this module should include:

- imaging queue
- imaging worklist
- status lifecycle from ordered to completed
- technician or radiographer execution workflow
- result entry and review
- radiology report output
- print/view result workflow
- optional integration later for image storage or PACS-style systems

Why not inpatient first:

- imaging closes an already-open outpatient loop
- inpatient would be much larger and more disruptive

Why not referral first:

- referral is important, but imaging has clearer technical foundations already in the codebase
- the user-facing gap is more obvious and immediate

## Secondary next-module candidates

If imaging is not the priority, the next strongest candidates are:

1. Referral workflow
2. Billing operations and claims workspace
3. Audit trail and activity history

## Code quality suggestions

## 1. Reduce controller bloat

Some workflows are already rich enough that controllers are carrying too much shaping and orchestration logic.

Recommended direction:

- continue moving business logic into Actions
- add dedicated read Actions or query/presenter layers for complex page payloads

## 2. Introduce more explicit workflow state models

Several modules still rely on inferred state rather than dedicated lifecycle objects or enums.

Strong candidates:

- consultation lifecycle
- referral lifecycle
- imaging lifecycle
- richer billing and claims lifecycle

## 3. Replace manual authorization checks with policies more consistently

There are still areas where access logic is enforced inline.

Why this matters:

- harder to audit
- harder to test
- easier to duplicate incorrectly

## 4. Add caching for repeated read-heavy payloads

This will matter especially for:

- dashboard metrics
- registration option lists
- facility manager option payloads
- stable master-data lookups

This is not the highest-priority architectural problem, but it is a worthwhile performance improvement.

## 5. Strengthen database indexes and query review

As usage grows, these areas will need attention:

- tenant and branch filtered queries
- queues
- stock movement lookups
- dashboard counts
- report queries

## 6. Expand targeted feature tests for weaker modules

The biggest testing needs appear to be:

- appointments
- billing workflow surfaces
- imaging once it lands
- referral flow once it becomes operational

## 7. Extract repeated payload serializers

This is especially relevant in:

- pharmacy
- inventory
- facility manager
- queue-driven modules

Why this matters:

- less duplication
- safer refactors
- better type consistency between backend and frontend

## 8. Add audit logging as a cross-cutting capability

High-value targets:

- patient updates
- consultation completion or amendment
- dispense posting
- stock reconciliation approval
- payment recording
- support impersonation and facility manager actions

## Suggested product and architecture priorities

If the goal is to make the EMR feel more complete rather than just bigger, I would prioritize work in this order:

1. Imaging module
2. Audit trail
3. Referral workflow
4. Billing workspace maturity
5. Inventory and pharmacy hardening
6. Reporting expansion

If the goal is to make the existing outpatient system safer and easier to maintain, I would prioritize this order instead:

1. Audit trail
2. Authorization cleanup
3. Controller/read-model cleanup
4. Targeted performance and caching work
5. Test expansion for partial modules

## Bottom line

This repository already contains a real outpatient EMR with meaningful operational depth.

The strongest parts are the outpatient backbone, administration, lab, pharmacy, inventory foundations, and workspace setup flows. The biggest remaining gaps are no longer "missing CRUD". They are mostly closed-loop workflow gaps, especially around imaging, referrals, billing maturity, and cross-cutting hardening like audit, consistency, and reporting.

The best single next module is imaging, because the system already creates imaging orders but has no true imaging operations workspace to complete that loop.
