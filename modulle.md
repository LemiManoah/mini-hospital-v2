# Module Packaging Review and Implementation Guide

**Date:** April 20, 2026  
**Goal:** Explain how to implement module packaging in this codebase so the same platform can be delivered cleanly as a hospital system, a general clinic system, a pharmacy-only system, a dental-only system, or other narrower healthcare products without exposing irrelevant workflows.

---

## 1) What Module Packaging Means Here

In this application, `module packaging` should mean:

- deciding which business areas are enabled for a tenant
- deciding which business areas are visible in the UI
- deciding which routes are accessible at runtime
- tailoring onboarding, roles, dashboards, and navigation to the tenant’s product type

This is not the same thing as permissions alone.

Permissions answer:

- can this user do this action?

Module packaging answers:

- should this tenant even see this part of the product at all?

That distinction is important.

Without module packaging, a pharmacy-only customer can still end up inside:

- triage
- appointments
- consultations
- laboratory
- hospital-style administration sections

even if those areas are not meaningful for that business.

---

## 2) What the Codebase Already Has

This codebase already contains strong building blocks for module packaging.

### 2.1 A Shared Tenant Context Already Exists

The Inertia shared payload already exposes tenant and user context in:

- [app/Http/Middleware/HandleInertiaRequests.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Middleware/HandleInertiaRequests.php)

It already shares:

- current user
- tenant
- subscription
- active branch
- permissions
- roles

That is the natural place to also share:

- enabled modules
- active facility type
- product package

### 2.2 Sidebar Navigation Is Already Centralized

The main app navigation is already built in:

- [resources/js/components/app-sidebar.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/app-sidebar.tsx)

Today it mostly filters sections by permission.

That means the sidebar is already ready to support:

- permission filtering
- module visibility filtering

without redesigning navigation from scratch.

### 2.3 There Is Already a Modules Landing Page

The app already has routes for:

- `/`
- `/modules`

defined in:

- [routes/web.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/routes/web.php)

This is useful because module packaging can naturally drive:

- what appears on the modules landing page
- what appears on the sidebar
- what default homepage a tenant should see

### 2.4 The Codebase Already Groups Some Functional Areas

There is already a strong concept of workspace/grouped functionality for:

- inventory
- laboratory
- pharmacy

Relevant files:

- [app/Support/InventoryWorkspace.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php)
- [app/Support/InventoryNavigationContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php)

This means the codebase already understands that some routes belong together as a product area.

That is a good foundation for broader package-level enablement.

### 2.5 Permissions and Roles Are Already Seeded Centrally

Permissions are centrally managed in:

- [database/seeders/PermissionSeeder.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/database/seeders/PermissionSeeder.php)

This is useful because module packaging should work *with* permissions, not replace them.

The correct model is:

- package enables a module
- role/permission controls what users inside that module can do

---

## 3) Why Permissions Alone Are Not Enough

If you rely only on permissions:

- the codebase still feels like one large hospital application
- tenants may still see irrelevant module shells
- onboarding becomes noisy
- reporting, dashboards, and menus feel cluttered
- support staff have to manually configure too many roles

Example:

A dental-only clinic may not want:

- pharmacy
- laboratory
- triage
- inpatient-like workflows

Even if users do not have some permissions, the system still needs a packaging layer so the product itself feels like a dental product instead of a hospital product with hidden buttons.

---

## 4) Recommended Packaging Model

The cleanest approach in this repo is:

### 4.1 Product Package

A tenant should have one primary package such as:

- `hospital`
- `general_clinic`
- `pharmacy_only`
- `dental_clinic`
- `laboratory_only`

This defines the broad product shape.

### 4.2 Enabled Modules

Inside that package, the tenant should also have explicit enabled modules such as:

- `dashboard`
- `patients`
- `appointments`
- `triage`
- `consultations`
- `laboratory`
- `pharmacy`
- `pharmacy_pos`
- `inventory`
- `billing`
- `reports`
- `administration`
- `dental`

This gives you flexibility.

For example:

- `pharmacy_only` package might enable `pharmacy`, `pharmacy_pos`, `inventory`, `billing`, `administration`
- `dental_clinic` package might enable `patients`, `appointments`, `consultations`, `billing`, `inventory`, `dental`, `administration`

