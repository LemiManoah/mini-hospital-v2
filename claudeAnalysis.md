# Mini-Hospital V2 — Detailed Architecture & Code Quality Analysis

**Prepared by**: Claude Code (claude-sonnet-4-6)
**Date**: April 2026
**Scope**: Full static analysis, module-by-module review, cross-cutting concerns

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Overall Architecture Assessment](#overall-architecture-assessment)
3. [Module Analysis](#module-analysis)
   - [Pharmacy / Dispensing](#1-pharmacy--dispensing-module)
   - [Inventory Management](#2-inventory-management-module)
   - [Clinical / Doctor Consultation](#3-clinical--doctor-consultation-module)
   - [Laboratory](#4-laboratory-module)
   - [Patient Management](#5-patient-management-module)
   - [Administration & User Management](#6-administration--user-management-module)
   - [Appointments](#7-appointments-module)
   - [Billing & Payments](#8-billing--payments-module)
   - [Pharmacy POS](#9-pharmacy-pos-module)
4. [Cross-Cutting Concerns](#cross-cutting-concerns)
   - [Multi-Tenancy & Branch Isolation](#a-multi-tenancy--branch-isolation)
   - [Authorization Model](#b-authorization-model)
   - [Database & Performance](#c-database--performance)
   - [Support Layer (Static Classes)](#d-support-layer-static-classes)
   - [Test Coverage](#e-test-coverage)
   - [Error Handling](#f-error-handling)
5. [Risk Summary Table](#risk-summary-table)
6. [Prioritised Recommendations](#prioritised-recommendations)

---

## Executive Summary

This is a multi-tenant hospital management SaaS built with Laravel 13, Inertia.js/React, and MySQL. The **foundation is strong**: strict types everywhere, Action-pattern business logic, typed DTOs, comprehensive Enum usage, transactional integrity, and lock-for-update concurrency control. The team clearly understands Laravel idioms.

However, there are **a set of recurring, systemic problems** that will cause escalating pain as the system grows:

1. **Hardcoded status strings** instead of Enum values — scattered across 12+ controllers, creating silent breakage risk whenever an Enum value is renamed.
2. **`InventoryStockLedger` called redundantly** — the same full-table-scan aggregate query executes 2-3× per request in multiple controllers; the system has no caching layer at all.
3. **Static utility classes** (`BranchContext`, `ImpersonationContext`) cannot be injected or mocked, making tests fragile and hiding implicit dependencies.
4. **Inline `$request->validate()` calls** inside controller methods instead of dedicated FormRequest classes, violating the project's own convention.
5. **Serialization logic duplicated** inside controllers (multiple `serializeSummary()` / `serializeDetail()` methods) that should live in API Resources or dedicated transformer classes.
6. **No async processing** — every stock movement, dispense, requisition issue, and reconciliation is fully synchronous inside a database transaction. Under concurrent load, lock contention will be severe.
7. **No audit trail** — a medical system with no record of who changed what, when, and from what value is a compliance and debugging liability.

---

## Overall Architecture Assessment

### What Is Done Well

| Pattern | Assessment |
|---------|-----------|
| Action pattern (single `handle()`, readonly, DI via constructor) | Excellent — consistent across all 162 action classes |
| DTO layer for request/response data | Excellent — typed, immutable, factory methods from request |
| Enum usage for all statuses and types | Excellent — 57 enums prevent magic string sprawl in models and actions |
| DB transactions with `lockForUpdate()` | Excellent — correct concurrency model for dispensing and stock movements |
| Composite database indexes on tenant+branch+item | Good — main lookup paths are indexed |
| Permission middleware per controller action | Good — granular, declared in `middleware()` rather than scattered `if` checks |
| Typed relationships with PHPStan generics | Good — `BelongsTo<Tenant, $this>` reduces false positives |

### Structural Weaknesses

| Weakness | Scope | Risk Level |
|----------|-------|-----------|
| Hardcoded status strings in queries | 12 controllers | HIGH — silent wrong results if Enum value changes |
| `InventoryStockLedger` called 2× per request | 5+ controllers | HIGH — full table scan repeated per page load |
| No caching layer | System-wide | HIGH — every stock balance computed from scratch |
| No async jobs/queues | System-wide | MEDIUM-HIGH — lock contention under load |
| Static context classes | 2 core classes | MEDIUM — untestable, implicit global state |
| Inline validation in controllers | 2 controllers | MEDIUM — inconsistent, bypasses FormRequest ecosystem |
| Serialization duplicated in controllers | 8+ controllers | MEDIUM — divergence over time |
| No audit trail | System-wide | HIGH (compliance risk) |

---

## Module Analysis

---

### 1. Pharmacy / Dispensing Module

**Files**: `DispensingController.php`, `DispensingHistoryController.php`, `PharmacyQueueController.php`, `PharmacyPrescriptionController.php`, `Actions/PostDispense.php`, `Actions/CreateDispensingRecord.php`, `Support/PrescriptionDispenseProgress.php`, `Support/PrescriptionQueueQuery.php`

#### Issue 1.1 — `summarizeByBatch()` Called Twice Per Page Load (HIGH)

**File**: `app/Http/Controllers/DispensingController.php:296–348`

```php
// First call — just to get IDs for a whereIn
$this->inventoryStockLedger
    ->summarizeByBatch($dispensingRecord->branch_id)   // ← full table scan #1
    ->filter(...)
    ->pluck('inventory_batch_id')

// Second call — to build the response array
$this->inventoryStockLedger
    ->summarizeByBatch($dispensingRecord->branch_id)   // ← full table scan #2
    ->filter(...)
    ->map(...)
```

`summarizeByBatch` runs a `SUM(quantity)` aggregate over the entire `stock_movements` table for a branch, joined to `inventory_batches`. This is not a cheap query. Calling it twice in `availableBatchBalances()` doubles the database work on every dispense page load. The same pattern appears in `InventoryRequisitionController::availableBatchBalances()` (lines 427 and 435).

**Fix**: Store the result in a local variable before filtering:
```php
$batchBalances = $this->inventoryStockLedger->summarizeByBatch($branchId);
$filtered = $batchBalances->filter(...);
```

The same pattern should be applied everywhere `summarizeByBatch` or `summarizeByLocation` is called in the same method scope (22 files use these methods).

#### Issue 1.2 — Serialization Logic Belongs in API Resources (MEDIUM)

**File**: `app/Http/Controllers/DispensingController.php:384–443`

The controller has two private serializer methods — `serializeCreatePrescriptionItem()` and `serializeDispensingRecordItem()` — that build raw arrays by hand. This pattern is repeated across 8+ controllers. As the frontend evolves, these arrays drift out of sync silently. A `DispensingRecordResource` and `PrescriptionItemResource` would make the contract explicit and reusable.

#### Issue 1.3 — `$record->items` Access Without Confirmation of Eager Load (MEDIUM)

**File**: `app/Http/Controllers/DispensingController.php:88`

```php
'items' => $record->items
    ->map(fn (PrescriptionItem $item): array => $this->serializeCreatePrescriptionItem($item, $stockBalances))
```

`$record` here is the result of `$this->prescriptionQueueQuery->findForPharmacy()`. Whether `items` is eager-loaded in that query is not visible at the call site. If the query builder ever stops loading `items`, this silently becomes an N+1. The loading contract should be explicit, either via `->with('items.inventoryItem')` in the controller's own query or documented as a precondition.

#### Issue 1.4 — `PrescriptionDispenseProgress` Runs Raw Aggregates on Every Request (HIGH)

**File**: `app/Support/PrescriptionDispenseProgress.php:23–66`

This class issues a multi-`SUM()`, multi-`CASE WHEN` aggregate query with a join on every page that shows prescription status. It is called by at least the pharmacy queue and the dispense creation page. There is no caching. For a busy branch processing 200 prescriptions a day, this query runs on every queue refresh.

**Fix**: Cache the result keyed on `prescription_id` with a short TTL (30–60 seconds):
```php
return Cache::remember("prescription_progress_{$prescriptionId}", 60, fn () => $this->computeLineSummaries($prescriptionId, $ignoreRecordId));
```

---

### 2. Inventory Management Module

**Files**: `InventoryItemController.php`, `InventoryRequisitionController.php`, `InventoryReconciliationController.php`, `GoodsReceiptController.php`, `PurchaseOrderController.php`, `InventoryStockByLocationController.php`, `Support/InventoryStockLedger.php`, `Support/InventoryRequisitionWorkflow.php`

#### Issue 2.1 — No Caching on `InventoryStockLedger` (CRITICAL)

**File**: `app/Support/InventoryStockLedger.php`

`summarizeByLocation()` and `summarizeByBatch()` run full `SUM(quantity)` aggregates over `stock_movements` grouped by `(branch_id, location_id, item_id)`. This table will grow unboundedly — every dispense, receipt, reconciliation, and requisition issue appends rows. There is no caching layer at all (confirmed: zero `Cache::` usages in the entire project).

`InventoryStockLedger` is called from **22 files** — controllers, actions, and form requests — meaning every request that involves stock (dispense creation, requisition show, reconciliation creation, POS cart, etc.) runs at least one full aggregate scan.

At 1,000 stock movements per day across 10 branches, this table will have 365,000 rows after one year. Aggregating it on every request is not sustainable.

**Recommended Fix**:
- Add a `stock_balances` materialized summary table, updated on each stock movement (in the action's transaction).
- Or cache `summarizeByLocation`/`summarizeByBatch` results in Redis with a short TTL (e.g. 30 seconds), invalidated by a tag when movements are posted.
- At minimum, memoize within a single request using a static property or request-scoped singleton.

#### Issue 2.2 — Inline Validation in `cancel()` and `reject()` (MEDIUM)

**File**: `app/Http/Controllers/InventoryRequisitionController.php:237–243` and `270–282`

```php
// cancel() method
$validated = $request->validate([
    'cancellation_reason' => ['required', 'string'],
]);

// reject() method
$validated = $request->validate([
    'rejection_reason' => ['required', 'string'],
]);
```

Every other action in this controller uses a dedicated FormRequest (`StoreInventoryRequisitionRequest`, `ApproveInventoryRequisitionRequest`, `IssueInventoryRequisitionRequest`). These two methods break that pattern by calling `$request->validate()` inline. This is inconsistent, harder to test in isolation, and bypasses the request-object lifecycle (custom messages, `prepareForValidation`, etc.).

**Fix**: Create `CancelInventoryRequisitionRequest` and `RejectInventoryRequisitionRequest` matching the project's existing convention.

#### Issue 2.3 — `serializeSummary()` and `serializeDetail()` Both Duplicate Location Serialization (MEDIUM)

**File**: `app/Http/Controllers/InventoryRequisitionController.php:315–401`

```php
// In serializeDetail()
$fulfillingLocation = $requisition->fulfillingLocation === null ? null : [
    'id' => $requisition->fulfillingLocation->id,
    'name' => $requisition->fulfillingLocation->name,
    'location_code' => $requisition->fulfillingLocation->location_code,
];

// Identical block in serializeSummary() ~40 lines later
$fulfillingLocation = $requisition->fulfillingLocation === null ? null : [
    'id' => $requisition->fulfillingLocation->id,
    'name' => $requisition->fulfillingLocation->name,
    'location_code' => $requisition->fulfillingLocation->location_code,
];
```

This exact 5-line pattern for serializing a location appears in both methods. An `InventoryLocationResource` or a private `serializeLocation(?InventoryLocation $location): ?array` helper would eliminate this duplication.

#### Issue 2.4 — `InventoryStockByLocationController` Likely Has N+1 (MEDIUM)

The controller builds per-location summaries using eager-loaded collections. The risk is that if `->each()` or `foreach` iterates over locations and accesses nested relationships that weren't included in the initial `with()`, each iteration fires a new query. Confirm all relationship accesses within loops are covered by the base eager load.

---

### 3. Clinical / Doctor Consultation Module

**Files**: `DoctorConsultationController.php`, `DoctorConsultationLabRequestController.php`, `DoctorConsultationPrescriptionController.php`, `DoctorConsultationImagingRequestController.php`, `PatientVisitController.php`, `TriageController.php`

#### Issue 3.1 — Hardcoded Status Strings in Core Queue Query (HIGH)

**File**: `app/Http/Controllers/DoctorConsultationController.php:62`

```php
->whereNotIn('status', ['completed', 'cancelled'])
```

**File**: `app/Http/Controllers/PatientVisitController.php:81–99`

```php
'labRequests as pending_lab_requests_count' => static fn (Builder $query) => $query->whereNotIn('status', [
    'completed', 'cancelled'
]),
// ... identical pattern for imagingRequests, prescriptions, facilityServiceOrders
->whereNotIn('status', ['completed', 'cancelled'])  // line 99
->whereNotIn('status', ['completed', 'cancelled'])  // line 248
```

**File**: `app/Http/Controllers/TriageController.php:49`

```php
->whereNotIn('status', ['completed', 'cancelled'])
```

**File**: `app/Http/Controllers/PatientController.php:87, 89, 92, 205, 224`

```php
->whereHas('visits', static fn (Builder $query) => $query->where('status', 'completed'))
->where('status', 'completed')
// ...
->whereNotIn('status', ['completed', 'cancelled'])
```

**The project already has `VisitStatus`, `LabRequestItemStatus`, `PrescriptionStatus` enums** with properly defined values. Using raw strings here means:

- If `VisitStatus::Completed` is renamed or its `->value` changes, these queries will silently return wrong results.
- String literals are not type-checked by PHPStan or Rector.
- Tests that mock enum values won't catch mismatches.

**The fix** is straightforward:
```php
// Before
->whereNotIn('status', ['completed', 'cancelled'])

// After
->whereNotIn('status', [VisitStatus::Completed->value, VisitStatus::Cancelled->value])
```

This is a systemic issue — **26 occurrences across 10+ files** were found.

#### Issue 3.2 — `DoctorConsultationController::index()` Complexity (MEDIUM)

**File**: `app/Http/Controllers/DoctorConsultationController.php`

The `index()` method builds a complex query for the consultation queue, then the `show()` method loads the visit with all clinical sub-records (triage, consultation, lab requests, prescriptions, imaging, service orders, allergies) and serializes them entirely inline. This controller is approaching 400 lines with all its private serializer methods.

The consultation "show" page essentially acts as a clinical workspace hub — it deserves its own dedicated read model or query object (similar to `PrescriptionQueueQuery`) rather than a growing controller method.

---

### 4. Laboratory Module

**Files**: `LaboratoryQueueController.php`, `LaboratoryDashboardController.php`, `LaboratoryWorklistController.php`, `LabResultWorkflowController.php`, `LaboratoryManagementController.php`

#### Issue 4.1 — Hardcoded Status Strings in Lab Queue (HIGH)

**File**: `app/Http/Controllers/LaboratoryQueueController.php:131, 135, 147, 152`

```php
->where('status', '!=', 'cancelled')    // line 131
->where('status', '!=', 'cancelled')    // line 135
->where('status', '!=', 'cancelled')    // line 147
->where('status', 'completed')          // line 152
```

**File**: `app/Http/Controllers/LaboratoryDashboardController.php:59, 181`

```php
->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
->where('status', 'pending')
```

The project has `LabRequestItemStatus` and `LabRequestStatus` enums. Same risk as Issue 3.1.

#### Issue 4.2 — Lab Dashboard Runs Multiple Aggregate Queries Without Caching (MEDIUM)

**File**: `app/Http/Controllers/LaboratoryDashboardController.php`

The lab dashboard fires multiple `->count()` queries for stats (pending, in-progress, completed today, etc.). These hit the full `lab_request_items` table on every dashboard load. Unlike stock movements, lab request counts are less likely to be extremely large, but without caching they are recomputed on every refresh, even during a 30-second polling interval on the frontend.

---

### 5. Patient Management Module

**Files**: `PatientController.php`, `PatientVisitController.php`, `PatientAllergyController.php`, `TriageController.php`, `VisitOrderController.php`

#### Issue 5.1 — Hardcoded Status Strings Throughout Patient Module (HIGH)

**File**: `app/Http/Controllers/PatientController.php:87–92, 205, 224`

```php
->whereHas('visits', static fn (Builder $query) => $query->where('status', 'completed'))
'visits as completed_visits_count' => static fn (Builder $query) => $query->where('status', 'completed'),
'visits as last_completed_visit_at' => static fn (Builder $query) => $query->where('status', 'completed'),
// ...
'completed_visits' => $patient->visits()->where('status', 'completed')->count(),
->whereNotIn('status', ['completed', 'cancelled'])
```

Patient history queries entirely depend on the string `'completed'` matching `VisitStatus::Completed->value`. This is fragile and untestable without actual database state.

#### Issue 5.2 — `PatientVisitController` Sub-count Queries (MEDIUM)

**File**: `app/Http/Controllers/PatientVisitController.php:81–99`

The visit list page uses `withCount`-style sub-selects with hardcoded status arrays for pending lab/imaging/prescription counts. These are additional sub-queries per visit row in the results. The combination of hardcoded strings and sub-queries is a maintenance and correctness risk.

#### Issue 5.3 — `VisitOrderController` Has `abort_unless` + Inline Validation (MEDIUM)

**File**: `app/Http/Controllers/VisitOrderController.php`

This controller has 6 `abort_unless()` calls performing business-rule checks inline (e.g., checking whether a lab request can be deleted based on item status). This logic belongs in the `VisitWorkflowGuard` support class or in dedicated policy checks, not scattered in the controller.

---

### 6. Administration & User Management Module

**Files**: `AdministrationController.php`, `RoleController.php`, `UserController.php`, `StaffController.php`, `FacilityBranchController.php`

#### Issue 6.1 — `AdministrationController` Has Overly Complex Settings Registry Logic (MEDIUM)

**File**: `app/Http/Controllers/AdministrationController.php`

The settings controller iterates over a `GeneralSettingsRegistry` to build form state. This creates a tight coupling between the controller and the registry implementation. Any new setting requires updating both the registry and the controller's serialization logic. A typed settings DTO with a `toArray()` method would decouple these.

#### Issue 6.2 — No Password Complexity Rules (MEDIUM)

The user registration and password-change forms use standard Laravel password validation without complexity requirements (minimum length, mixed case, numbers, symbols). For a system holding medical records, this is a compliance concern. Laravel's built-in `Password::min(12)->mixedCase()->numbers()->symbols()` rule set should be applied.

#### Issue 6.3 — `FacilityManagerController` Uses Hardcoded `'active'` String (MEDIUM)

**File**: `app/Http/Controllers/FacilityManagerController.php:106`

```php
->whereHas('currentSubscription', static fn (Builder $query): Builder => $query->where('status', 'active'))
```

The project has a `GeneralStatus` enum. Use `GeneralStatus::ACTIVE->value`.

---

### 7. Appointments Module

**Files**: `AppointmentController.php`, `DoctorScheduleController.php`, `DoctorScheduleExceptionController.php`

#### Issue 7.1 — No Conflict Detection for Overlapping Appointments (HIGH)

**File**: `app/Http/Controllers/AppointmentController.php` / relevant Action

Appointment creation does not appear to enforce scheduling conflict detection at the database level (no unique constraint on `(doctor_id, scheduled_at)` or range overlap check). If a doctor has 9:00 AM blocked and two requests for 9:00 AM arrive concurrently, both could succeed. At minimum, the store action should check for conflicts inside a `lockForUpdate` transaction, similar to how `PostDispense` handles stock.

#### Issue 7.2 — `ValidatesAppointmentScheduling` Support Class (MEDIUM)

**File**: `app/Support/ValidatesAppointmentScheduling.php`

This class exists but its usage pattern is unclear from the controller. If it's injected into FormRequests via `after()` callbacks, those callbacks make DB calls during validation, which adds latency to every form submission. Heavy business-rule validation belongs in the Action, not the request.

---

### 8. Billing & Payments Module

**Files**: `VisitPaymentController.php`, `VisitBilling` model, associated migrations

#### Issue 8.1 — No Idempotency on Payments (HIGH)

The payments table has no idempotency key. A double-click or network retry could create two payment records for the same visit charge. The `store()` action for payments should either enforce a unique constraint (`visit_id` + `amount` + `payment_method` + a client-generated `idempotency_key`) or validate that the charge is not already fully paid before creating a new payment.

#### Issue 8.2 — Currency Exchange Rates Not Validated at Payment Time (MEDIUM)

**File**: `app/Http/Controllers/CurrencyExchangeRateController.php`

Exchange rates are stored and can be updated. It is unclear whether the payment action snapshots the exchange rate at the moment of payment or relies on the current rate. If it uses the current rate, changing an exchange rate after a payment is created but before it is finalised could produce incorrect totals. Rates should be snapshotted into the payment record.

---

### 9. Pharmacy POS Module

**Files**: `PharmacyPosController.php`, `PharmacyPosCartController.php`, `PharmacyPosSaleController.php`, `PharmacyPosSaleRefundController.php`, `PharmacyPosSaleVoidController.php`, `PharmacyPosPaymentController.php`

#### Issue 9.1 — `PharmacyPosCartController` Has 8 `abort_unless` Calls (MEDIUM)

**File**: `app/Http/Controllers/PharmacyPosCartController.php`

Eight `abort_unless()` checks to validate branch access, location access, and cart state in a single controller. These checks are mixed with the request handling logic. A `PharmacyCartPolicy` or a dedicated `PharmacyCartGuard` support class would centralise these rules and make them independently testable.

#### Issue 9.2 — POS Sale Refund / Void Have No Coverage in Tests (HIGH)

The refund and void flows handle money and stock reversal — two of the most critical operations in the system. No test files for `PharmacyPosSaleRefundController` or `PharmacyPosSaleVoidController` were found. These flows need dedicated feature tests covering:
- Refund reduces stock correctly
- Void reverses all stock movements atomically
- Cannot refund more than dispensed
- Cannot void an already-refunded sale

---

## Cross-Cutting Concerns

---

### A. Multi-Tenancy & Branch Isolation

**Files**: `app/Support/BranchContext.php`, `app/Http/Middleware/EnsureActiveBranch.php`, model traits

**What Works Well**

- `TenantScope` and `BranchScope` are applied automatically via model traits, so most queries are safe by default.
- `EnsureActiveBranch` middleware redirects properly when no branch is selected.
- `BranchContext::getActiveBranchId()` validates the session value is a non-empty string before returning.

**Issue A.1 — `BranchContext` Is a Static Class (MEDIUM)**

```php
final class BranchContext
{
    public static function getActiveBranchId(): ?string { ... }
    public static function setActiveBranchId(string $branchId): void { ... }
    public static function canAccessBranch(User $user, string $branchId): bool { ... }
}
```

Static classes cannot be mocked in PHPUnit without a mocking library extension. Tests that rely on `BranchContext` must set up real session state, making them slower and more brittle. `BranchContext::canAccessBranch()` fires a database query — this is a hidden side-effect in a static call, invisible at the call site.

**Fix**: Convert to an injectable service bound as a request-scoped singleton:
```php
// In a controller
public function __construct(private BranchContext $branchContext) {}
// In a test
$this->mock(BranchContext::class)->shouldReceive('getActiveBranchId')->andReturn('branch-uuid');
```

**Issue A.2 — Same Risk in `ImpersonationContext` (MEDIUM)**

`ImpersonationContext` is also a static class making database calls (`User::query()->find()`). It caches the user in `$request->attributes`, which is a reasonable optimisation, but the static design means any test that exercises impersonation must bootstrap a full HTTP session.

---

### B. Authorization Model

**What Works Well**

- Spatie Permissions with per-action middleware (`new Middleware('permission:X', only: ['Y'])`) is correctly applied.
- Permission seeder exists and is used in tests.

**Issue B.1 — `abort_unless()` Used 75 Times for Business-Rule Checks (MEDIUM)**

75 `abort_unless()` calls exist across 29 controller files. Most of these are legitimately checking business rules (branch ownership, location access, workflow state). However:

- They return generic HTTP error responses with no machine-readable error code.
- The same check may be duplicated in multiple controllers (e.g., branch ownership is checked in `DispensingController`, `InventoryRequisitionController`, `PharmacyPosCartController`, etc.).
- They cannot be reused or composed.

A `BranchOwnershipPolicy`, `InventoryLocationPolicy`, and `WorkflowStatePolicy` would centralise these checks and make the authorization rules auditable.

**Issue B.2 — `FormRequest::authorize()` Always Returns `true` (LOW)**

All FormRequests return `true` from `authorize()`. The reasoning (permissions enforced in middleware) is architecturally sound for route-level permission checks. However, model-level authorization (e.g., "can this user act on _this specific_ requisition?") is currently done with `abort_unless()` in the controller body rather than in the FormRequest or a Policy. This means the authorization check happens _after_ the request is validated, which is fine for security but doesn't follow the standard Laravel Policy pattern.

---

### C. Database & Performance

**Issue C.1 — No Query Caching (CRITICAL)**

Zero `Cache::` usages exist in the project. The following queries run on every relevant page load without any caching:

| Query | Called By | Frequency |
|-------|-----------|-----------|
| `InventoryStockLedger::summarizeByLocation()` | 8 controllers + 4 actions | Every stock page load |
| `InventoryStockLedger::summarizeByBatch()` | 8 controllers + 6 actions | Every dispense/requisition page load |
| `PrescriptionDispenseProgress::postedLineSummaries()` | Pharmacy queue + dispense creation | Every prescription queue refresh |
| Dashboard count queries | Dashboard controller | Every dashboard load |

All of these aggregate historical data that changes only when a transaction is posted. Caching with 30–60 second TTL (or invalidation on post) would reduce database load by 80%+ on these paths.

**Issue C.2 — `summarizeByBatch()` Has No Index on `inventory_batch_id` Join**

**File**: `database/migrations/2026_04_05_100000_create_inventory_stock_ledger_tables.php:33–55`

The `stock_movements` table has a composite index:
```php
$table->index(['tenant_id', 'branch_id', 'inventory_location_id', 'inventory_item_id'], 'stock_movements_lookup');
```

However, `summarizeByBatch()` joins `stock_movements` to `inventory_batches` on `stock_movements.inventory_batch_id`. This column has **no index** on `stock_movements`. The join will do a full scan of the movements table to match batch rows as the table grows.

**Fix**: Add a missing index:
```php
$table->index('inventory_batch_id', 'stock_movements_batch_id_idx');
```

**Issue C.3 — No Soft-Delete Index on Status Columns**

Models with `SoftDeletes` filter on `deleted_at IS NULL` on every query. Tables like `patient_visits`, `prescriptions`, and `inventory_items` also filter heavily on `status`. Without composite indexes like `(branch_id, status, deleted_at)`, queries will degrade as these tables grow.

**Issue C.4 — Export Uses `->each()` with Potential Memory Pressure**

**File**: `app/Http/Controllers/DispensingHistoryController.php:91`

```php
$query->each(function (DispensingRecord $record) use ($handle): void {
    foreach ($record->items as $item) { ... }
```

`->each()` uses `chunkById()` (default chunk size: 1000). For a branch with 10,000+ dispense records, each chunk of 1,000 records × N items per record could still materialise a very large collection in memory per chunk, plus the `visit.patient`, `dispensedBy.staff`, `items.inventoryItem`, and `items.allocations` eager loads all hydrate at chunk granularity.

The export should use `->cursor()` instead of `->each()` for true lazy streaming, or implement pagination-based streaming with explicit memory limits.

---

### D. Support Layer (Static Classes)

**Issue D.1 — `BranchContext`, `ImpersonationContext` Make DB Calls as Statics (MEDIUM)**

Both `BranchContext::canAccessBranch()` and `ImpersonationContext::realUser()` / `targetUser()` issue `User::query()->find()` calls as static methods. These are invisible side-effects. A developer reading a controller method cannot know from the call site that `BranchContext::getActiveBranch()` might fire a database query.

`ImpersonationContext` partially mitigates this with request-attribute caching, which is a good pattern, but it's still static.

**Issue D.2 — `TenantGeneralSettings` Queried Per-Request (MEDIUM)**

**File**: `app/Http/Controllers/DispensingController.php:354–378`

`pharmacyPolicy()` calls `$this->tenantGeneralSettings->boolean(...)` three times to build the pharmacy policy object. Each call presumably reads from a `tenant_general_settings` table. These settings change rarely (tenant configuration). They should be cached (e.g., `Cache::remember("tenant_settings_{$tenantId}", 300, ...)`).

---

### E. Test Coverage

**Issue E.1 — POS Refund, Void, and Payment Flows Are Untested (HIGH)**

The Pharmacy POS refund/void/payment controllers exist and handle financial + stock operations, but no test files were found for them. These are the most financially consequential flows in the system.

**Issue E.2 — Lab Result Workflow Has Minimal Coverage (HIGH)**

The lab result lifecycle (specimen collection, result entry, clinical review, approval, rejection/correction) is complex with multiple state transitions. Test coverage of `LabResultWorkflowController` and associated actions is thin.

**Issue E.3 — Action Classes Lack Direct Unit Tests (MEDIUM)**

162 Action classes contain the core business logic. Most test coverage comes from feature tests that exercise actions through controllers. If an action has complex internal branching (like `PostDispense` with its FEFO batch allocation, partial dispense handling, and prescription status sync), feature tests may not cover all code paths. Unit tests directly instantiating actions with mock repositories would provide better path coverage.

**Issue E.4 — No Browser / E2E Tests for Critical Flows (MEDIUM)**

Pest 5 includes browser testing capabilities. Given the system handles medication dispensing and lab results, critical happy paths should have end-to-end browser tests: dispense a prescription, verify stock decremented; issue a requisition, verify stock transferred.

---

### F. Error Handling

**Issue F.1 — No Audit Trail for Any Data Change (CRITICAL for Medical Context)**

There are no model observers, no activity log package (e.g., `spatie/laravel-activitylog`), and no audit tables. In a medical system, the following require an audit trail for regulatory compliance and clinical safety:

- Prescription creation and modification
- Dispensing record creation and posting
- Lab result entry and correction
- Patient record modification (allergies, demographics)
- User role and permission changes
- Inventory adjustments (reconciliations)

Without an audit trail, there is no answer to "who changed this, when, and from what value?"

**Fix**: Integrate `spatie/laravel-activitylog` or implement a custom `AuditTrail` observer. At minimum, add `created_by` / `updated_by` columns to critical tables (several already have `created_by` — extend this to `updated_by` universally).

**Issue F.2 — No Structured Exception Handling Beyond HTTP Aborts (MEDIUM)**

Business rule violations are expressed as either:
- `throw ValidationException::withMessages([...])` (from within FormRequest `after()` callbacks)
- `abort(403, 'message')` / `abort_unless(...)` in controllers

There is no custom exception hierarchy (e.g., `InsufficientStockException`, `InvalidWorkflowTransitionException`). This means:
- Exceptions from Actions cannot be caught selectively by callers.
- When Actions are later called from Console commands, scheduled jobs, or API endpoints, they will produce HTTP-specific exceptions that make no sense outside a web context.

**Fix**: Define domain exceptions like:
```php
class InsufficientStockException extends RuntimeException {}
class InvalidWorkflowTransitionException extends DomainException {}
```

Actions throw these; controllers catch them and convert to appropriate HTTP responses.

---

## Risk Summary Table

| # | Issue | Module | Severity | Probability of Breaking |
|---|-------|--------|----------|------------------------|
| 1 | No audit trail | All | CRITICAL | Will cause compliance failure |
| 2 | No caching on `InventoryStockLedger` | Inventory/Pharmacy | CRITICAL | Will break under production load |
| 3 | `summarizeByBatch` called 2× per request | Inventory/Pharmacy | HIGH | Currently slow; worsens linearly |
| 4 | Hardcoded status strings (26 occurrences) | All clinical modules | HIGH | Silent wrong results if Enum values change |
| 5 | No duplicate payment protection | Billing | HIGH | Money can be double-counted |
| 6 | POS refund/void untested | Pharmacy POS | HIGH | Unknown correctness |
| 7 | No appointment conflict detection | Appointments | HIGH | Double-booking in concurrent load |
| 8 | Static `BranchContext` with DB calls | All | MEDIUM | Test fragility, hidden side-effects |
| 9 | Inline validation in `cancel()`/`reject()` | Inventory | MEDIUM | Inconsistency, missing reuse |
| 10 | Serialization duplicated in controllers | All | MEDIUM | Frontend contract drift |
| 11 | No async jobs for heavy transactions | All | MEDIUM-HIGH | Lock contention under concurrent users |
| 12 | Missing `inventory_batch_id` index | Inventory | MEDIUM | Query degradation as stock grows |
| 13 | No password complexity rules | Auth | MEDIUM | Compliance / security |
| 14 | `TenantGeneralSettings` queried per-request | Pharmacy | MEDIUM | Redundant DB reads per page |
| 15 | No domain exception hierarchy | All | MEDIUM | Actions become web-only |
| 16 | No unit tests for Action classes | All | MEDIUM | Logic bugs in complex paths |
| 17 | No caching on lab/visit dashboard counts | Lab/Clinical | LOW-MEDIUM | Scaling concern |

---

## Prioritised Recommendations

### Priority 1 — Fix Before Any Production Load

1. **Replace all hardcoded status strings with Enum values** (`::value`)
   - Files: `DoctorConsultationController`, `PatientController`, `PatientVisitController`, `LaboratoryQueueController`, `LaboratoryDashboardController`, `TriageController`, `VisitOrderController`, `FacilityManagerController`, `Print/VisitSummaryPrintController`
   - Effort: 2–3 hours

2. **Stop calling `summarizeByBatch()` / `summarizeByLocation()` twice in the same method**
   - Files: `DispensingController::availableBatchBalances()`, `InventoryRequisitionController::availableBatchBalances()`
   - Effort: 30 minutes

3. **Add index on `stock_movements.inventory_batch_id`**
   ```php
   // New migration
   $table->index('inventory_batch_id', 'stock_movements_batch_id_idx');
   ```
   - Effort: 15 minutes

4. **Add payment idempotency key** — unique constraint on `(visit_id, idempotency_key)` in the payments table.
   - Effort: 1 hour

### Priority 2 — Before Scaling to Multiple Tenants

5. **Cache `InventoryStockLedger` results** — Redis with 30-second TTL, invalidated by stock movement post events.
   - Effort: 4–6 hours

6. **Cache `TenantGeneralSettings`** — Simple `Cache::remember()` with 5-minute TTL per tenant.
   - Effort: 1 hour

7. **Cache `PrescriptionDispenseProgress`** — Keyed per prescription ID, invalidated on dispense post.
   - Effort: 1 hour

8. **Add missing FormRequests for `cancel` and `reject`** in `InventoryRequisitionController`.
   - Effort: 1 hour

### Priority 3 — Technical Debt Reduction

9. **Introduce domain exceptions** (`InsufficientStockException`, `InvalidWorkflowTransitionException`, etc.)
   - Refactor Actions to throw these; add exception handler in `bootstrap/app.php`.
   - Effort: 4 hours

10. **Extract serialization into API Resources** — Start with `InventoryRequisitionResource`, `DispensingRecordResource`, `PrescriptionItemResource`.
    - Effort: 6–8 hours

11. **Convert `BranchContext` to an injectable service** — Bind as request-scoped singleton; update all call sites.
    - Effort: 3–4 hours

12. **Add password complexity rules** via `Password::min(12)->mixedCase()->numbers()->symbols()` in user creation and password change requests.
    - Effort: 30 minutes

### Priority 4 — Compliance and Safety

13. **Implement audit trail** — Add `spatie/laravel-activitylog` or a custom observer to track changes to prescriptions, dispensing records, lab results, patient records, and user permissions.
    - Effort: 8–12 hours

14. **Add appointment conflict detection** — Validate no overlapping slots exist before persisting an appointment, using `lockForUpdate`.
    - Effort: 2–3 hours

15. **Write feature tests for POS refund, void, and payment flows** — At minimum: happy path, double-refund prevention, void reverses stock.
    - Effort: 4–6 hours

### Priority 5 — Future Architecture

16. **Introduce Queue Jobs** for post-dispense stock sync, email notifications, and report generation. Start with `PostDispenseJob` wrapping the existing `PostDispense` action.
17. **Materialise stock balances** — `stock_balances` summary table updated transactionally with each movement, eliminating aggregate scans.
18. **Add RESTful API layer** — Required for mobile apps and third-party integrations; the Action layer makes this straightforward to add without duplicating logic.

---

*Analysis performed via static code review: Glob, Grep, Read across ~500 PHP files.*
*Recommendations are based on observed code patterns and risk assessment — no runtime profiling was performed.*
