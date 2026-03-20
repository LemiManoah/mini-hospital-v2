# Phase 2: Multi-Tenant Architecture & Security

**Date:** March 20, 2026  
**Goal:** Move Phase 2 from "architecture exists" to "tenant and branch operations are fully manageable, enforced, and testable."

---

## 1) Current Read

Phase 2 is not about introducing tenancy for the first time. The codebase already has the core primitives:

- tenant-aware models and traits
- branch-aware context handling
- support facility switching
- branch switching for operational users
- branch isolation tests
- permission and policy enforcement for the main app surface

So the real remaining Phase 2 work is:

- exposing tenant and branch administration more explicitly
- tightening context guard rails
- making branch/tenant state more visible and manageable
- improving verification around cross-tenant and cross-branch access

---

## 2) What Counts As Phase 2 Complete

Phase 2 should be considered complete when all of the following are true:

- tenant context is always resolved predictably for authenticated users
- branch context is always resolved predictably for tenant users who need operational access
- users cannot read or mutate records outside their tenant
- users cannot read or mutate records outside their authorized branch scope where branch scoping applies
- support users can switch tenant context safely and intentionally
- tenant and branch administration have usable internal management surfaces
- branch-sensitive modules behave correctly when no branch is selected
- automated tests cover the critical tenant and branch isolation rules

---

## 3) Concrete Build Slices

## Slice 2.1: Tenant Context Hardening + Branch Administration Surface

### Objective

Turn the existing tenant/branch architecture into a complete operational layer by adding missing admin surfaces and tightening invalid-state handling.

### Deliverables

- internal tenant administration page review and cleanup
- facility branch administration CRUD or equivalent management surface
- clearer empty/no-branch-selected handling for operational modules
- stricter guard rails around active tenant and active branch assumptions
- expanded feature coverage for tenant switching, branch switching, and isolation

### Why This Slice First

- it finishes the architecture you already have instead of starting a new domain
- it reduces hidden multi-tenant risk before billing, inventory, and IPD add more complexity
- it gives support/admin users proper operational visibility into the tenancy model

### Status

- completed in implementation shape
- branch administration CRUD exists
- active branch middleware is applied to the operational route group
- invalid active-branch recovery is standardized through branch switcher and branch admin access
- branch-sensitive workflow pages and major admin surfaces now enforce active branch context

## Slice 2.2: Branch-Owned Reference & Operational Surface Completion

### Objective

Finish Phase 2 by bringing the remaining branch-owned supporting modules into the same active-branch rule set already used by the main workflow pages.

### Deliverables

- branch-scoped supporting reference modules such as appointment categories where branch ownership exists
- cleanup of any remaining tenant-wide queries on branch-owned admin pages
- document updates that clearly mark Phase 2.1 complete and identify the exact remaining Phase 2 work

### Definition Of Done

- branch-owned admin/reference records cannot be listed, edited, or mutated from another active branch
- branch-linked dropdowns and forms only offer active-branch selections
- the next milestone after Phase 2 is obvious from the docs without rereading code

---

## 4) Scope Breakdown

## 4.1 Tenant Administration Surface

### Current State

- support facility switching exists
- support can inspect tenant lifecycle state
- support actions exist for onboarding/subscription recovery

### Remaining Work

- review whether tenant detail shows enough structural data:
  - branch count
  - onboarding state
  - subscription state
  - tenant status
  - key bootstrap completeness markers
- tighten support-only visibility and action affordances
- ensure support actions are consistently policy-backed and permission-backed

### Definition Of Done

- support users can inspect a tenant cleanly without direct DB assumptions
- tenant state is understandable from the UI
- tenant actions are explicit, limited, and audited through tests

## 4.2 Facility Branch Administration

### Current State

- branches exist in the model layer
- branch switching exists
- branch-aware access exists
- branch CRUD is not yet exposed as a complete admin module

### Remaining Work

- build a branch administration module for tenant admins
- allow listing branches for the active tenant
- allow creating additional branches
- allow editing branch metadata
- allow activating/deactivating branches where allowed
- protect main-branch invariants if your rules require them

### Suggested Data Surface

- branch name
- branch code
- currency
- status
- main branch flag
- has store flag
- contact details if available in the model

### Definition Of Done

- tenant admins can manage branches without support intervention
- the app still prevents unsafe branch operations
- branch switching reflects newly managed branches correctly

## 4.3 Branch Guard Rails

### Current State

- branch switching is present
- active branch middleware exists
- some modules already depend on branch context

### Remaining Work

- identify every operational page that requires an active branch
- standardize the redirect/empty-state behavior when no branch is selected
- ensure support users bypass only where intended
- ensure non-support users cannot accidentally operate in a null-branch context when a branch is required

### Definition Of Done

- no operational workflow silently runs with missing branch context
- users are either redirected to branch switcher or shown a deliberate blocked state

## 4.4 Tenant And Branch Isolation Testing

### Current State

- branch isolation coverage exists
- permission enforcement coverage exists

### Remaining Work

- add feature tests for:
  - branch CRUD access by tenant admin vs unauthorized user
  - branch switch refusal for inaccessible branches
  - tenant-bound records not leaking across tenants
  - operational module redirects when branch context is missing
  - support tenant switching preserving or clearing branch state correctly

### Definition Of Done

- the most dangerous tenant/branch boundary cases are feature-tested

---

## 5) Recommended Implementation Order

1. audit current tenant and branch routes/controllers against the desired Phase 2 surface
2. build facility branch admin pages and routes
3. standardize missing-branch handling across operational modules
4. add feature coverage for tenant/branch isolation and switching behavior
5. update documentation to mark Phase 2 complete once the surface and tests align

---

## 6) Suggested Immediate Next Task

The current next implementation task is:

### Finish branch isolation on the remaining branch-owned supporting modules

That means:

- harden appointment categories and any similar branch-owned references
- scan for remaining tenant-wide admin queries that should respect active branch
- update the docs immediately after each finished slice so the next target stays visible

---

## 7) Bottom Line

Phase 2 is now well past the architectural stage. Slice 2.1 is effectively implemented, and the remaining work is consistency cleanup on the last branch-owned surfaces plus verification. Once Slice 2.2 is done, the project can move forward with far less tenant/branch ambiguity.
