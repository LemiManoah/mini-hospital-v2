# SaaS Layer Plan

**Date:** March 17, 2026  
**Goal:** Take the project from developer-driven tenant setup to a complete self-service SaaS onboarding and subscription flow.

---

## 0) Implementation Checklist

### Milestone 1: Public Entry + Workspace Signup

- [x] Replace the default public welcome page with a SaaS landing page
- [x] Add `Create Workspace` public signup route
- [x] Build workspace registration form
- [x] Provision tenant, first admin staff record, and owner user transactionally
- [x] Log new owner in and redirect to onboarding
- [x] Add a first onboarding checkpoint page

### Milestone 2: Guided Onboarding Wizard

- [x] Capture hospital profile details in the wizard
- [x] Create the primary branch during onboarding
- [x] Persist onboarding progress step by step
- [x] Add department bootstrap
- [x] Add first operational staff bootstrap or invitations

### Milestone 3: Subscription Activation

- [x] Connect package selection to subscription records
- [x] Add trial and pending activation states
- [ ] Integrate checkout
- [ ] Handle payment success and failure callbacks

### Milestone 4: SaaS Admin Operations

- [ ] Add internal tenant lifecycle dashboard
- [ ] Show onboarding state to support/admin users
- [ ] Show subscription state to support/admin users
- [ ] Add controlled support actions for activation and recovery

---

## 1) Objective

Phase 0 is about making the application usable as a real multi-tenant SaaS product, not just a hospital system with manually seeded tenants.

The end state should allow a new hospital to:

- discover the product from a public landing page
- create an account
- create a tenant/hospital workspace
- choose a subscription package
- complete initial setup
- create the first admin and core branch data
- land inside a ready-to-use workspace

It should also allow internal support/admin staff to:

- review tenants
- switch into tenant context safely
- see onboarding/subscription state
- assist with activation, payment, and setup issues

---

## 2) Current Starting Point

Based on the current codebase:

### Already Available

- authentication
- email verification
- password reset
- profile and two-factor settings
- `Tenant` model
- `SubscriptionPackage` model and CRUD
- support tenant switching
- branch switching
- tenant-aware and branch-aware application structure

### Partially Available

- tenant context exists technically, but tenant creation is still developer/admin driven
- subscription packages exist, but checkout and activation do not
- support workflows exist, but tenant lifecycle management does not

### Missing For True SaaS

- public marketing site
- public signup flow for a new tenant
- tenant provisioning workflow
- onboarding wizard
- billing/subscription checkout
- subscription status enforcement
- onboarding progress tracking
- tenant admin bootstrap flow

---

## 3) Phase 0 Scope

This plan covers the full SaaS layer needed before calling Phase 0 complete.

### Included

- public SaaS entry experience
- self-service account and tenant registration
- tenant provisioning
- onboarding wizard
- subscription selection and activation
- internal support/admin tenant management basics
- SaaS state tracking

### Excluded

- deeper hospital operations like visits, triage, billing for patients, or inventory
- advanced finance reporting beyond subscription lifecycle
- white-label custom domains in the first pass unless the project decides to include them in MVP

---

## 4) Desired End-to-End User Journey

### Public User Journey

1. Visitor lands on the marketing site.
2. Visitor reviews packages/features.
3. Visitor clicks `Start free trial` or `Create hospital workspace`.
4. Visitor creates a user account.
5. Visitor enters hospital details.
6. System provisions a tenant and initial owner/admin context.
7. User is redirected into a guided onboarding wizard.
8. User sets up the first branch, departments, and core staff/admin account details.
9. User selects a subscription package.
10. User completes checkout or begins a trial.
11. Tenant becomes active.
12. User lands on modules/dashboard with the correct tenant and branch selected.

### Internal Support Journey

1. Support user logs in.
2. Support user opens tenant management.
3. Support user reviews tenant onboarding/subscription state.
4. Support user can switch into tenant context.
5. Support user can assist with activation, failed onboarding, or branch setup issues.

