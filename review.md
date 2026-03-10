# Phase 1-2 Implementation Review

## Scope Reviewed
- `hospital_database_schema.md`
- `implementation.md`
- Current codebase implementation for Phase 1, 1.5, and 2 (migrations, models, controllers, requests, actions, seeders, and existing tests)

Note: This review is static. Runtime verification (`php artisan migrate/test`) could not be executed in this shell because `php` is not installed.

## What You Have Done So Far

### Completed Foundation (Phase 1)
- Added core lookup/foundation migrations:
  - `countries`, `currencies`, `subscription_packages`, `addresses`, `allergens`
- Added matching models and seeders for core data.
- Added CRUD UI/controllers for several foundation entities (`addresses`, `currencies`, `allergens`, `subscription-packages`).

### Completed RBAC Baseline (Phase 1.5)
- Installed and migrated Spatie permission tables (`database/migrations/2026_03_06_091646_create_permission_tables.php`).
- Added `Role` and `Permission` models with UUID support.
- Seeded roles and permissions (`database/seeders/PermissionSeeder.php`).

### Completed Major Multi-tenant Structures (Phase 2)
- Added multi-tenant core tables:
  - `tenants`, `facility_branches`, `departments`, `staff`, `staff_branches`
- Added tenant-aware model trait and global scope:
  - `App\Traits\BelongsToTenant`
  - `App\Models\Scopes\TenantScope`
- Added support-user concept and tenant switch flow:
  - `users.is_support`
  - `FacilitySwitcherController`
- Added CRUD UI/controllers for `departments`, `staff`, `staff_positions`, and user management flow linked to staff.

## Good Work
- Good phased momentum: you built from foundation to tenancy instead of jumping to visits/clinical flows.
- Solid use of UUIDs and soft deletes across core healthcare entities.
- Reusable tenancy design pattern via `BelongsToTenant` trait is a good architectural direction.
- Seeders are practical and provide realistic starter data (tenants, branches, admin/support users, roles).
- Frontend and backend scaffolding are aligned for many modules (Inertia pages + controllers + actions + requests).

## Flaws and How to Correct Them

## Critical (Fix First)

1. Migration dependency order will break on clean databases
- Evidence:
  - `users.staff_id` references `staff` before `staff` table exists: `database/migrations/0001_01_01_000000_create_users_table.php:15`
  - `tenants.address_id` references `addresses` before `addresses` exists: `database/migrations/2026_03_09_000010_create_tenants_table.php:27`
  - `staff.department_id` references `departments` before `departments` exists: `database/migrations/2026_03_09_000035_create_staff_table.php:27`
- Why this is a blocker:
  - Fresh migration can fail due to unresolved foreign keys and circular dependencies (`staff <-> departments`, `users <-> staff`).
- Correction:
  - Keep base columns nullable at table creation, then add foreign keys in follow-up migrations after both referenced tables exist.
  - Break circular references explicitly:
    - Create `staff` without FK to `departments`.
    - Create `departments` without FK to `staff`.
    - Add FKs in a later migration (`Schema::table(...)`).

2. Tenant-switch endpoint is privilege escalation risk
- Evidence:
  - Routes are only `auth+verified`, no support-role gate: `routes/web.php:33,38,39`
  - Any authenticated user can call switch and set own `tenant_id`: `app/Http/Controllers/FacilitySwitcherController.php:30,36`
- Why this is a blocker:
  - A normal tenant user can switch into another tenant and access their data.
- Correction:
  - Protect routes/controller with explicit authorization (policy/gate/middleware) for `is_support` or `super_admin`.
  - Validate switch target is allowed for current principal.
  - Consider audit logging on every tenant switch.

3. Missing branch isolation (only tenant scope implemented)
- Evidence:
  - Only `TenantScope` exists (`app/Models/Scopes/TenantScope.php`), no branch scope.
  - Schema/plan expects both tenant and branch segmentation for many modules.
- Impact:
  - Data from multiple branches under one tenant is not isolated at query level.
