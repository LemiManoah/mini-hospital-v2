# Mini-Hospital v2 System Overview

## Purpose

Mini-Hospital v2 is a multi-tenant outpatient hospital management system. It is built around the daily OPD flow: facility setup, patient registration, appointment scheduling, visit start, triage, consultation, orders, laboratory, pharmacy, inventory, billing, printing, reports, and support operations.

This document is written for developers joining the project. It explains what the system is, which modules exist, what is complete, what is partial, what is not done, the design choices already made, and what could be improved next.

## Current Technical Stack

- Backend: Laravel, PHP, Fortify, Spatie Permission, Wayfinder, DomPDF.
- Frontend: Inertia, React, TypeScript, Vite, Tailwind CSS, shadcn-style components, lucide icons.
- Database: relational schema with UUID-heavy models, tenant scoping, branch scoping, and workflow-specific tables.
- Testing: Pest feature and unit tests.
- Architecture style: controller plus action classes, DTOs, form requests, enum-backed workflow states, Inertia pages, Blade print/report views.

The installed package versions in this repository currently show Laravel 12, Inertia Laravel 3, Fortify 1, Wayfinder 0.1, Spatie Permission 7, Pest 4, and PHP 8.4+ constraints. Some older docs in the repository still mention Laravel 13, PHP 8.5, or Inertia v2. Treat `composer.json` and `package.json` as the source of truth for installed versions.

## High-Level System Shape

The application is a single Laravel/Inertia monolith. Server-side routes live mostly in `routes/web.php`; backend workflow logic lives in `app/Actions`; persistent records live in `app/Models`; frontend screens live in `resources/js/pages`; printable outputs and report PDFs use Blade views under `resources/views`.

Important directories:

- `app/Actions`: business operations such as creating visits, posting stock, approving lab results, finalizing POS sales, and recalculating billing.
- `app/Data`: DTOs used to move validated payloads into actions.
- `app/Enums`: domain states and option sets.
- `app/Http/Controllers`: route entry points and Inertia payload shaping.
- `app/Http/Requests`: validation and authorization request classes.
- `app/Models`: Eloquent models for tenants, patients, visits, stock, lab, pharmacy, billing, and support.
- `app/Support`: reusable workflow helpers and query helpers.
- `database/migrations`: schema definitions.
- `database/seeders`: base and demo data.
- `resources/js/pages`: Inertia React pages.
- `resources/js/components`: shared UI components.
- `resources/views/print`: document-style print views.
- `resources/views/reports`: report PDF views.
- `tests/Feature` and `tests/Unit`: feature, controller, action, data, and support tests.

## Core Design Choices

### Multi-Tenant and Branch-Aware Data

The system is built for multiple facilities. Most operational records belong to a tenant. Many workflows are also branch-aware. Branch context is enforced through middleware, support classes, model scopes, session state, and controller queries.

Developer implication: when adding a feature, always ask:

- Which tenant owns this record?
- Is this branch-specific?
- Should support users see it cross-tenant?
- Should ordinary users be limited to the active branch?
- Does the feature need tenant and branch indexes?

### Action Pattern

Business logic is intentionally placed in `app/Actions`. Controllers should validate, authorize, fetch models, call actions, and render or redirect. Actions perform the actual business operation.

This is already used for workflows such as:

- workspace registration
- appointment confirmation, check-in, cancellation, no-show, and rescheduling
- patient registration and visit start
- consultation creation and completion
- lab request, result entry, review, approval, correction, and specimen receipt
- purchase order approval
- goods receipt posting
- inventory requisition approval, rejection, cancellation, and issue
- reconciliation review, approval, rejection, and posting
- dispensing and pharmacy POS finalization, payment, void, and refund
- visit billing recalculation and payment recording

Developer implication: new business behavior should usually be added as an action with a single `handle()` method.

### DTOs and Form Requests

Validated input is commonly converted into DTOs under `app/Data`. This keeps actions cleaner and gives tests a stable structure.

Developer implication: prefer typed DTOs over passing raw request arrays into actions.

### Enum-Driven Workflows

Workflow states are represented with PHP enums. Examples include visit status, appointment status, prescription status, lab request status, inventory requisition status, reconciliation status, purchase order status, billing status, and POS sale/cart status.