---

## 5) Product Areas To Build

## 5.1 Public Marketing Surface

### Purpose

Give the product a public entry point instead of the default Laravel-style welcome page.

### Deliverables

- new public landing page
- package/pricing section powered by `subscription_packages`
- clear CTAs for signup/trial/demo
- feature summary for hospital admins
- contact/support CTA

### Notes

- this can replace or heavily redesign `welcome.tsx`
- package cards should use live package data where practical

## 5.2 Self-Service Signup

### Purpose

Allow a new hospital owner to create an account and start tenant onboarding.

### Deliverables

- signup page for new hospital owners
- validation for email, password, and organization/hospital details
- first-user registration flow
- email verification handoff

### Data To Capture

- account owner name
- owner email
- password
- hospital/tenant name
- country
- optionally phone and business contact

## 5.3 Tenant Provisioning

### Purpose

Create the initial tenant and attach the first owner/admin cleanly.

### Deliverables

- `RegisterTenant` or similar application action
- transactional tenant provisioning service
- tenant creation
- first admin user creation
- owner staff profile creation if required by the current domain model
- initial subscription state record
- initial onboarding state record

### Important Rule

Provisioning must be transactional. If one part fails, no half-created tenant should remain.

## 5.4 Onboarding Wizard

### Purpose

Guide first-time setup instead of dropping the user directly into a blank workspace.

### Recommended Steps

1. Hospital profile
2. Primary branch setup
3. Departments
4. First staff/admin setup
5. Package selection
6. Review and finish

### Deliverables

- onboarding progress persistence
- resumable wizard state
- completion check before entering the main app
- redirect middleware for incomplete onboarding

### Suggested Onboarding State Model

Track:

- `started_at`
- `completed_at`
- `current_step`
- `status`
- flags like `branch_completed`, `departments_completed`, `staff_completed`, `subscription_completed`

## 5.5 Subscription & Checkout

### Purpose

Convert package selection into an actual SaaS billing lifecycle.

### MVP Deliverables

- package selection step
- trial or pending activation state
- checkout integration
- payment success callback
- payment failure handling
- subscription activation/update

### Gateway Decision

Choose one:

- Flutterwave
- Stripe

### Subscription States To Support

- `trial`
- `pending_payment`
- `active`
- `past_due`
- `suspended`
- `cancelled`

### Notes

- if payment integration is deferred slightly, an MVP can still support trial mode plus manual activation by support
- even in trial mode, the subscription lifecycle model should still be built correctly

## 5.6 Tenant Admin Bootstrap

### Purpose

Ensure the first tenant user has a usable workspace immediately after onboarding.

### Deliverables

- assign admin/super-admin role inside the tenant
- ensure an active branch is selected
- create minimum required records for the tenant to enter the app safely
- set onboarding complete only after these conditions are met

## 5.7 SaaS Administration Console

### Purpose

Give internal staff visibility into tenant lifecycle and activation state.

### Deliverables

- tenant list page
- tenant detail page
- onboarding status view
- subscription status view
- support actions

### Support Actions

- switch to tenant
- mark onboarding complete or reset step
- activate/suspend subscription
- resend onboarding invitation or verification email

## 5.8 Guard Rails & Middleware

### Purpose

Prevent users from entering the product in invalid states.

### Rules Needed

- unauthenticated users see public marketing pages only
- authenticated but unverified users are prompted to verify
- authenticated users with incomplete onboarding are redirected to onboarding
- tenants without active subscription or valid trial are redirected to billing/activation state
- support users may bypass some restrictions for support operations

---

## 6) Data Model Additions

The exact schema can vary, but the SaaS layer likely needs these entities or equivalents.

## 6.1 Tenant Onboarding

- `tenant_onboardings`
- fields for status, current step, started/completed timestamps, and completion flags

## 6.2 Tenant Subscription