### 4.3 Permissions

Permissions still remain user-level and role-level.

This means the final access decision becomes:

1. is the module enabled for the tenant?
2. is the route or screen visible for the package?
3. does the user have permission to use it?

That is the safest and cleanest layering.

---

## 5) Recommended Data Model

You have two good options.

### Option A: Simple JSON on `tenants`

Add fields to `tenants` such as:

- `product_package`
- `enabled_modules` JSON

Recommended when:

- you want a fast implementation
- module settings are not expected to become very complex

Example:

- `product_package = pharmacy_only`
- `enabled_modules = ["pharmacy","pharmacy_pos","inventory","billing","administration"]`

### Option B: Dedicated Module Tables

Recommended tables:

- `platform_modules`
- `tenant_enabled_modules`
- optionally `product_packages`
- optionally `product_package_modules`

This is better if you want:

- admin UI for package management
- module metadata
- cleaner reporting
- future licensing rules
- cleaner seeding and evolution

### Recommended Long-Term Design

I recommend the dedicated-table approach.

#### `platform_modules`

Fields:

- `id`
- `key`
- `name`
- `description`
- `is_core`
- `status`
- timestamps

Examples:

- `patients`
- `appointments`
- `triage`
- `consultations`
- `laboratory`
- `pharmacy`
- `pharmacy_pos`
- `inventory`
- `billing`
- `dental`
- `administration`

#### `product_packages`

Fields:

- `id`
- `key`
- `name`
- `description`
- `status`
- timestamps

Examples:

- `hospital`
- `general_clinic`
- `pharmacy_only`
- `dental_clinic`
- `laboratory_only`

#### `product_package_modules`

Fields:

- `id`
- `product_package_id`
- `platform_module_id`
- timestamps

This defines the default modules for each package.

#### `tenant_enabled_modules`

Fields:

- `id`
- `tenant_id`
- `platform_module_id`
- `enabled`
- `source`
- timestamps

`source` can help indicate whether the module came from:

- package default
- manual override
- support override

#### Tenant Update

Then `tenants` can store:

- `product_package_id`

This keeps packaging normalized.

---

## 6) Recommended Module Registry Layer

This codebase would benefit from a central module registry similar to the existing general settings registry.

Recommended class:

- `App\Support\Modules\ModuleRegistry`

Its job should be to define:

- module keys
- labels
- sidebar group names
- route prefixes
- dependency rules
- default landing pages

Example responsibilities:

- `pharmacy_pos` depends on `pharmacy`
- `consultations` usually depends on `patients`
- `dental` may depend on `patients`, `appointments`, `billing`

This avoids scattering module definitions across:

- sidebar files
- middleware
- controllers
- seeders

---

## 7) Recommended Runtime Support Classes

### 7.1 `TenantModuleResolver`

Recommended class:

- `App\Support\Modules\TenantModuleResolver`

Responsibilities:

- return enabled modules for a tenant
- answer `isEnabled($tenant, 'pharmacy')`
- cache tenant module state
- apply package defaults + tenant overrides

### 7.2 `ModuleVisibility`

Recommended class:

- `App\Support\Modules\ModuleVisibility`

Responsibilities:

- decide whether a sidebar group should show
- decide whether a modules landing card should show
- decide whether a feature should render in the UI

### 7.3 `ModuleRouteMap`

Recommended class:

- `App\Support\Modules\ModuleRouteMap`

Responsibilities:

- map route names and path prefixes to modules

Examples:

- `laboratory.* -> laboratory`
- `pharmacy.* -> pharmacy`
- `inventory.* -> inventory`
- `appointments.* -> appointments`
- `patients.* -> patients`

This will make route guarding much easier.

---

## 8) Recommended Middleware

This is the core enforcement piece.

### Add a Module Middleware

Recommended middleware:

- `EnsureModuleEnabled`

Usage examples:

- `middleware('module:pharmacy')`
- `middleware('module:laboratory')`
- `middleware('module:dental')`

This middleware should:

1. get authenticated user
2. get tenant
3. resolve enabled modules for tenant
4. abort with `404` or `403` if module is disabled

I recommend:

- `404` for tenant-facing disabled modules so the app feels smaller and cleaner
- `403` only where explicit authorization failure is more appropriate

### Why Middleware Matters

Do not rely only on:

- hidden sidebar links
- hidden buttons

Because users can still paste URLs directly.

Module packaging must be enforced server-side.

---

## 9) How to Wire the UI

### 9.1 Shared Inertia Data

Extend:

- [app/Http/Middleware/HandleInertiaRequests.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Middleware/HandleInertiaRequests.php)

Add shared tenant packaging data such as:

- `product_package`
- `enabled_modules`
- maybe `default_module_home`

Example shape:

```php
'tenant_modules' => [
    'package' => 'pharmacy_only',
    'enabled' => [
        'dashboard',
        'pharmacy',
        'pharmacy_pos',
        'inventory',
        'billing',
        'administration',
    ],
],
```

### 9.2 Sidebar Filtering

Update:

- [resources/js/components/app-sidebar.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/app-sidebar.tsx)

Today the sidebar mainly checks permission.

It should also check:

- whether the owning module is enabled

Example:

- show `Laboratory` only if `laboratory` enabled
- show `Pharmacy` only if `pharmacy` enabled
- show `Pharmacy POS` only if `pharmacy_pos` enabled

### 9.3 Modules Landing Page

The `modules` page should become a real packaging-aware home screen.

It should show only enabled modules for the tenant and send users into the right workspace.

Examples:

- pharmacy-only tenant sees pharmacy, POS, inventory, sales history
- dental clinic sees patients, appointments, dental consultations, billing

### 9.4 Empty-State Messaging

If a user has permission but the module is disabled, the UI should not tease unavailable areas.

Avoid:

- “You lack permission” when the real issue is package disablement

Prefer:

- not showing the module at all

---

## 10) Recommended Onboarding Strategy

Packaging is best chosen during onboarding.

### Recommended Onboarding Step

Add a step where a new tenant selects a facility type such as:

- hospital
- general clinic
- pharmacy only
- dental clinic
- laboratory only

That selection should:

- assign a default product package
- enable default modules
- seed recommended roles
- set default dashboards
- optionally set some general settings

### Why This Is Important

It prevents support or admins from having to manually disable many unrelated areas after tenant creation.

---

## 11) Role Templates Should Follow the Package

This is very important.

The current permission system is broad and useful, but package-aware role templates will make deployment much cleaner.

### Example: Pharmacy-Only Tenant

Role templates might be:

- `pharmacy_admin`
- `pharmacist`
- `cashier`
- `store_keeper`

### Example: Dental Clinic Tenant

Role templates might be:

- `dental_admin`
- `dentist`
- `dental_nurse`
- `receptionist`
- `cashier`

This is better than assuming every tenant needs:

- lab technician
- nurse
- pharmacist
- store keeper
- doctor

all at once.

---

## 12) Module Dependencies

Some modules should not be independently enabled without context.

You should define dependency rules in the registry.

### Example Dependency Rules

- `pharmacy_pos` depends on `pharmacy`
- `triage` depends on `patients`
- `consultations` depends on `patients`
- `laboratory` usually depends on `patients`
- `dental` depends on `patients`, `appointments`, maybe `billing`
- `inventory` can be standalone or shared depending on package

### How to Enforce

When enabling modules:

- validate dependencies before save

When resolving modules:

- derived dependencies can be auto-included if desired

My recommendation:

- validate and store explicitly
- do not rely too heavily on hidden auto-enable behavior

That makes support and debugging easier.

---

## 13) Packaging and Subscription

This codebase already has subscription structures.

That means module packaging can eventually tie into commercial rules too.

Possible future patterns:

- some packages include only certain modules
- higher tiers unlock more modules
- support can override modules for special tenants

But do not overcomplicate that on day one.

For version 1:

- package choice should drive enabled modules
- billing/package enforcement can stay simple

---

## 14) Recommended Implementation Phases

### Phase 1: Read-Only Packaging Layer

Deliverables:

- module registry
- tenant enabled modules storage
- shared Inertia payload
- sidebar filtering
- modules landing page filtering

Why first:

- immediately improves tenant experience
- low risk
- no business logic changes yet

### Phase 2: Route Enforcement

Deliverables:

