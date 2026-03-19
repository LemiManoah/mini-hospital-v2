# Roles & Permissions Status

**Date:** March 18, 2026  
**Scope:** Phase 1.5 review for roles, permissions, and authorization completeness.

---

## 1) Bottom Line

Phase 1.5 is **partially complete**.

The project already has the core roles-and-permissions foundation:

- Spatie permissions infrastructure is present
- permissions are seeded
- roles are seeded
- role CRUD exists
- user CRUD exists
- role-to-permission assignment exists
- browser coverage exists for the role permission UI

What is still missing is the part that makes the system truly secure and complete:

- consistent backend authorization enforcement on routes and controller actions
- module-by-module permission gating beyond UI visibility
- a more explicit permission matrix for operational workflows
- feature tests that prove unauthorized users are actually blocked

So the current system is best described as:

- **authorization-capable**
- **admin-manageable**
- **not yet comprehensively enforced**

---

## 2) What Is Clearly Done

### 2.1 Permission Infrastructure

The application already uses Spatie permission models through:

- `App\Models\Role`
- `App\Models\Permission`

This means the project has a standard, extensible permission framework rather than a custom one-off implementation.

### 2.2 Permission And Role Seeding

The permission catalog already exists in `database/seeders/PermissionSeeder.php`.

This includes permission groups for:

- dashboard
- settings
- countries
- addresses
- currencies
- subscription packages
- allergens
- roles
- permissions
- users
- patients
- visits
- appointments
- doctor schedules
- doctor schedule exceptions
- appointment categories
- appointment modes
- triage
- consultations
- tenants
- facility branches
- staff positions
- staff
- departments
- clinics
- units
- drugs
- facility services
- insurance companies
- insurance packages
- insurance claims
- insurance payments

Seeded roles already include:

- `super_admin`
- `admin`
- `doctor`
- `nurse`
- `lab_technician`
- `pharmacist`
- `receptionist`
- `accountant`
- `cashier`
- `human_resource`
- `store_keeper`

### 2.3 Role CRUD

The application already supports:

- listing roles
- creating roles
- editing roles
- deleting roles
- assigning permission sets to roles

This is implemented through the role controller and role pages.

### 2.4 User CRUD With Role Assignment

The application already supports:

- listing users
- creating users
- editing users
- deleting users
- attaching roles to users

This is enough to manage access assignments operationally.

### 2.5 UI-Level Permission Awareness

The app already shows evidence of permission-aware UI behavior, especially in module visibility and role management pages.

That is useful, but UI hiding alone is not sufficient authorization.

### 2.6 Initial Test Coverage

There is already browser coverage for the role-permission UI.

This is a good start because it proves the permissions interface works, but it does not yet prove access control is enforced across the product.

---

## 3) What Is Partial

### 3.1 Route And Controller Enforcement

This is the biggest gap.

The current review found strong evidence of:

- permission data existing
- role assignment existing
- permissions being displayed in the UI

But it did **not** find broad, systematic evidence of:

- `permission:` middleware on protected routes
- `can:` middleware on route groups
- controller authorization checks enforced across modules
- policy-based permission checks consistently guarding sensitive actions

This means the backend enforcement story still appears incomplete.

### 3.2 Permission Matrix Depth

The permission list is broad, but the actual operational model still looks shallow in places.

Examples of likely future refinement:

- different staff should not all share identical patient/visit capabilities
- billing roles will eventually need stricter financial permissions
- clinical order creation, update, completion, and cancellation may need separate permissions
- support and tenant-admin powers should remain clearly separated

### 3.3 Workflow-Specific Authorization

Some workflows are clinically or financially sensitive and likely need stronger distinction than simple CRUD groupings.

Examples:

- appointment confirmation vs appointment creation
- triage creation vs triage editing
- consultation authoring vs consultation completion
- subscription activation vs subscription viewing
- support switching vs tenant self-management

The current permission structure has started this in some places, but not yet as a comprehensive system standard.

### 3.4 Testing Coverage

Current testing proves the role UI behaves, but Phase 1.5 completion needs more than that.

There should also be tests proving that:

- authorized users can access protected actions
- unauthorized users receive a forbidden response or redirect
- support-only actions stay support-only
- tenant users cannot access admin/support-only SaaS controls

---

## 4) What Is Not Done Yet

The following work still appears necessary before calling Phase 1.5 complete in a strong sense.

### 4.1 Backend Authorization Hardening

- add route middleware or policy enforcement for protected modules
- apply permissions consistently to create, view, update, delete, and special workflow actions
- ensure backend checks exist even when UI already hides buttons

### 4.2 Authorization Standards

- decide when to use route middleware vs policies vs controller checks
- standardize permission naming for non-CRUD workflow actions
- document the intended permission model for each module

### 4.3 Sensitive Workflow Separation

- define who can complete clinical actions vs only view them
- define who can manage tenant lifecycle vs only inspect it
- define who can activate subscriptions, reopen onboarding, or switch tenant context
- define who can cancel, reschedule, confirm, or no-show appointments

### 4.4 Negative-Path Test Coverage

- add feature tests for forbidden access
- add tests for role-limited modules
- add tests for support-only endpoints
- add tests for tenant-sensitive SaaS operations

---

## 5) Risks If Left As-Is

If the system stops here, the main risks are:

- users may still reach protected backend routes directly even if the UI hides those links
- permission assignments may give a false sense of security
- future modules may copy inconsistent authorization patterns
- SaaS admin and support operations may become harder to secure as the product grows
- auditing access problems later will be more expensive than enforcing patterns now

---

## 6) Recommended Definition Of Done For Phase 1.5

Phase 1.5 should be considered complete when all of the following are true:

- every protected module has backend authorization enforcement
- routes or controller actions are guarded consistently
- sensitive workflow actions have explicit permissions where needed
- UI visibility matches backend authorization
- unauthorized access paths are covered by feature tests
- support/admin-only actions are explicitly protected
- the permission model is documented clearly enough for future modules to follow the same pattern

---

## 7) Recommended Next Build Slice

The most valuable next slice for permissions is:

### Slice 1: Enforce Existing Permissions On The Backend

- identify the main protected modules already using permission names
- apply route middleware or policy checks to those modules
- start with:
  - users
  - roles
  - patients
  - visits
  - appointments
  - triage
  - consultations
  - subscription/support SaaS actions
- add feature tests for at least one allowed and one forbidden role per module

### Why This Slice First

- it turns the existing permission catalog into real security
- it reduces the gap between UI behavior and backend behavior
- it establishes a reusable authorization pattern before more modules are added

---

## 8) Suggested Build Order After That

### 8.1 Core Admin Enforcement

- roles
- users
- addresses
- currencies
- subscription packages
- staff positions
- departments
- clinics

### 8.2 Clinical Workflow Enforcement

- patients
- visits
- triage
- consultations
- consultation order actions
- appointment workflow actions

### 8.3 SaaS / Support Enforcement

- facility switching
- subscription activation actions
- onboarding recovery actions
- tenant lifecycle operations

### 8.4 Test Hardening

- add forbidden-access feature coverage
- add regression coverage for role-specific access rules

---

## 9) Practical Conclusion

The project already has a strong permissions foundation, so this is not a restart problem.

What remains is the hardening layer:

- enforce permissions consistently
- test them properly
- make workflow-specific access rules explicit

Once that is done, Phase 1.5 can be called complete with confidence.