- Correction:
  - Implement `BranchScope` and companion trait/context.
  - Store active branch in session/user context and apply global `where branch_id = ...` where relevant.

## High Priority

4. `staff_branches` table is implemented but not wired into create/edit workflows
- Evidence:
  - Controller sends branches: `app/Http/Controllers/StaffController.php:54,59`
  - Staff create page ignores branches prop: `resources/js/pages/staff/create.tsx:26-29`
  - Create action only creates staff row, no branch pivot sync: `app/Actions/CreateStaff.php:22`
- Impact:
  - Staff-branch assignment is not captured through UI; multi-branch staffing model is incomplete.
- Correction:
  - Add `branch_ids[]` (+ primary branch field) to request validation and forms.
  - In create/update actions, sync `staff_branches` inside DB transaction.

5. Validation uniqueness rules conflict with tenant-based uniqueness strategy
- Evidence:
  - Global unique checks in requests:
    - `unique:staff,email` and `unique:staff,employee_number` (`app/Http/Requests/StoreStaffRequest.php:19,23`)
    - `unique:staff,email,...` (`app/Http/Requests/UpdateStaffRequest.php:24`)
- Impact:
  - Cross-tenant duplicate staff emails/employee numbers are blocked even though schema expects tenant-scoped uniqueness.
- Correction:
  - Replace with tenant-scoped `Rule::unique(...)->where('tenant_id', auth()->user()->tenant_id)`.
  - Apply same principle to other tenant-owned resources.

6. User edit flow uses `name` field that is not persisted on `users` table
- Evidence:
  - `name` required in request: `app/Http/Requests/UpdateUserRequest.php:23`
  - Edit form posts `name`: `resources/js/pages/user/edit.tsx:58-59`
  - `User::$fillable` has no `name` (`app/Models/User.php:50-54`)
- Impact:
  - UX inconsistency and possible silent no-op on update.
- Correction:
  - Decide single source of truth:
    - Option A: remove `name` from user edit and edit linked `staff` names instead.
    - Option B: add `name` column to users and fully support it.

## Medium Priority

7. Missing runtime guards for staff-user one-to-one constraints
- Evidence:
  - `CreateUserRequest` validates `staff_id exists`, but not uniqueness/tenant ownership: `app/Http/Requests/CreateUserRequest.php:21`
  - Backend does not enforce `staff` belongs to active tenant or has no existing user.
- Impact:
  - Race conditions or crafted requests can create inconsistent user-staff links.
- Correction:
  - Add rules:
    - `Rule::exists('staff', 'id')->where('tenant_id', auth()->user()->tenant_id)`
    - Unique constraint on `users.staff_id` (if strict 1:1 desired).
  - Add server-side guard in action before create.

8. Test suite does not currently validate your new Phase 1/2 workflows
- Evidence:
  - Existing `UserControllerTest` targets old registration routes/fields (`tests/Feature/Controllers/UserControllerTest.php:10-27`) that do not match current route structure.
- Impact:
  - Regressions in tenancy/security/data integrity can pass unnoticed.
- Correction:
  - Add focused tests:
    - migration smoke test (fresh migrate)
    - tenant switch authorization
    - tenant-scoped query isolation
    - staff creation with branch sync
    - tenant-scoped uniqueness rules

## Recommended Fix Order
1. Resolve migration graph and FK sequencing.
2. Lock down tenant-switch authorization.
3. Implement branch scoping/context.
4. Wire `staff_branches` in staff create/edit requests/actions/UI.
5. Replace global unique validations with tenant-scoped rules.
6. Correct user `name` edit model mismatch.
7. Add/refresh feature tests for Phase 1/2 security and data integrity.

## Summary
You have completed a strong amount of foundational work through Phase 2 schema and scaffolding. The main risk now is not missing tables, but correctness and safety: migration dependency order, tenant/branch isolation enforcement, and runtime validation constraints. Once those are fixed, your foundation will be stable enough to move confidently into Phase 3+.
