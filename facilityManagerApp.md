# Facility Manager Panel Plan

**Date:** April 11, 2026  
**Goal:** Answer how to manage SaaS and tenant/facility operations in a detailed way without creating a completely separate application.

---

## 1) Short Answer

Yes, you can make this detailed **without building another app**.

The cleanest approach is to build a **support-only / platform-admin module inside the same codebase** and treat it as a separate panel area in the navigation and permissions, rather than a separate deployed application.

That gives you:

- one codebase
- one authentication system
- shared models and business rules
- less duplicated infrastructure
- a much richer tenant-management experience than the current simple facility switcher

So instead of a second app, I recommend a **Facility Manager panel** or **Platform Manager panel** inside this app.

---

## 2) Why The Current Facility Switcher Feels Too Small

The current facility switcher is useful for quick workspace switching, but it is not a true management console.

It does not feel like a full management tool because it is mostly optimized for:

- finding a tenant
- jumping into a tenant
- doing a few support actions

What you are describing is broader. You want to manage:

- all onboarded facilities
- their onboarding state
- subscription state
- branch structure
- user counts
- staff counts
- activity and usage
- health/status indicators
- operational flags and exceptions

That needs a more deliberate management panel.

---

## 3) Recommended Direction

Create a new internal module called something like:

- `Facility Manager`
- `Platform Manager`
- `Tenant Manager`

I would personally recommend:

- `Facility Manager` if the language should feel hospital-facing
- `Platform Manager` if the language should feel SaaS/internal-facing

Since your users think in terms of facilities and branches, `Facility Manager` is probably the clearer name.

---

## 4) Recommended Access Model

This module should be visible only to:

- support users
- super admins
- selected platform admins

It should not be part of normal hospital-admin navigation.

Recommended permission family:

- `tenants.view`
- `tenants.manage`
- `tenants.switch`
- `tenants.manage_subscription`
- `tenants.manage_onboarding`
- `tenants.view_usage`

---

## 5) Recommended IA For The Facility Manager Panel

### Main Pages

- `facility-manager/dashboard`
- `facility-manager/facilities`
- `facility-manager/facilities/{tenant}`
- `facility-manager/facilities/{tenant}/branches`
- `facility-manager/facilities/{tenant}/users`
- `facility-manager/facilities/{tenant}/subscriptions`
- `facility-manager/facilities/{tenant}/activity`
- `facility-manager/facilities/{tenant}/support-tools`

### Why This Works

It gives you:

- one global overview
- one searchable facilities list
- one deep detail page per tenant
- focused tabs for the most important management areas

---

## 6) What The Facilities List Page Should Show

The facilities index should be a proper management table, not just a switcher list.

Recommended columns:

- facility name
- tenant code / slug
- onboarding status
- subscription status
- current package
- active branch count
- active user count
- active patient count
- last activity date
- trial end / renewal date
- health flags

Recommended filters:

- onboarding status
- subscription status
- package
- active/inactive
- branch count
- last activity date
- trial ending soon
- overdue subscription

Recommended quick actions:

- view details
- switch into facility
- activate subscription
- mark past due
- reopen onboarding
- suspend / reactivate

---

## 7) What The Facility Detail Page Should Show

This page should become the real control center for each tenant.

### Suggested Sections

#### Overview

- facility name
- tenant id / slug
- created date
- onboarding state
- subscription state
- package
- branch count
- user count
- patient count
- current active branch count
- recent usage summary

#### Branches

- all branches
- branch status
- branch users
- branch activity
- which branch is primary

#### Users

- total users
- active users
- verified users
- users by role
- last login
- disabled users

#### Subscription

- package
- billing state
- trial end
- activation history
- past-due state
- manual overrides

#### Activity

- visits created this week
- consultations this week
- lab requests this week
- prescriptions this week
- inventory transactions this week
- latest sign-in / latest operational action

#### Support Tools

- switch into tenant
- complete onboarding
- reopen onboarding
- reset branch selection
- trigger support notes / flags

---

## 8) What “Detailed” Should Mean In Practice

To make the panel truly useful, each tenant should show both **business state** and **operational state**.

### Business State

- subscription package
- billing status
- trial status
- overdue status
- activation date
- payment issues

### Operational State

- how many branches exist
- how many users are active
- what modules are being used
- whether onboarding is incomplete
- whether the facility has recent activity
- whether configuration is missing

