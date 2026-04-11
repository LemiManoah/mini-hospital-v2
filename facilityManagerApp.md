# Facility Manager Panel Plan

**Date:** April 11, 2026  
**Goal:** Manage SaaS tenants and facilities in a rich way without creating a separate application.

---

## 1) Short Answer

Yes, this should stay in the same app.

The better approach is a **support-only Facility Manager panel** inside the existing Laravel + Inertia codebase, with its own route prefix, navigation identity, permissions, and pages.

Why this is better than a second app:

- one codebase
- one auth/session system
- shared models and business rules
- less duplication
- faster support workflows
- lower maintenance cost

---

## 2) Current Implementation Status

Implemented now:

- support-only `Facility Manager` sidebar entry
- `/facility-manager/dashboard`
- `/facility-manager/facilities`
- `/facility-manager/facilities/{tenant}`
- `/facility-manager/facilities/{tenant}/branches`
- `/facility-manager/facilities/{tenant}/users`
- `/facility-manager/facilities/{tenant}/subscriptions`
- `/facility-manager/facilities/{tenant}/activity`
- `/facility-manager/facilities/{tenant}/support-notes`

Also implemented:

- support note storage through `tenant_support_notes`
- tenant switch action
- activate subscription
- mark subscription past due
- complete onboarding
- reopen onboarding

Still pending:

- health checks
- configuration audits
- snapshot-based analytics
- inactive-facility detection
- exports
- richer support tooling

---

## 3) Recommended Access Model

This module should be visible only to:

- support users
- super admins
- selected platform admins

Recommended permission family:

- `tenants.view`
- `tenants.update`
- `tenants.manage_subscription`
- `tenants.onboard`

Notes:

- right now the implemented Facility Manager pages use `tenants.view`
- support actions and support-note creation use `tenants.update`
- this is enough for the current slice, and more granular permissions can be introduced later if needed

---

## 4) Implemented IA

### Main Pages

- `facility-manager/dashboard`
- `facility-manager/facilities`
- `facility-manager/facilities/{tenant}`
- `facility-manager/facilities/{tenant}/branches`
- `facility-manager/facilities/{tenant}/users`
- `facility-manager/facilities/{tenant}/subscriptions`
- `facility-manager/facilities/{tenant}/activity`
- `facility-manager/facilities/{tenant}/support-notes`

### Why This Structure Works

It gives us:

- one platform overview
- one searchable facilities list
- one tenant overview page
- focused detail pages for the most important management areas

---

## 5) What The Facilities List Should Do

The facilities page should behave like a real management table, not just a switcher.

Implemented already:

- facility name
- domain
- onboarding state
- subscription state
- branch count
- user count
- patient count
- visit count
- lab count
- prescription count
- search and status filters

Still worth adding later:

- package filter
- trial ending soon filter
- last activity column
- health flags column
- suspend/reactivate controls

---

## 6) What The Facility Detail Experience Should Do

The overview page should act as the control center for the tenant, then hand off to focused child pages.

Implemented now:

### Overview

- facility identity
- onboarding state
- subscription state
- branch summary
- department summary
- recent users
- recent subscription history
- high-level usage cards

### Branches

- full branch list
- branch status
- staff count
- store-enabled flag
- main-branch indicator

### Users

- tenant-linked users
- search/filter
- position
- role list
- branch assignments
- verification state
- active/inactive state
- last login

### Subscriptions

- current subscription summary
- full subscription history
- support actions

### Activity

- visits in last 7 days
- consultations in last 30 days
- lab requests in last 30 days
- prescriptions in last 30 days
- service orders in last 30 days
- recent activity feed

### Support Notes

- internal note history
- pinned notes
- create support note

---

## 7) Support Features To Keep Building

### Onboarding Control

- view onboarding state
- mark onboarding complete
- reopen onboarding

### Subscription Operations

- activate subscription
- mark past due
- view subscription history
- later: assign/change package directly from this panel

### Support Notes

- implementation notes
- billing notes
- support reminders
- special configuration instructions

### Health Checks

Still to add:

- no active branch
- no active users
- no clinicians configured
- no lab catalog
- no inventory locations
- no facility services
- onboarding incomplete

---

## 8) Suggested Data Sources

Current pages are already drawing from:

- `tenants`
- `facility_branches`
- `users`
- `staff`
- `patients`
- `patient_visits`
- `consultations`
- `lab_requests`
- `prescriptions`
- `tenant_subscriptions`
- `tenant_support_notes`

Useful future support tables:

- `tenant_health_snapshots`
- `tenant_usage_snapshots`
- `tenant_support_flags`

Snapshots will matter when this grows and needs to stay fast.

---

## 9) Updated Implementation Plan

### Phase 1: Better Facility List

Deliverables:

- facility manager index page
- searchable/filterable tenant list
- onboarding and subscription columns
- user count and branch count
- switch-into-tenant action

Status:

- completed

### Phase 2: Facility Detail Page

Deliverables:

- tenant overview
- branch summary
- user summary
- subscription summary
- last activity indicators

Status:

- completed

### Phase 3: Operational And Support Controls

Deliverables:

- onboarding controls
- subscription actions
- support notes
- health checks

Status:

- partially completed

Done:

- onboarding controls
- subscription actions
- support notes

Pending:

- health checks

### Phase 4: Usage Analytics

Deliverables:

- activity charts
- 7-day / 30-day usage summaries
- module usage trends
- inactive facility detection

Status:

- partially completed

Done:

- 7-day / 30-day usage summaries
- recent activity feed

Pending:

- charts
- module usage trends
- inactive detection

### Phase 5: Advanced Support Tooling

Deliverables:

- safer impersonation / switch workflow
- configuration audits
- missing setup warnings
- exports

Status:

- not started

---

## 10) Recommended Next Slice

The next most valuable additions are:

1. facility health checks on dashboard and tenant overview
2. tenant configuration audit page
3. activity trend charts
4. inactive / low-usage facility detection
5. support flags and escalations

---

## 11) Bottom Line

We do not need a second app.

The right direction is to keep growing **Facility Manager** inside this same app until it becomes the real support/admin console for:

- facilities
- subscriptions
- onboarding
- branches
- users
- activity
- internal support operations

That gives the depth you want without doubling infrastructure and maintenance.
