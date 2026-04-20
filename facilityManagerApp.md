# Facility Manager Panel Review and Updated Implementation Guide

**Date:** April 20, 2026  
**Goal:** Reassess the Facility Manager work against the current codebase, clarify what is actually complete, what is partial, what is still missing, and define the right next implementation order.

---

## 1) Short Answer

No, the Facility Manager app is **not fully complete yet**.

But it is also no longer just a plan.

The current codebase already has a solid support-facing Facility Manager slice with:

- support-only access control
- dashboard
- facilities list
- facility overview
- branches page
- users page
- subscriptions page
- activity page
- support notes

It also has related support operations implemented in the separate `facility-switcher` flow, such as:

- switching into a tenant
- activating a subscription
- marking subscription past due
- completing onboarding
- reopening onboarding

So the correct current read is:

- **core Facility Manager foundation: implemented**
- **operational support tooling: partially implemented**
- **health, audit, analytics depth, and advanced support controls: not yet complete**

---

## 2) What Is Actually Implemented Now

### 2.1 Access Model and Navigation

Implemented:

- support-only route group under `/facility-manager`
- support-only route group under `/facility-switcher`
- `Facility Manager` sidebar entry for support / platform-level users
- permission checks based on `tenants.view` and `tenants.update`
- policy-based tenant authorization on detail pages

Relevant files:

- [routes/web.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/routes/web.php)
- [resources/js/components/app-sidebar.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/app-sidebar.tsx)
- [app/Http/Controllers/FacilityManagerController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/FacilityManagerController.php)
- [app/Http/Controllers/FacilitySwitcherController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/FacilitySwitcherController.php)

Assessment:

- this part is **complete for the current slice**

### 2.2 Main Facility Manager Pages

Implemented routes and pages:

- `/facility-manager/dashboard`
- `/facility-manager/facilities`
- `/facility-manager/facilities/{tenant}`
- `/facility-manager/facilities/{tenant}/branches`
- `/facility-manager/facilities/{tenant}/users`
- `/facility-manager/facilities/{tenant}/subscriptions`
- `/facility-manager/facilities/{tenant}/activity`
- `/facility-manager/facilities/{tenant}/support-notes`

Frontend pages exist in:

- [resources/js/pages/facility-manager/dashboard.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/dashboard.tsx)
- [resources/js/pages/facility-manager/index.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/index.tsx)
- [resources/js/pages/facility-manager/show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/show.tsx)
- [resources/js/pages/facility-manager/branches.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/branches.tsx)
- [resources/js/pages/facility-manager/users.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/users.tsx)
- [resources/js/pages/facility-manager/subscriptions.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/subscriptions.tsx)
- [resources/js/pages/facility-manager/activity.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/activity.tsx)
- [resources/js/pages/facility-manager/support-notes.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/support-notes.tsx)

Assessment:

- this page structure is **implemented**

### 2.3 Facilities List

Implemented on the list page:

- tenant name
- domain
- onboarding status filtering
- subscription status filtering
- counts for branches, departments, users, patients, visits, lab requests, and prescriptions
- pagination
- searchable facility list

Assessment:

- this is **more than partial**
- the list page is **functionally complete for the current generation**

Still missing later:

- package filter
- last activity column
- health warning column
- export
- bulk support actions

### 2.4 Facility Overview Page

Implemented:

- tenant identity
- country / address context
- onboarding state
- current subscription summary
- recent users
- recent subscription history
- usage counters
- last activity timestamps for visits, lab requests, prescriptions, and support notes
- quick overview of branches and departments

Assessment:

- this is **implemented**
- but still not the final “deep support control center”

### 2.5 Branches Page

Implemented:

- total branches
- active branches
- main branches
- store-enabled count
- branch list with:
  - name
  - code
  - status
  - main branch flag
  - store-enabled flag
  - staff count
  - currency
  - address summary

Assessment:

- **implemented**

### 2.6 Users Page

Implemented:

- tenant-scoped users listing
- search
- active/inactive filtering
- verified count
- active staff count
- user role display
- staff position display
- branch assignments
- employee number
- email verification state
- last login timestamp

