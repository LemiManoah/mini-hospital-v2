# Facility Manager Review, Current Status, and Recommended Workflow

**Date:** April 25, 2026  
**Goal:** Capture the current Facility Manager implementation accurately, distinguish what is done vs partial vs not done, and recommend the right workflow for onboarding new facilities.

---

## 1) Short Answer

Yes, it does make sense to have facility creation visible from Facility Manager, but not as a completely separate creation system.

The best fit is:

- Facility Manager should be the **support control center**
- facility creation should be available there as a **support entry point**
- creation should still reuse the existing **workspace registration + onboarding pipeline**

So the recommendation is:

- **add a `Create Facility` action inside Facility Manager**
- **reuse the current `/create-workspace` + `/onboarding` flow underneath**
- **do not build a second parallel tenant-creation workflow**

---

## 2) Current Reality In The Codebase

### 2.1 What Facility Manager Already Does

Implemented under `/facility-manager`:

- dashboard
- facilities list
- facility overview
- branches page
- users page
- subscriptions page
- activity page
- support notes page
- impersonation listing
- start impersonation
- activate subscription
- mark subscription past due
- mark onboarding complete
- reopen onboarding

Relevant routes:

- [routes/web.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/routes/web.php)

Relevant controllers:

- [app/Http/Controllers/FacilityManagerController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/FacilityManagerController.php)
- [app/Http/Controllers/FacilityImpersonationController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/FacilityImpersonationController.php)

Relevant pages:

- [resources/js/pages/facility-manager/dashboard.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/dashboard.tsx)
- [resources/js/pages/facility-manager/index.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/index.tsx)
- [resources/js/pages/facility-manager/show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/show.tsx)
- [resources/js/pages/facility-manager/branches.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/branches.tsx)
- [resources/js/pages/facility-manager/users.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/users.tsx)
- [resources/js/pages/facility-manager/subscriptions.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/subscriptions.tsx)
- [resources/js/pages/facility-manager/activity.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/activity.tsx)
- [resources/js/pages/facility-manager/support-notes.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/support-notes.tsx)
- [resources/js/pages/facility-manager/impersonation/index.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/impersonation/index.tsx)

### 2.2 What Creates New Facilities Today

New facilities are **not** created from Facility Manager today.

They are created through:

- `GET /create-workspace`
- `POST /create-workspace`
- then redirected into `/onboarding`

Relevant files:

- [app/Http/Controllers/WorkspaceRegistrationController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/WorkspaceRegistrationController.php)
- [resources/js/pages/saas/register.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/saas/register.tsx)
- [app/Http/Controllers/OnboardingController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/OnboardingController.php)
- [resources/js/pages/onboarding/show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/onboarding/show.tsx)

So the current split is:

- **Facility Manager** manages existing facilities
- **Create Workspace** creates new facilities
- **Onboarding** completes setup inside the tenant context

---

## 3) Done, Partial, Not Yet Done

## 3.1 Done

These parts are implemented and working as a real product slice.

### Access and navigation

Done:

- support-only route group for Facility Manager
- permission-based access using `tenants.view` and `tenants.update`
- support sidebar entry
- tenant detail navigation structure

### Main pages

Done:

- dashboard
- facilities index
- facility overview
- branches
- users
- subscriptions
- activity
- support notes

### Facility list behavior

Done:

- search
- onboarding filter
- subscription filter
- pagination
- counts for branches, departments, users, patients, visits, lab requests, and prescriptions

### Support actions already inside Facility Manager

Done:

- support notes creation
- activate subscription
- mark subscription past due
- complete onboarding
- reopen onboarding

### Impersonation support flow

Done:

- impersonation index page
- start impersonation from Facility Manager
- stop impersonation route

### Onboarding pipeline

Done:

- tenant profile step
- primary branch step
- departments step
- first staff step
- completion path back into the app

### Workspace creation pipeline

Done:

- create workspace page
- create first tenant + user
- choose package
- handoff into onboarding

---

## 3.2 Partial

These parts exist, but they are not yet at the best workflow level.

### Facility Manager as the full support control center

Partial because:

- it manages existing facilities well
- but it does not yet let support staff create a new facility directly from the same console

### Subscription management

Partial because:

- support can activate and mark past due
- but there is no richer subscription editing flow yet
- no package reassignment flow in-context
- no billing history / invoice-style operational tooling

### Activity analytics

Partial because:

- summary metrics and recent activity exist
- but there are no charts, trends, snapshots, or inactivity heuristics

### Onboarding support workflow

Partial because:

- support can reopen or complete onboarding
- support can impersonate a tenant user
- but there is no explicit “resume onboarding as support” workflow button tied directly to the onboarding steps

### Facility creation support workflow

Partial because:

- the system can create facilities
- but Facility Manager does not expose that creation flow yet

---

## 3.3 Not Yet Done

These are still genuinely missing.

### Create Facility inside Facility Manager

Not yet done:

- no `Create Facility` button in Facility Manager
- no support-facing create flow under `/facility-manager`
- no direct handoff from Facility Manager into workspace creation

### Health and readiness checks

Not yet done:

- no active branch warning
- no verified users warning
- no clinicians configured warning
- no service catalog warning
- no stock / lab readiness checks
- no tenant health summary

### Configuration audit page

Not yet done:

- no facility audit page
- no setup checklist page
- no module readiness audit

### Trend analytics

Not yet done:

- no charts
- no trend lines
- no low-usage detection
- no dormant facility detection

### Export and advanced support tooling

Not yet done:

- no facility export
- no support-note export
- no support flags / escalations
- no reminders or task queue for follow-up

---

## 4) Updated Phase Status

### Phase 1: Facility Manager foundation

Includes:

- support access
- dashboard
- facilities list
- tenant detail navigation

Status:

- **done**

### Phase 2: Facility detail and operational visibility

Includes:

- overview
- branches
- users
- subscriptions
- activity
- support notes

Status:

- **done**

### Phase 3: Support intervention actions

Includes:

- subscription state actions
- onboarding state actions
- impersonation
- support notes

Status:

- **done for the current slice**

### Phase 4: Facility creation from support console

Includes:

- create facility entry point inside Facility Manager
- support-safe handoff into onboarding

Status:

- **not yet done**

### Phase 5: Health and audit layer

Includes:

- health warnings
- readiness checks
- audit page

Status:

- **not yet done**

### Phase 6: Analytics depth and support operations

Includes:

- trends
- churn / inactivity detection
- exports
- support flags

Status:

- **not yet done**

---

## 5) Should Facility Creation Be In Facility Manager?

## 5.1 Why It Does Make Sense

It makes sense because Facility Manager is already the support operator’s home for:

- reviewing facility state
- intervening in onboarding
- intervening in subscriptions
- impersonating tenant users

Adding creation there gives support a complete lifecycle:

- create facility
- monitor onboarding
- unblock setup
- activate subscription
- manage support follow-up

That is a clean mental model.

## 5.2 What Should Not Happen

It should **not** become a second unrelated onboarding engine.

That would create duplicate logic for:

- tenant creation
- owner user creation
- package selection
- onboarding step transitions

The app already has those flows. Duplicating them would create maintenance drift.

## 5.3 Best Product Shape

Best shape:

- support user clicks `Create Facility` inside Facility Manager
- Facility Manager opens a support-friendly version of the existing workspace registration flow
- after creation, support is taken to the new facility record
- support can then:
  - view status
  - impersonate
  - resume onboarding
  - manage subscription state

---

## 6) Recommended Workflow

This is the recommended workflow for new facility onboarding going forward.

### Recommended operator workflow

1. Support user opens `/facility-manager/facilities`
2. Support clicks `Create Facility`
3. System uses the existing workspace registration pipeline to create:
   - tenant
   - initial owner/admin user
   - initial subscription record
4. After creation, support is redirected to the new facility’s Facility Manager detail page
5. The detail page clearly shows:
   - onboarding status
   - subscription status
   - quick action to impersonate
   - quick action to resume onboarding
6. Support uses impersonation to enter the tenant context when hands-on onboarding is needed
7. Tenant onboarding continues through the existing `/onboarding` steps
8. When setup is complete, Facility Manager becomes the ongoing support console for that facility

### Recommended UI additions

Add to Facility Manager:

- `Create Facility` primary button on dashboard and facilities index
- optional `Resume Onboarding` action on facility detail
- optional `Open Onboarding Status` card on facility overview

### Recommended implementation strategy

Preferred:

- reuse `WorkspaceRegistrationController`
- reuse `RegisterWorkspace`
- reuse `OnboardingController`
- add a support-facing entry point and redirect flow

Avoid:

- duplicate controller logic for tenant creation
- duplicate onboarding forms under a second route tree

---

## 7) Recommended Next Build Order

### 1. Add `Create Facility` entry point to Facility Manager

Why first:

- highest workflow value
- closes the biggest product gap
- avoids support having to leave the management console for creation

### 2. Add “Resume Onboarding” support action

Why next:

- creation and onboarding should feel like one lifecycle
- support should be able to move directly from Facility Manager into the tenant onboarding journey

### 3. Add health / readiness indicators on facility overview

Why next:

- support needs to quickly know why a facility is stuck

### 4. Add a dedicated audit page

Why next:

- overview flags tell support something is wrong
- audit tells support exactly what is missing

### 5. Add trend analytics and exports

Why later:

- useful, but not as urgent as creation + onboarding + health workflow coherence

---

## 8) Bottom Line

Facility Manager is already a real support console for existing facilities.

What is done:

- support-only access
- dashboard
- facilities list
- detail pages
- support notes
- impersonation
- onboarding state actions
- subscription state actions
- workspace creation and onboarding pipeline outside Facility Manager

What is partial:

- full lifecycle support workflow from creation to onboarding to support
- richer subscription operations
- richer onboarding intervention flow
- deeper analytics

What is not yet done:

- support-facing `Create Facility` inside Facility Manager
- health checks
- audit page
- trends
- exports

Recommended decision:

- **yes, put facility creation into Facility Manager**
- **but implement it by reusing the existing workspace registration and onboarding flow**
- **do not create a separate second facility-creation system**