Developer implication: add new states carefully. Check controllers, requests, UI filters, seeders, tests, and reports before changing enum values.

### Inertia Frontend

The frontend is React rendered through Inertia. Pages live in `resources/js/pages`. Shared UI lives in `resources/js/components`. Route calls should use generated route helpers or Wayfinder-style functions where available.

Developer implication: avoid hardcoding URLs in new frontend code when a route helper exists.

### Printing and Reports

The app uses Blade for printable documents and DomPDF-style report downloads. Existing print views include lab results, prescriptions, payment receipts, visit summaries, goods receipts, requisitions, dispensing records, and POS receipts.

Developer implication: for official documents, prefer Blade views under `resources/views/print` or `resources/views/reports` over trying to print React screens directly.

### Permissions

The app uses Spatie Permission. Routes are protected by middleware such as `permission:*`, support-only middleware, auth, email verification, and active branch middleware.

Developer implication: new modules need permission names, seed data, tests, and sidebar visibility rules.

## Status Legend

- Complete: usable end to end with routes, pages, backend actions, and reasonable tests or workflow coverage.
- Partial: meaningful implementation exists, but workflow edges, hardening, reporting, permissions, or UX are incomplete.
- Not done: only hints, data capture, or related pieces exist; no full operational module yet.

## Module Status Matrix

| Module | Status | Current read |
|---|---|---|
| Authentication, profile, password, email verification, 2FA | Complete | Fortify-backed auth flows, settings pages, tests. |
| Users, roles, permissions | Complete | CRUD and permission enforcement exist; policy usage could be more consistent. |
| SaaS workspace registration and onboarding | Complete | Workspace creation, onboarding steps, package selection, activation flow. |
| Facility manager and support console | Complete/Partial | Strong support console exists; advanced analytics and support tasking are still missing. |
| Impersonation | Complete/Partial | Support impersonation exists; audit and stricter reason policies can improve it. |
| Tenant and branch management | Complete | Facility branches, branch switching, active branch guard, tenant-aware support views. |
| Administration and master data | Complete | Broad CRUD foundations for departments, clinics, units, currencies, allergens, insurance, referral facilities, services, etc. |
| General settings | Partial | Settings registry and UI exist; workflow enforcement is partial. |
| Patient registration and management | Complete | Patient CRUD, returning patients, allergies, visit start, tests. |
| Visits and OPD workflow | Complete/Partial | Strong backbone; workflow rules are becoming complex and need continued centralization. |
| Triage and vital signs | Complete | Triage queue/workspace and vitals capture exist. |
| Consultation and doctor workspace | Partial but strong | Doctor queue, consultation workspace, orders; referral/follow-up closure is incomplete. |
| Appointments and scheduling | Partial | Booking, schedule, queue, check-in, status actions exist; maturity and tests lag behind stronger modules. |
| Laboratory | Partial but strong | Worklists, specimen/result workflow, correction, approval, printing, stock integration; reporting/hardening still needed. |
| Pharmacy dispensing | Partial but broad | Queue, prescription review, dispense draft/post/history/print; final permission and serializer cleanup needed. |
| Pharmacy POS | Mostly complete | Cart, hold/resume, checkout, sales, payments, voids, refunds, receipts, stock posting. |
| Inventory and supply chain | Partial | Items, locations, suppliers, POs, receipts, ledger, requisitions, reconciliations; transfers/alerts/reporting need work. |
| Billing and payments | Partial | Billing engine, charges, payments, receipts; lacks a full billing workspace and claims workflow. |
| Reports | Partial | Report framework and several P1 reports exist; most planned reports remain pending. |
| Printing | Partial but useful | Many key documents print; shared branding/settings and more documents can improve. |
| Audit trail | Partial planning | Actor columns and workflow timestamps exist; full activity log is not implemented yet. |
| Notifications | Not done | Documented plan exists; notification infrastructure is not implemented as a user-facing module. |
| Imaging / radiology | Not done operationally | Orders can be created; no imaging worklist/result workflow. |
| Referral management | Not done operationally | Referral facilities and consultation fields exist; no referral lifecycle module. |
| Inpatient/admissions/wards | Not done | Outpatient-first system; no admissions or bed management. |
| Public/mobile/API integrations | Not done | Primary app is web/Inertia; no mature external API surface documented. |

