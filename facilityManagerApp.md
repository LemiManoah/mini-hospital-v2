# Facility Manager Review, Current Status, and Recommended Workflow

**Date:** April 25, 2026  
**Goal:** Capture the current Facility Manager implementation accurately, distinguish what is done vs partial vs not done, and recommend the right workflow for onboarding and supporting facilities.

---

## 1) Short Answer

Yes, it makes sense to keep facility lifecycle work inside Facility Manager.

The right product shape is:

- Facility Manager is the support control center
- facility creation is available there as the support entry point
- creation and onboarding still reuse the existing workspace registration and onboarding pipeline

That means support can manage the whole lifecycle from one place without us building a second tenant-creation system.

---

## 2) Current Reality In The Codebase

### 2.1 What Facility Manager Already Does

Implemented under `/facility-manager`:

- dashboard
- facilities list
- facility export
- facility create flow
- facility overview
- facility audit
- branches page
- users page
- subscriptions page
- activity page
- support notes page
- support workflow flags
- impersonation listing
- start impersonation
- activate subscription
- mark subscription past due
- mark onboarding complete
- reopen onboarding

### 2.2 What Creates New Facilities

New facilities can now be created from:

- `GET /facility-manager/facilities/create`
- `POST /facility-manager/facilities`

That support-facing flow still reuses the existing workspace registration pipeline and then redirects back into Facility Manager.

The original self-serve pipeline still exists through:

- `GET /create-workspace`
- `POST /create-workspace`
- `/onboarding`

So the current split is:

- **Facility Manager** handles support-led creation, visibility, intervention, and follow-up
- **Create Workspace** still exists as the base tenant registration pipeline
- **Onboarding** remains the tenant-context setup journey

---

## 3) Done, Partial, Not Yet Done

## 3.1 Done

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
- facility create
- facility overview
- facility audit
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
- support workflow filter
- pagination
- CSV export
- counts for branches, departments, users, patients, visits, lab requests, and prescriptions

### Support actions already inside Facility Manager

Done:

- support notes creation
- support workflow status and priority updates
- activate subscription
- mark subscription past due
- complete onboarding
- reopen onboarding

### Impersonation support flow

Done:

- impersonation index page
- start impersonation from Facility Manager
- stop impersonation route

### Health and audit layer

Done:

- readiness checks
- tenant health summary
- facility audit page
- setup checklist style recommendations

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

### Facility Manager as the full support control center

Partial because:

- support can now create facilities directly from Facility Manager
- support can audit health and manage workflow flags there
- but there is still no direct one-click “resume onboarding” handoff from the overview into the tenant onboarding journey

### Subscription management

Partial because:

- support can activate and mark subscriptions past due
- but there is no richer subscription editing flow yet
- there is no package reassignment flow in-context
- there is no billing history or invoice-style operational tooling

### Activity analytics

Partial because:

- summary metrics, recent activity, health checks, exports, and support flags exist
- but there are no charts, trends, churn snapshots, or inactivity heuristics yet

### Onboarding support workflow

Partial because:

- support can reopen or complete onboarding
- support can impersonate a tenant user
- but there is still no explicit “resume onboarding as support” button tied directly to onboarding progress

---

## 3.3 Not Yet Done

### Trend analytics

Not yet done:

- charts
- trend lines
- low-usage detection
- dormant facility detection
- churn and inactivity heuristics

### Advanced support operations

Not yet done:

- support-note export
- reminder or task queue for follow-up
- structured assignment or ownership tracking for support cases

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

- **done**

### Phase 5: Health and audit layer

Includes:

- health warnings
- readiness checks
- audit page

Status:

- **done**

### Phase 6: Analytics depth and support operations

Includes:

- trends
- churn and inactivity detection
- exports
- support flags

Status:

- **partial**

What is done in Phase 6:

- facility CSV export
- support workflow flags
- support priority and follow-up scheduling fields

What is still open in Phase 6:

- charts and trends
- churn and inactivity heuristics
- reminder-style support queue
- support-note export

---

## 5) Recommended Workflow

This is the recommended workflow for new facility onboarding going forward.

### Recommended operator workflow

1. Support user opens `/facility-manager/facilities`
2. Support clicks `Create Facility`
3. System uses the existing workspace registration pipeline to create:
   - tenant
   - initial owner/admin user
   - initial subscription record
4. Support is redirected to the new facility’s Facility Manager detail page
5. Support reviews:
   - onboarding status
   - subscription status
   - health summary
   - support workflow status
6. Support uses impersonation when hands-on onboarding is needed
7. Tenant onboarding continues through the existing `/onboarding` steps
8. Support uses audit + support workflow flags to keep follow-up organized until the facility is stable

### Recommended next build order

1. Add a direct “Resume Onboarding” action from Facility Manager into the tenant onboarding journey
2. Add trend analytics and inactivity detection to the dashboard and facility activity views
3. Add support-note export and a reminder-style follow-up queue
4. Add optional support ownership or assignee tracking if the support team grows

---

## 6) Bottom Line

Facility Manager is already a real support console now.

What is done:

- support-only access
- dashboard
- facilities list
- facility export
- facility creation inside Facility Manager
- detail pages
- audit page
- support notes
- support workflow flags
- impersonation
- onboarding state actions
- subscription state actions
- workspace creation and onboarding pipeline

What is partial:

- full lifecycle support workflow from creation to onboarding to ongoing support
- richer subscription operations
- richer onboarding intervention flow
- deeper analytics

What is not yet done:

- trend analytics
- churn and inactivity heuristics
- support-note export
- reminder-style follow-up queue

Recommended decision:

- **yes, keep facility creation inside Facility Manager**
- **continue reusing the existing workspace registration and onboarding flow**
- **treat Phase 6 as partially complete, with exports and support flags shipped, and trends still pending**