- `EnsureModuleEnabled` middleware
- route-to-module mapping
- apply middleware to major module route groups

Why next:

- closes direct URL access gaps
- makes packaging real, not cosmetic

### Phase 3: Onboarding and Role Templates

Deliverables:

- facility type selection during onboarding
- package default modules
- package-specific role template seeding

Why then:

- avoids manual configuration burden
- makes new tenant creation cleaner

### Phase 4: Admin Management UI

Deliverables:

- tenant module management screen
- package override controls
- audit trail of module changes

### Phase 5: Subscription and Reporting Integration

Deliverables:

- package reporting
- module usage analytics
- package-aware licensing rules if needed

---

## 15) Concrete First Implementation in This Repo

If you want the cleanest first slice here, I would implement it in this order:

1. Create tables:
   - `platform_modules`
   - `product_packages`
   - `product_package_modules`
   - `tenant_enabled_modules`
2. Add:
   - `product_package_id` to `tenants`
3. Seed modules such as:
   - dashboard
   - patients
   - appointments
   - triage
   - consultations
   - laboratory
   - pharmacy
   - pharmacy_pos
   - inventory
   - billing
   - dental
   - administration
4. Seed packages such as:
   - hospital
   - general_clinic
   - pharmacy_only
   - dental_clinic
   - laboratory_only
5. Add `TenantModuleResolver`
6. Share enabled modules through `HandleInertiaRequests`
7. Update `app-sidebar.tsx` to check module enablement
8. Add `EnsureModuleEnabled` middleware
9. Apply middleware to laboratory, pharmacy, POS, and future dental route groups
10. Update onboarding to select facility type and assign package defaults

That gets you a real packaging framework without trying to solve every subscription or marketplace scenario at once.

---

## 16) Example Package Definitions

### Hospital

Enabled modules:

- dashboard
- patients
- appointments
- triage
- consultations
- laboratory
- pharmacy
- pharmacy_pos
- inventory
- billing
- administration

### General Clinic

Enabled modules:

- dashboard
- patients
- appointments
- consultations
- laboratory
- pharmacy
- pharmacy_pos
- billing
- administration

### Pharmacy Only

Enabled modules:

- dashboard
- pharmacy
- pharmacy_pos
- inventory
- billing
- administration

Usually disabled:

- triage
- consultations
- laboratory
- appointments

### Dental Clinic

Enabled modules:

- dashboard
- patients
- appointments
- consultations
- dental
- inventory
- billing
- administration

Usually disabled:

- pharmacy
- pharmacy_pos
- laboratory
- triage if not desired

### Laboratory Only

Enabled modules:

- dashboard
- laboratory
- inventory
- administration

---

## 17) Important Design Decisions

### Do Not Mix Packaging With Permissions

Keep them separate.

- packaging = tenant product shape
- permissions = user capability

### Hide and Enforce

Always do both:

- hide disabled modules in UI
- enforce disabled modules on the backend

### Prefer Explicit Package Metadata

Do not hardcode many `if tenant is dental` checks across the app.

Use:

- registry
- resolver
- middleware

### Keep Core Shared Foundations Reusable

The same shared systems can power many packages:

- tenants
- branches
- users
- permissions
- inventory
- patients
- billing

Packaging should sit on top of these, not fragment them.

---

## 18) Definition of Done

Module packaging should be considered complete when:

- tenants have a defined product package
- tenants have explicit enabled modules
- sidebar only shows enabled modules
- modules landing page only shows enabled modules
- disabled module routes are blocked server-side
- onboarding assigns package defaults
- role templates follow the selected package
- support/admins can understand and manage packaging without code changes

---

## 19) Bottom Line

The right way to implement module packaging in this codebase is:

**package-level tenant module enablement layered on top of existing permissions**

not permissions alone and not UI hiding alone.

This codebase already has the foundations:

- tenant context
- centralized sidebar
- modules page
- permissions
- grouped workspaces

So the next clean move is to add:

1. a module registry
2. tenant enabled modules storage
3. shared resolver support
4. sidebar filtering
5. route middleware
6. onboarding package selection

Once that is done, this same system can feel like:

- a hospital platform
- a pharmacy product
- a dental clinic product
- a laboratory product

while still sharing one core codebase.