## Implemented Modules

### Authentication and Account Security

What exists:

- login and logout
- password reset and forgot-password flow
- email verification
- two-factor authentication screens and backend flow
- profile settings
- password settings
- user account deletion

Important files:

- `SessionController`
- `UserPasswordController`
- `UserEmailVerificationController`
- `UserEmailVerificationNotificationController`
- `UserTwoFactorAuthenticationController`
- `UserProfileController`
- Fortify provider and auth config

What could be better:

- central audit logging for security events
- clearer user activity history
- more explicit support controls around 2FA resets

### Users, Roles, and Permissions

What exists:

- user CRUD
- role CRUD
- Spatie Permission integration
- permission enforcement tests
- staff linkage for operational users

What could be better:

- consistent policy usage across all modules
- more granular permission naming for newer modules
- user activity and role-change audit history

### Workspace Registration, Onboarding, and Subscriptions

What exists:

- guest workspace registration
- tenant creation
- first user creation
- onboarding profile, branch, departments, and staff steps
- subscription package selection
- subscription activation and checkout callbacks
- support-side subscription activation and past-due marking

Important files:

- `WorkspaceRegistrationController`
- `OnboardingController`
- `SubscriptionActivationController`
- `RegisterWorkspace`
- `StartTenantSubscription`
- onboarding DTOs and actions

What could be better:

- richer subscription editing and package-change history
- stronger billing/invoice connection for SaaS subscription payments
- support task queue for onboarding follow-up

### Facility Manager and Support Console

What exists:

- support-only Facility Manager area
- facility dashboard
- facility list with search, filters, pagination, and CSV export
- facility creation from support console
- facility overview
- branches view
- users view
- subscriptions view
- activity view
- audit/readiness view
- support notes
- support workflow status and priority
- activate subscription, mark past due, complete onboarding, reopen onboarding
- support impersonation entry points

What is partial:

- support console is strong, but not yet a full CRM/support operations tool
- no trend charts or churn/inactivity analytics
- no support assignment, tasks, reminders, or support-note export
- no one-click "resume onboarding as support" flow directly tied to onboarding progress

### Administration and Master Data

What exists:

- departments
- staff positions
- staff
- clinics
- facility branches
- facility services
- countries and addresses
- currencies and exchange rates
- units
- allergens
- appointment categories and modes
- doctor schedules and exceptions
- insurance companies and insurance packages
- referral facilities
- subscription packages
- lab test categories, specimen types, result types, and lab catalogs

What could be better:

- stronger downstream validation for some master data
- consistent "created by / updated by" display
- cache stable option lists
- audit log for setting and master-data changes

### General Settings

What exists:

- administration section restructuring
- tenant general settings model and registry
- general settings page
- billing, currency, pharmacy, and laboratory setting foundations
- dedicated permissions for general settings
- some numbering and currency rules wired into workflows

What is partial:

- branch-level overrides are not fully implemented
- clinical workflow settings are not implemented
- inventory, documents, printing, notifications, security, and audit settings are mostly not implemented
- settings changes do not yet have a full audit trail

### Patient Registration and Patient Management

What exists:

- patient CRUD
- returning patient flow
- patient allergies
- patient show page
- visit creation from patient context
- patient registration tests

What could be better:

- richer longitudinal patient history
- better patient timeline combining visits, labs, prescriptions, payments, and documents
- fuller duplicate-patient detection

### Visits, OPD Workflow, and Triage

What exists:

- visit list and detail
- start visit workflow
- visit status transitions
- visit payment recording
- visit summary print
- payment receipt print
- triage queue and triage detail
- vital signs capture
- visit completion guard logic

Important files:

- `PatientVisitController`
- `VisitTriageController`
- `VisitVitalSignController`
- `VisitPaymentController`
- `TransitionPatientVisitStatus`
- `AssessPatientVisitCompletion`
- `VisitWorkflowGuard`

What could be better:

- workflow rules should remain centralized as complexity grows
- visit timeline should be easier to inspect
- richer status reasons and audit trail would help support and clinical review

### Consultation and Doctor Workspace

What exists:

- doctor consultation queue
- consultation detail/workspace
- start, store, update, and complete consultation flows
- consultation orders for lab, imaging, prescriptions, and facility services
- SOAP-like clinical documentation and outcome capture
- referral facility data available

What is partial:

- referral capture is not a closed-loop referral workflow
- follow-up capture exists, but follow-up execution is not a module
- consultation lifecycle could be more explicitly modeled
- imaging orders have no downstream imaging module

### Appointments and Scheduling

What exists:

- appointment index
- my appointments
- appointment queue
- create and show appointment pages
- confirm, cancel, no-show, reschedule, check-in
- doctor schedules
- schedule exceptions
- appointment categories and modes
- check-in can hand off to visit flow

What is partial:

- appointment module appears usable but less hardened than lab, pharmacy, and inventory
- reminder notifications are not implemented
- schedule utilization and no-show analytics are not built out
- product rules around capacity, overlapping, and follow-up scheduling should be reviewed

### Laboratory

What exists:

- lab dashboard
- lab management page
- lab test catalog administration
- specimen type and result type administration
- incoming investigations queue
- enter results queue
- review results queue
- view results queue
- worklist and request item detail
- collect sample
- receive request item
- enter structured results
- correct released result
- review and approve result
- result print
- consumable usage
- lab stock, requisitions, receipts, and movement pages
- branch isolation tests around lab behavior

What is partial:

- final operational hardening is still needed
- lab reporting is still thin
- turnaround-time analytics are not implemented
- direct lab-only patient workflow may need clearer product treatment
- notification hooks for released results are not implemented

### Pharmacy

What exists:

- pharmacy queue
- prescription review
- remaining quantity and stock availability display
- draft dispensing record creation
- posting dispense records to stock
- direct dispense flow
- external pharmacy completion flow
- dispensing history and export
- dispensing print
- pharmacy stock page
- pharmacy requisitions, receipts, and movements
- POS cart, checkout, sale finalization, payment, history, detail, receipt, void, and refund

What is partial:

- permission model is better than before but not fully exhaustive
- repeated controller payload shaping should be extracted
- stock/status semantics need final consistency review
- static analysis cleanup is ongoing

### Inventory and Supply Chain

What exists:

- inventory dashboard
- inventory items
- inventory locations
- stock by location
- suppliers
- purchase orders
- submit, approve, and cancel purchase orders
- goods receipts and posting
- inventory batches
- stock movements
- movement report
- requisitions
- submit, approve, reject, cancel, and issue requisitions
- inventory reconciliations
- submit, review, approve, reject, and post reconciliations
- shared pharmacy and laboratory inventory entry points

What is partial:

- inter-store transfers are not fully implemented
- expiry alerts and low-stock operational workflows are limited
- richer inventory reports remain pending
- some advanced reconciliation and return workflows are missing
- stock audit views could be improved

### Billing and Payments

What exists:

- visit billing records
- visit charges
- billing recalculation
- visit payment recording
- visit payment receipt print
- visit summary print
- charge syncing from lab requests and service orders
- insurance package and payer structures
- billing-aware visit completion

What is partial:

- billing exists more as an engine than a full workspace
- claims management is not fully surfaced
- insurance invoicing is foundational but not fully operational
- revenue reports are limited
- refunds and reversals need clearer first-class flows outside POS

### Reports

What exists:

- report index page
- CSV export from report generator
- daily revenue report
- stock level report
- low stock alert report
- appointment schedule report
- report actions under `app/Actions/Reports`
- report controllers under `app/Http/Controllers/Reports`
- report React pages under `resources/js/pages/reports`
- report Blade views under `resources/views/reports`

What is partial:

- many planned reports are still pending
- no full management analytics suite
- clinical, lab, pharmacy, inventory, billing, and administrative reports need expansion
- report permissions and audit should be reviewed as reports mature

### Printing

What exists:

- visit summary print
- payment receipt print
- prescription print
- lab result print
- inventory requisition print
- goods receipt print
- dispensing record print
- pharmacy POS receipt print
- shared print partials and layouts

What is partial:

- document settings and branding controls are still limited
- not all workflows have official printouts
- print/report styling could be further standardized

### Audit

What exists:

- audit/readiness checks in Facility Manager
- recent activity summary in Facility Manager
- actor fields such as `created_by`, `updated_by`, `approved_by`, `rejected_by`, `cancelled_by`, `issued_by`, `received_by`, and staff-level workflow fields
- workflow timestamps for approvals, releases, corrections, cancellations, and receiving
- a detailed `audit.md` implementation guide

What is partial:

- no full immutable activity log yet
- no patient/visit/lab/inventory/billing audit timelines
- no Spatie Activitylog implementation yet
- no retention or audit export policy

### Notifications

What exists:

- planning documentation for notifications
- workflow points where notifications should eventually fire

What is not done:

- no user-facing notification center
- no database notification implementation
- no appointment reminders
- no lab result released notifications
- no pharmacy or inventory notifications
- no subscription reminder notifications

### Imaging / Radiology

What exists:

- imaging request enums, DTOs, actions, and route entry points for order creation
- imaging orders can be placed from visits and consultations

What is not done:

- no imaging dashboard
- no imaging worklist
- no technician/radiologist workspace
- no result entry or approval workflow
- no imaging result print
- no imaging reports

### Referral Management

What exists:

- referral facilities CRUD
- consultation fields can capture referral-related outcomes

What is not done:

- no referral tracking module
- no referral lifecycle
- no referral acceptance, completion, or feedback loop
- no internal vs external referral workflow

### Inpatient / Admissions / Wards

What exists:

- some enum hints for future inpatient concepts

What is not done:

- no admission workflow
- no ward/bed management
- no inpatient medication administration
- no nursing notes workflow
- no discharge process

## Core Workflows

### New Facility Workflow

1. A workspace is created from the public registration flow or Facility Manager.
2. A tenant, first user, and initial subscription context are created.
3. The facility completes onboarding profile, branch, departments, and staff.
4. Subscription activation or checkout flow is completed.
5. The facility enters the application through the modules page.
6. Support can monitor health through Facility Manager.

### OPD Visit Workflow

1. Patient is registered or selected as returning.
2. A visit is started with payer and branch context.
3. Patient enters triage.
4. Triage/vitals are captured.
5. Doctor consultation begins.
6. Doctor documents consultation and creates orders.
7. Lab, pharmacy, service, imaging, and billing workflows proceed from those orders.
8. Visit completion checks whether required clinical and billing steps are done.
9. Visit summary and receipts can be printed.

### Laboratory Workflow

1. Lab order is created from visit or consultation.
2. Lab request items appear in lab queues.
3. Sample can be collected or request item received.
4. Result is entered.
5. Result is reviewed and approved.
6. Released result can be printed and seen by clinical users.
7. Released results can be corrected with a reason and reprocessed.
8. Consumables can be logged against lab request items.

### Pharmacy Workflow

1. Prescription is created from visit or consultation.
2. Prescription appears in pharmacy queue.
3. Pharmacy reviews remaining quantities and available stock.
4. Dispensing record can be drafted.
5. Posting a dispense reduces stock through allocations.
6. Dispense history and print views support review.
7. Separate POS handles over-the-counter pharmacy sales.

### Inventory Workflow

1. Items and locations are configured.
2. Purchase orders are created, submitted, approved, or cancelled.
3. Goods receipts are created and posted.
4. Posting receipts creates stock and movement records.
5. Requisitions are submitted, approved/rejected, issued, or cancelled.
6. Reconciliations are created, reviewed, approved/rejected, and posted.
7. Stock by location and movement reports support operational review.

### Billing Workflow

1. Visit billing is ensured for a visit.
2. Charges are created or synced from billable orders.
3. Billing is recalculated when charges or payments change.
4. Payments are recorded against the visit.
5. Receipts can be printed.
6. Visit completion can depend on billing status.

## Testing Coverage

The project has meaningful tests across:

- auth and user account flows
- permission enforcement
- dashboard and branch isolation
- patient registration
- facility manager
- subscription activation
- lab result workflow
- lab consumables
- inventory stock, requisitions, reconciliations, goods receipts, and movement reports
- pharmacy queue, dispensing, dispensing history, and POS phases
- print controllers
- reports
- DTOs and action classes

Where testing should grow:

- appointment workflow edge cases
- billing and insurance workflows
- consultation lifecycle and follow-up/referral behavior
- notification/audit once implemented
- report authorization and export correctness
- policy coverage for newer modules