This is what will make the panel feel real, not just administrative.

---

## 9) Useful Metrics Per Facility

Here are the most useful metrics to show in a tenant detail page:

- total users
- active users in last 7 / 30 days
- total branches
- active branches
- total patients
- total visits
- visits in last 30 days
- consultations in last 30 days
- lab requests in last 30 days
- prescriptions in last 30 days
- stock movements in last 30 days
- most recently active user
- last activity timestamp

These tell you whether the facility is alive, underused, inactive, or stuck.

---

## 10) Important Support Features Worth Adding

### 10.1 Onboarding Control

Support staff should be able to:

- view onboarding stage
- mark onboarding complete
- reopen onboarding
- see missing onboarding steps

### 10.2 Subscription Operations

Support staff should be able to:

- assign package
- activate subscription
- mark past due
- pause access if needed
- view subscription history

### 10.3 Facility Health Checks

Add simple health indicators like:

- no active branch
- no active users
- no clinicians configured
- no inventory locations
- no lab catalog
- no facility services
- onboarding incomplete

This gives fast operational visibility.

### 10.4 Impersonation / Workspace Switching

Keep the current switcher capability, but place it as one action inside a richer tenant detail page.

### 10.5 Support Notes

Add internal-only notes for each facility:

- implementation notes
- billing notes
- support issues
- special configuration reminders

---

## 11) Recommended Technical Approach

### Keep It In The Same App

I recommend:

- same Laravel backend
- same database
- same frontend app
- separate support-only routes and pages
- strict permission gates

This gives you a “different panel” experience without a second application.

### Why This Is Better Than Another App Right Now

- less duplication
- shared auth/session
- shared models
- easier support workflows
- fewer sync problems
- faster implementation

---

## 12) Suggested Route Structure

Recommended route prefix:

- `/facility-manager`

Suggested routes:

- `/facility-manager/dashboard`
- `/facility-manager/facilities`
- `/facility-manager/facilities/{tenant}`
- `/facility-manager/facilities/{tenant}/branches`
- `/facility-manager/facilities/{tenant}/users`
- `/facility-manager/facilities/{tenant}/subscriptions`
- `/facility-manager/facilities/{tenant}/activity`

This makes it feel like its own panel, while still living inside the same app.

---

## 13) Suggested Sidebar Placement

Do **not** put this under ordinary hospital Administration.

Recommended:

- a separate support-only sidebar item called `Facility Manager`

Visible only to:

- support
- super admin
- platform admin

That way:

- normal facility users never see it
- platform users get a dedicated management area

---

## 14) Suggested Data Sources

You can build most of this from tables you already have:

- `tenants`
- `facility_branches`
- `users`
- `staff`
- `patients`
- `patient_visits`
- `doctor_consultations`
- `lab_requests`
- `prescriptions`
- `subscriptions`

Add small support tables later if needed:

- `tenant_support_notes`
- `tenant_health_snapshots`
- `tenant_usage_snapshots`

Snapshots are useful if you want the panel to remain fast at scale.

---

## 15) Recommended Implementation Plan

### Phase 1: Better Facility List

Deliverables:

- facility manager index page
- searchable/filterable tenant list
- onboarding and subscription columns
- user count and branch count
- switch-into-tenant action

### Phase 2: Facility Detail Page

Deliverables:

- tenant overview
- branch summary
- user summary
- subscription summary
- last activity indicators

### Phase 3: Operational And Support Controls

Deliverables:

- onboarding controls
- subscription actions
- support notes
- health checks

### Phase 4: Usage Analytics

Deliverables:

- activity charts
- 7-day / 30-day usage summaries
- module usage trends
- inactive facility detection

### Phase 5: Advanced Support Tooling

Deliverables:

- safer impersonation / switch workflow
- configuration audits
- missing setup warnings
- exports

---

## 16) Recommended First Version

The first useful version should include:

- facility list
- status filters
- subscription status
- onboarding status
- user counts
- branch counts
- facility detail page
- switch-into-facility action

That alone would already feel far more complete than the current switcher.

---

## 17) Bottom Line

You do not need a second app to get a rich tenant-management experience.

The best path is:

- build a **Facility Manager** panel inside this same app
- keep it support-only
- give it its own route prefix and navigation identity
- make it detailed enough to manage facilities, subscriptions, onboarding, users, branches, and activity from one place

That will give you the depth you want without the maintenance cost of a separate application.
