# Phase 3: Hospital Infrastructure & Service Catalogs

**Date:** March 23, 2026  
**Goal:** Close the remaining infrastructure and catalog gaps in Phase 3 without duplicating the billing foundation that has already started elsewhere in the codebase.

---

## 1) Current Read

Phase 3 is already partially implemented in the live app. The current codebase clearly has:

- clinic CRUD
- doctor schedule CRUD
- doctor schedule exception CRUD
- drug CRUD
- facility service CRUD
- lab test catalog data model used by consultation order entry

There is also an important project reality change since the older planning notes:

- early billing foundation work has already started through `visit_charges`, `visit_billings`, payment capture, insurance package prices, and charge-sync actions for lab requests and facility service orders

That means Phase 3 should now focus on finishing the missing infrastructure and catalog surface, not on inventing a second pricing architecture that conflicts with the newer billing work.

---

## 2) What Counts As Phase 3 Complete

Phase 3 should be considered complete when all of the following are true:

- operational service catalogs used by clinicians have proper admin/maintenance surfaces
- catalog records that affect ordering and pricing are manageable through the app, not only via seeders or direct database work
- branch-owned infrastructure entities are modeled and manageable where branch scoping matters
- minimal inpatient infrastructure primitives exist for future IPD work
- pricing sources for billable catalog items are documented and consistent with the current billing implementation
- automated tests cover the critical catalog and infrastructure guard rails

---

## 3) Concrete Build Slices

## Slice 3.1: Lab Test Catalog Administration

### Objective

Promote lab tests from a model-only/order-only dependency into a first-class managed catalog.

### Why This Slice First

- doctor consultation already depends on `LabTestCatalog`
- lab request charging already depends on test pricing
- permission tests already assume the model exists, but there is no visible CRUD surface in the route layer

### Deliverables

- `LabTestCatalogController` with index/create/edit/update/delete flows
- request validation and permission enforcement
- Inertia pages matching the existing admin CRUD patterns
- searchable catalog fields such as:
  - test code
  - test name
  - category
  - department
  - base price
  - turnaround time
  - fasting requirement
  - active/inactive status
- safe delete or disable rules when a test is already referenced by orders

### Definition Of Done

- tenant users can manage lab test definitions from the UI
- consultation ordering reads from the same managed catalog
- tests cover CRUD access and obvious invalid-state cases

## Slice 3.2: Pricing & Catalog Alignment

### Objective

Align Phase 3 catalog data with the billing foundation that already exists, instead of introducing a conflicting generic charge-master layer too early.

### Current Reality

- lab tests have `base_price`
- facility services have `selling_price`
- insurance overrides exist in `insurance_package_prices`
- visit charges are already generated from some order actions

### Recommended Direction

- treat catalog base prices plus insurance package overrides as the current source of truth
- defer a standalone `charge_masters` table unless a concrete duplication problem appears during later billing work
- document which billable item types are currently priceable and which are not yet operational

### Deliverables

- document the active pricing model for:
  - lab tests
  - facility services
  - drugs
  - future bed-day charging
- identify gaps where a billable item can be ordered but not priced cleanly
- clean up any mismatched naming or assumptions between Phase 3 docs and Phase 8 billing docs

### Definition Of Done

- the team can answer "where does this item’s price come from?" for each active catalog type
- Phase 3 docs no longer point toward a pricing design that the app has already outgrown

## Slice 3.3: Wards & Beds Foundations

### Objective

Add the minimum inpatient infrastructure primitives needed before any serious IPD workflow can exist.

### Why This Belongs In Phase 3

Wards and beds are infrastructure, not full IPD workflow. They should exist before admissions, nursing care, and bed assignment flows.

### Deliverables

- `wards` table, model, policies, and admin CRUD
- `beds` table, model, policies, and admin CRUD
- branch ownership for wards and beds
- core fields such as:
  - ward name
  - ward code
  - ward type
  - sex restriction or occupancy policy if needed
  - bed number/code
  - bed status
  - isolation/critical-care flags if needed
- active/inactive handling instead of hard deletion where occupancy history could matter later

### Suggested First Constraints

- wards belong to tenant and branch
- beds belong to a ward and branch
- duplicate ward codes should be blocked within the same branch
- duplicate bed numbers should be blocked within the same ward

### Definition Of Done

- tenant admins can manage wards and beds through the app
- data shape is ready for later admission and bed-allocation work
- branch isolation is enforced in both data access and form options

## Slice 3.4: Verification, Permissions, and Documentation

### Objective

Finish Phase 3 with confidence instead of leaving the new admin surface lightly verified.

### Deliverables

- feature tests for lab test catalog CRUD
- feature tests for ward and bed CRUD
- permission tests for unauthorized access
- branch isolation tests for wards and beds
- implementation doc updates marking Phase 3 complete or clearly partial

### Definition Of Done

- critical Phase 3 boundaries are covered by automated tests
- docs accurately describe what is now implemented and what remains deferred

---

## 4) Recommended Implementation Order

1. build `phase3.md` and align the docs around the actual current state
2. add lab test catalog administration because consultation already depends on it
3. document the current pricing model and explicitly defer or confirm `charge_masters`
4. add wards and beds as the minimum inpatient infrastructure layer
5. add tests for catalog access, permissions, and branch isolation
6. update `implementation.md` after the code lands to mark Phase 3 complete or restate the remaining gaps

---

## 5) Suggested Immediate Implementation Task

The first code slice for Phase 3 should be:

### Build Lab Test Catalog Administration

That means:

- add the controller, requests, routes, and pages
- match the CRUD and permission pattern already used by drugs and facility services
- keep the catalog tenant-safe
- decide whether lab tests should be tenant-wide or branch-filtered and document that choice in the implementation

This is the highest-leverage Phase 3 gap because the consultation workflow already depends on lab tests operationally.

---

## 6) Bottom Line

Phase 3 is not a greenfield phase anymore. Most outpatient service catalogs already exist, and early billing foundations have started. The cleanest way to finish Phase 3 is to:

- close the missing lab catalog admin surface
- clarify the real pricing model instead of reviving an outdated charge-master idea
- add wards and beds as true infrastructure foundations
- verify the new surface with permissions and branch-isolation tests