## Known Weak Areas

### Controller Payload Duplication

Several controllers build large arrays for Inertia props. This is practical but leads to duplicated shaping logic.

Recommended improvement:

- introduce small presenters, resources, or payload builders for repeated shapes.
- start with pharmacy, lab, inventory, facility manager, and patient/visit payloads.

### Incomplete Closed Loops

Some modules create downstream records but do not manage the full lifecycle.

Examples:

- consultation can capture referral intent, but referral tracking is not a module.
- consultation can create imaging orders, but imaging fulfillment is missing.
- billing can record payments, but full claims and debtor operations are not surfaced.

### Audit Is Not Centralized

The app has actor columns and timestamps, but not a full activity log.

Recommended improvement:

- implement the Spatie Activitylog plan in `audit.md`.
- log business events from actions before enabling broad automatic model logging.

### Notifications Are Missing

Many workflows would benefit from notifications:

- appointment reminders
- lab result released
- prescription ready
- stock low
- requisition submitted
- subscription near expiry

Recommended improvement:

- start with Laravel database notifications.
- trigger notifications from actions after transactions succeed.

### Reports Are Young

The report framework exists but the catalog is incomplete.

Recommended improvement:

- prioritize daily-use P1 reports first: lab worklist, POS sales, stock movement, expiry, dispensing, revenue summary, visit census.

### Permissions Need Final Granularity

Some modules have broad or still-evolving permission boundaries.

Recommended improvement:

- standardize permission naming by module.
- test route access for each major workflow.
- keep sidebar visibility aligned with route permissions.

### Workflow State Could Be More Explicit

Some workflows rely on a combination of timestamps, related records, and statuses.

Recommended improvement:

- centralize state transitions in actions/support classes.
- avoid status mutation directly in controllers.
- add tests around invalid transitions.

## Recommended Next Work

### Highest Product Value

1. Build imaging/radiology operations: worklist, result entry, review, print, and reports.
2. Build audit logging using Spatie Activitylog.
3. Build notification infrastructure and start with lab result released, appointment reminders, and inventory requisition notifications.
4. Strengthen billing into a first-class workspace with outstanding balances, insurance claims, refunds, and revenue reports.
5. Finish inventory alerts, expiry reports, and transfer workflows.

### Highest Technical Value

1. Extract repeated Inertia payload shaping into presenters/resources.
2. Centralize workflow transitions further.
3. Add audit events to key actions.
4. Standardize permissions across pharmacy, lab, inventory, reports, and billing.
5. Add indexes for high-traffic tenant/branch/report queries.
6. Cache stable option lists and short-lived dashboard metrics.
7. Continue PHPStan/Pint/test hardening by module.

### Best First Refactor Targets

1. Pharmacy payload serializers.
2. Visit timeline payload builder.
3. Lab result workflow presenter.
4. Inventory stock movement presenter.
5. Facility manager metrics and activity query objects.

## Developer Onboarding Notes

Before changing a module:

1. Read the related markdown document if it exists.
2. Check routes in `routes/web.php`.
3. Check the controller and action classes.
4. Check the model relationships and enum states.
5. Check existing feature and unit tests.
6. Preserve tenant and branch scope.
7. Add or update tests for the affected workflow.
8. Run the smallest relevant test set.
9. Run Pint for PHP changes.

Useful module documents:

- `audit.md`
- `appointment.md`
- `billing.md`
- `consultation.md`
- `emr.md`
- `facilityManagerApp.md`
- `generalSettings.md`
- `impersonation.md`
- `inventory.md`
- `lab-module-plan.md`
- `notifications.md`
- `pharmacy.md`
- `pos.md`
- `print.md`
- `REPORTS.md`

## Bottom Line

Mini-Hospital v2 is no longer a simple starter app. It is a substantial outpatient EMR/HMIS with strong foundations in tenant setup, administration, patient registration, OPD visits, triage, consultation ordering, laboratory, pharmacy, inventory, printing, reports, and support operations.

The system is strongest where workflows are action-driven and tested. The main remaining work is not basic CRUD. It is operational maturity: closed-loop imaging and referrals, stronger billing operations, centralized audit logging, notifications, deeper reports, clearer permissions, richer timelines, and final hardening of high-volume modules.