- `tenant_subscriptions`
- fields for package, billing status, gateway reference, trial dates, activation dates, renewal dates

## 6.3 Subscription Transactions

- `subscription_payments` or `subscription_transactions`
- fields for amount, reference, gateway payload, status, paid_at

## 6.4 Tenant Owner / Invitation Support

- optional `tenant_invitations` if inviting the first admin or additional admins becomes part of onboarding

---

## 7) Recommended Build Order

## Milestone 1: Replace Public Welcome With Real SaaS Entry

- redesign public landing page
- expose package cards
- add CTA to signup

## Milestone 2: Self-Service Signup + Tenant Provisioning

- build public registration flow
- create tenant and first owner transactionally
- log user in and redirect into onboarding

## Milestone 3: Onboarding Wizard

- add onboarding state storage
- implement wizard steps
- create first branch and setup data
- enforce onboarding redirect until complete

## Milestone 4: Subscription Selection + Trial State

- package selection during onboarding
- trial activation or pending activation state
- subscription-aware middleware

## Milestone 5: Payment Gateway Integration

- implement checkout
- webhook/callback handling
- activate subscription on success
- failed-payment recovery states

## Milestone 6: Internal SaaS Admin Console

- tenant list/detail pages
- onboarding state visibility
- subscription state visibility
- support/admin actions

## Milestone 7: Hardening

- end-to-end tests
- email flows
- error recovery
- audit logging for tenant switching and activation

---

## 8) Technical Plan By Layer

## Backend

- create onboarding and subscription tables
- create tenant registration/provisioning action
- create onboarding update actions
- create subscription activation/update services
- add middleware for onboarding and subscription access control
- add gateway webhook/callback endpoints

## Frontend

- redesign public welcome/landing page
- build signup flow
- build onboarding wizard pages
- build package selection UI
- build subscription activation and payment result screens
- build support/admin SaaS pages

## Routing

- public routes for marketing, pricing, signup
- authenticated onboarding routes
- billing/subscription routes
- support/admin SaaS routes

## Security

- enforce verification
- enforce onboarding completion
- enforce subscription state
- keep support-only context switching protected and logged

---

## 9) Risks And Decisions To Resolve Early

## Product Decisions

- free trial or no free trial
- single package per tenant or upgrade/downgrade support from day one
- whether onboarding requires payment before entering the app
- whether custom domains are MVP or later

## Technical Decisions

- Stripe vs Flutterwave
- whether the first admin is represented as both `users` and `staff` during onboarding
- whether branch creation is mandatory before any app access
- whether support users can bypass subscription/onboarding guards

---

## 10) Definition Of Done For Phase 0

Phase 0 should be considered complete when all of the following are true:

- a public visitor can sign up without developer intervention
- signup creates a tenant and first owner correctly
- first login enters a guided onboarding wizard
- onboarding creates the initial branch and required bootstrap data
- a subscription package can be selected and recorded
- payment or trial activation updates tenant access state correctly
- incomplete onboarding and inactive subscription states are enforced by middleware
- support/admin users can review and assist tenant onboarding
- core Phase 0 flows have automated test coverage

---

## 11) Suggested Immediate Next Build Slice

To start Phase 0 without boiling the ocean, build this first slice:

### Slice 1

- replace the current public welcome page with a product landing page
- add a `Create Workspace` CTA
- build public signup form
- implement transactional tenant provisioning
- log the new owner in
- redirect to onboarding step 1

### Why Start Here

- it creates visible SaaS progress immediately
- it establishes the core tenant creation path
- every later Phase 0 feature depends on this slice

---

## 12) Bottom Line

The project already has the internal building blocks for SaaS, especially tenant context, subscription packages, auth, and support switching. What it lacks is the product flow around those pieces. Phase 0 should now focus on turning those internal primitives into a complete external customer journey: public discovery, self-service signup, onboarding, subscription activation, and support visibility.