Assessment:

- **implemented**

### 2.7 Subscriptions Page

Implemented:

- current subscription display
- subscription history
- counts of active, trial, and past-due records

Assessment:

- **implemented as a read-and-review page**

Important nuance:

- operational subscription actions are not handled directly here yet
- they are still handled through the separate `facility-switcher` support flow

So this page is:

- **implemented for visibility**
- **partial for direct operational control**

### 2.8 Activity Page

Implemented:

- visits in the last 7 days
- consultations in the last 30 days
- lab requests in the last 30 days
- prescriptions in the last 30 days
- service orders in the last 30 days
- recent activity feed

Assessment:

- **implemented as summary analytics**
- **partial as a full analytics module**

Missing:

- trend charts
- day-by-day trend lines
- inactive facility detection
- module usage trend analysis

### 2.9 Support Notes

Implemented:

- `tenant_support_notes` table
- support note model and persistence
- pinned notes
- support note history
- note creation

Relevant files:

- [database/migrations/2026_04_11_130000_create_tenant_support_notes_table.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/database/migrations/2026_04_11_130000_create_tenant_support_notes_table.php)
- [app/Models/TenantSupportNote.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/TenantSupportNote.php)
- [app/Http/Requests/StoreTenantSupportNoteRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreTenantSupportNoteRequest.php)

Assessment:

- **implemented**

### 2.10 Separate Facility Switcher Support Operations

Implemented outside the Facility Manager pages:

- `/facility-switcher`
- `/facility-switcher/{tenant}`
- switch into tenant
- activate subscription
- mark subscription past due
- complete onboarding
- reopen onboarding

Relevant files:

- [resources/js/pages/facility-switcher/index.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-switcher/index.tsx)
- [resources/js/pages/facility-switcher/show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-switcher/show.tsx)
- [app/Services/SwitchTenantContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Services/SwitchTenantContext.php)

Assessment:

- these operations are **implemented**
- but the Facility Manager experience is still **partially fragmented**, because the core support operator has to move between:
  - `facility-manager`
  - `facility-switcher`

---

## 3) What Is Partial

These areas exist, but are not yet at the level originally envisioned.

### 3.1 Operational Controls Are Split Across Two Support Areas

Current state:

- visibility and analytics live in `Facility Manager`
- some support actions live in `Facility Switcher`

Why this is partial:

- the support operator does not yet get one unified tenant control center
- action placement is split
- the mental model is still more “switcher + manager” than one rich support console

### 3.2 Activity Analytics Exist, But Not Full Analytics

Current state:

- summary counts and recent activity feed exist

Missing:

- trend charts
- module usage trends
- growth / decline indicators
- inactivity heuristics
- tenant comparison snapshots

### 3.3 Subscription Management Exists, But Not Full Subscription Operations In One Place

Current state:

- subscription visibility exists
- activation and past-due marking exist

Missing:

- direct package change flow inside Facility Manager
- full subscription edit/reassignment flow
- cleaner support action controls on the subscriptions page itself

### 3.4 Overview Page Is Strong, But Not Yet a Full Health Console

Current state:

- overview metrics and last-activity timestamps exist

Missing:

- health flags
- setup warnings
- operational risk signals
- missing configuration audits

---

## 4) What Is Not Yet Implemented

These items are still genuinely pending.

### 4.1 Health Checks

Not yet implemented:

- no active branch
- no active users
- no clinicians configured
- no facility services
- no inventory locations where expected
- no lab catalog where expected
- onboarding incomplete risk flags

### 4.2 Configuration Audit Page

Not yet implemented:

- tenant setup audit
- branch readiness audit
- service catalog readiness audit
- insurance configuration audit
- role coverage audit
- inventory readiness audit

### 4.3 Snapshot-Based Analytics

Not yet implemented:

- `tenant_health_snapshots`
- `tenant_usage_snapshots`
- daily or weekly historical rollups
- cached trend reporting

### 4.4 Inactive / Low-Usage Facility Detection

Not yet implemented:

- facilities with no recent users
- facilities with no recent visits
- facilities with expiring activity
- early churn-risk detection

### 4.5 Exports

Not yet implemented:

- facility list export
- subscription report export
- support notes export
- activity export

### 4.6 Richer Support Tooling

Not yet implemented:

- support flags / escalations
- support task reminders
- bulk platform actions
- richer impersonation safeguards
- more guided intervention workflows

---

## 5) Updated Status By Phase

### Phase 1: Better Facility List

Deliverables:

- facility manager index page
- searchable/filterable tenant list
- onboarding and subscription columns
- counts
- path into facility detail

Status:

- **completed**

### Phase 2: Facility Detail Experience

Deliverables:

- tenant overview
- branch summary
- user summary
- subscription summary
- activity summary
- support notes

Status:

- **completed**

### Phase 3: Operational Support Controls

Deliverables:

- onboarding controls
- subscription operations
- support notes
- switch-into-tenant action

Status:

- **partially completed**

Done:

- support notes
- switch-into-tenant action
- subscription activate / past-due actions
- onboarding complete / reopen actions

Still partial because:

- these actions are not yet unified inside the Facility Manager page set itself

### Phase 4: Health And Audit Layer

Deliverables:

- facility health checks
- missing setup warnings
- configuration audit page

Status:

- **not yet implemented**

### Phase 5: Analytics Depth

Deliverables:

- charts
- trends
- inactive facility detection
- snapshot reporting

Status:

- **partially completed**

Done:

- summary metrics
- recent activity feed

Pending:

- charts
- trends
- detection logic
- snapshots

### Phase 6: Advanced Support Tooling

Deliverables:

- exports
- support flags
- escalations
- richer intervention tooling

Status:

- **not yet implemented**

---

## 6) Recommended Next Order

This is the recommended order from here.

### 1. Unify Operational Controls Into Facility Manager

Why first:

- the underlying actions already exist
- this gives the biggest UX improvement fastest
- it turns Facility Manager into the real support console instead of a mostly read-only console plus a separate switcher

What to do:

- surface:
  - switch into tenant
  - activate subscription
  - mark subscription past due
  - complete onboarding
  - reopen onboarding
- directly from facility overview and subscriptions page

### 2. Add Facility Health Checks

Why next:

- highest support value
- immediately helps support identify broken or incomplete tenants

Suggested checks:

- no active branch
- no verified users
- no active clinicians
- no facility services
- no pharmacy or lab setup where expected
- no inventory locations where expected

### 3. Build Configuration Audit Page

Why next:

- health flags tell support *that* something is wrong
- audit page tells support *what specifically is missing*

Suggested page:

- `/facility-manager/facilities/{tenant}/audit`

### 4. Add Trend Analytics and Low-Usage Detection

Why then:

- once support has a better operational console, trends become more valuable

Suggested outputs:

- 7-day vs previous 7-day
- 30-day activity trend
- inactive facility flags
- low-adoption facility flags

### 5. Add Exports and Support Flags

Why later:

- useful, but lower priority than health and audit

Suggested outputs:

- export tenants list
- export subscription state
- export support notes
- support escalation flags

---

## 7) Recommended Definition of Complete

The Facility Manager app should be considered complete when:

- support users have one unified management console
- facility list is searchable and operationally useful
- facility detail page acts as a real support control center
- support notes are auditable
- onboarding and subscription actions are available in-context
- health checks and configuration audits exist
- activity trends and low-usage detection exist
- exports and support flags exist where needed

Right now, the system is not there yet.

But it is well past the “plan only” stage.

---

## 8) Bottom Line

The Facility Manager app is **partially complete**.

What is already solid:

- support-only access
- core Facility Manager route structure
- dashboard
- facilities list
- detail pages
- support notes
- tenant switching and basic support actions through the switcher flow

What is still incomplete:

- unified in-context operational controls
- health checks
- configuration audits
- trend analytics
- inactive-facility detection
- exports
- richer support tooling

So the right current verdict is:

- **foundation complete**
- **operational slice partially complete**
- **advanced support/admin console not yet complete**
