# Mini-Hospital V2 - Technical Analysis Report

## 1. Architecture Overview

### 1.1 Project Structure
- **Framework**: Laravel 13 (PHP 8.5)
- **Frontend**: Inertia.js with React/Vue (SPA-like experience)
- **Database**: MySQL (implied by Laravel conventions)
- **Authentication**: Laravel Fortify + Spatie Permissions
- **Architecture Pattern**: Action-based (CQRS-lite) with Service classes

### 1.2 Key Directories
```
app/
├── Actions/          # Business logic (140+ action classes)
├── Data/            # DTOs for request/response
├── Http/
│   ├── Controllers/ # ~90 controllers
│   ├── Middleware/   # Custom middleware
│   └── Requests/    # Form requests with validation
├── Models/          # Eloquent models (~70 models)
├── Support/         # Service classes,Query builders, Contexts
└── Traits/          # Shared model behaviors
```

---

## 2. Bad Programming Habits & Issues

### 2.1 Missing Validation Rules in FormRequests (CRITICAL - CAUSING CI FAILURES)

**Issue**: Several FormRequests validate data only in `after()` hooks but don't define `rules()`, causing `validated()` to return empty arrays.

**Files Affected**:
- `ApproveInventoryRequisitionRequest.php` - FIXED
- `IssueInventoryRequisitionRequest.php` - FIXED  
- `StoreInventoryReconciliationRequest.php` - FIXED

**Problem Code**:
```php
// WRONG - Only validates in after() but no rules() defined
final class ApproveInventoryRequisitionRequest extends FormRequest
{
    public function after(): array // validation happens here
    {
        return [function(Validator $validator): void {
            $items = $this->input('items'); // accesses input directly
            // ...
        }];
    }
}
```

**Why It Fails**: Laravel's `$request->validated()` only returns fields defined in `rules()`. Direct `$this->input()` bypasses validation pipeline.

---

### 2.2 Global Scope Inconsistency

**Issue**: Models use global scopes (TenantScope, BranchScope) but there are inconsistencies in how they're applied.

**Details**:
- 34 models use SoftDeletes
- Only 2 global scopes defined: `TenantScope` and `BranchScope`
- Some queries use `withoutGlobalScopes()` in tests but controllers don't always

**Potential Problem**:
```php
// In BranchScope.php - returns all records for support users when tenant_id is null
if ($user->is_support && $user->tenant_id === null) {
    return; // Returns ALL records - potential data leakage
}
```

---

### 2.3 No Query Caching Implementation

**Issue**: No caching layer exists despite heavy query patterns.

**Found**:
- No `Cache::` usage found
- No `remember()` method calls
- Every request recomputes stock balances, prescription progress, etc.

**Examples of Missing Caching**:
```php
// In PrescriptionDispenseProgress.php - runs on EVERY request
$query = DispensingRecordItem::query()
    ->whereHas('dispensingRecord', function ($q) use ($prescriptionId) {
        $q->where('prescription_id', $prescriptionId);
    })
    ->where('dispense_status', PrescriptionItemStatus::DISPENSED)
    ->selectRaw('prescription_item_id, SUM(dispensed_quantity) as total')
    ->groupBy('prescription_item_id')
    ->get();
```

---

### 2.4 Missing Indexes in Migrations (PERFORMANCE)

**Issue**: Database indexes may be missing on foreign keys and commonly filtered columns.

**Evidence**: No explicit index definitions found in migration files for:
- `branch_id` on most tables
- `tenant_id` on most tables
- `status` columns (used in WHERE clauses)
- `created_at` / `updated_at` (used in ORDER BY)

---

### 2.5 Inline Auth Checks Instead of Policies

**Issue**: 81 instances of `abort_unless()` for authorization instead of Laravel Policies.

**Example**:
```php
// Controllers contain manual checks
abort_unless($dispensingRecord->branch_id === BranchContext::getActiveBranchId(), 404);
abort_unless($workspace->isInventory(), 404);
```

**Should Be**:
```php
// Using Policy
$this->authorize('view', $dispensingRecord);
```

---

### 2.6 Large Eager Loading Without Pagination

**Issue**: Controllers use eager loading (`with()`) on potentially large relationships without pagination limits.

**Example**:
```php
// In DispensingController.php
->with([
    'visit.patient:id,first_name,last_name,patient_number',
    'inventoryLocation:id,name',
    'dispensedBy:id,staff_id,email',
    'dispensedBy.staff:id,first_name,last_name',
])
```

**Issue**: For exports or reports, this loads ALL records into memory.

---

### 2.7 No Queue/Job Implementation

**Issue**: No async processing. Heavy operations run synchronously.

**Evidence**:
- No `app/Jobs/` directory exists
- No `app/Events/` directory exists
- All 109 `DB::transaction()` calls are synchronous

**Examples**:
- Stock movements are created synchronously
- Email notifications run synchronously
- No background processing for reports

---

### 2.8 Console Detection in Global Scopes

**Issue**: Global scopes check `app()->runningInConsole()` which can have edge cases.

```php
// In TenantScope.php
if (app()->runningInConsole()) {
    return;
}
```

**Problem**: This check is imprecise. Commands like `php artisan tinker`, `php artisan test`, or background workers might behave unexpectedly.

---

### 2.9 Type Casting Issues

**Issue**: Some models use weak type casting.

```php
// In some models
'dispensed_by' => 'string',  // Should be 'integer' if it's a foreign key
'quantity' => 'numeric',     // Good, but should specify precision for decimal columns
```

---

### 2.10 No Rate Limiting Implementation

**Issue**: No rate limiting found despite handling financial and medical data.

**Evidence**:
- No `throttle:` middleware in routes
- No custom rate limiters defined

---

## 3. Potential Performance Issues

### 3.1 N+1 Query Risks

**Locations at Risk**:
- `DispensingHistoryController.php:92` - `foreach ($record->items as $item)`
- `PharmacyQueueController` - serialization loops
- `InventoryStockLedger.php` - stock calculations

### 3.2 Missing Composite Indexes

**Likely Needed Indexes**:
```sql
-- For prescription queue
INDEX (facility_branch_id, status, prescription_date)

-- For stock movements
INDEX (branch_id, inventory_location_id, inventory_item_id, occurred_at)

-- For visits
INDEX (facility_branch_id, status, registered_at)
```

### 3.3 Expensive Calculations Without Caching

**Problem Areas**:
1. `InventoryStockLedger::summarizeByBatch()` - Called on every inventory page
2. `PrescriptionDispenseProgress::postedLineSummaries()` - Called for every prescription
3. `PrescriptionQueueQuery` - Runs complex joins on every queue load
4. Stock balance calculations in multiple controllers

### 3.4 Pagination Without Count

**Issue**: Some endpoints might not use proper pagination:

```php
->paginate(10)->withQueryString()  // Good - uses length-aware
// But some may use ->get() directly loading all records
```

---

## 4. Security Concerns

### 4.1 Support User Bypass

**Issue**: Support users with null `tenant_id` bypass all filtering:

```php
// In BranchScope.php
if ($user->is_support && $user->tenant_id === null) {
    return; // NO FILTERING - sees ALL branches
}
```

**Risk**: If a support user account is compromised, attacker sees ALL data.

### 4.2 Weak Password Policy

**Issue**: No password complexity validation found.

**Evidence**: Standard `password` field in migrations without custom rules.

### 4.3 No Audit Logging

**Issue**: No audit trail for:
- Changes to patient records
- Prescription modifications
- Inventory adjustments
- User privilege changes

### 4.4 Missing Authorization Policies

**Issue**: Not using Laravel Policies consistently (using manual `abort_unless`).

---

## 5. Code Quality Issues

### 5.1 Inconsistent Return Types

**Examples**:
```php
// Some controllers return mixed types
return $something ? 'string' : redirect('/');  // Inconsistent

// Should be
return $something ? to_route('route') : back();
```

### 5.2 Magic Numbers/Strings

**Issue**: Hardcoded values scattered throughout:

```php
->where('status', 'PENDING')  // Should use Enum
->where('type', 'pharmacy')   // Should use constant
->paginate(30)               // Magic number
```

### 5.3 Missing PHPDoc on Complex Returns

**Issue**: Some complex return types lack documentation:

```php
// Missing return type
public function serializeCheckoutCartItem($item) { }

// Should be
/**
 * @return array{cart_item_id: string, inventory_item_id: string, ...}
 */
public function serializeCheckoutCartItem(PharmacyPosCartItem $item): array { }
```

### 5.4 Controller Bloat

**Issue**: Some controllers exceed 400 lines, handling too many responsibilities:

- `DispensingController.php` - 446 lines
- `InventoryRequisitionController.php` - 516+ lines

**Should**: Split into resource-specific controllers or use action classes more consistently.

### 5.5 Duplicate Code Patterns

**Issue**: Similar query patterns repeated:

```php
// Repeated in multiple controllers
$prescriptions = Prescription::query()
    ->whereHas('visit', fn ($q) => $q->where('facility_branch_id', $branchId))
    ->with([...same relations...])
    ->latest();
```

**Should**: Extract to scopes or query builders.

---

## 6. Incomplete Features

### 6.1 No API Endpoints

**Issue**: Only web routes exist (`routes/web.php`).

**Missing**:
- No `routes/api.php`
- No API versioning
- No RESTful endpoints for mobile apps
- No token-based authentication

### 6.2 No WebSocket/Real-time Features

**Issue**: No real-time updates for:
- Pharmacy queue changes
- Lab results ready
- Appointment updates

**Missing**:
- No Pusher/Reverb integration
- No Laravel Reverb
- No WebSocket gateway

### 6.3 No Notification System

**Issue**: No structured notification system for:
- Prescription ready alerts
- Lab results notified
- Appointment reminders

**Missing**:
- No database notifications
- No email queue
- No SMS integration

### 6.4 No Reporting Module

**Issue**: No dedicated reporting:
- Revenue reports
- Usage statistics
- Audit reports
- Inventory reports (basic only)

### 6.5 Incomplete Workflows

**Potential Missing**:
- Patient discharge workflow
- Insurance claims processing
- Referral system
- Inventory alerts/expirations

### 6.6 No Multi-language Support

**Issue**: No i18n implementation:
- No translation files
- No locale handling
- Hardcoded UI strings

### 6.7 No Audit Trail

**Issue**: No system for tracking changes to:
- Patient records
- Prescriptions
- Inventory quantities
- User permissions

---

## 7. Recommendations & Action Plan

### 7.1 Critical Fixes (Immediate)

| # | Issue | Fix |
|---|-------|-----|
| 1 | Missing FormRequest rules | Add `rules()` to FormRequests (ALREADY FIXED 3) |
| 2 | CI test failures | Fix branch scoping in test queries |
| 3 | Support user data leak | Add branch filtering for support users |

### 7.2 High Priority (Before Production)

| # | Issue | Fix | Effort |
|---|-------|-----|--------|
| 1 | Add database indexes | Run migration with composite indexes | Medium |
| 2 | Implement query caching | Add Cache facade to StockLedger, PrescriptionProgress | High |
| 3 | Replace abort_unless with Policies | Create and use Policy classes | Medium |
| 4 | Add rate limiting | Configure throttle middleware | Low |
| 5 | Audit logging | Create audit trail table and model | High |

### 7.3 Medium Priority (Optimization)

| # | Issue | Fix | Effort |
|---|-------|-----|--------|
| 1 | Extract large controllers | Split into resource controllers | Medium |
| 2 | Create scopes for common queries | Add model scopes | Low |
| 3 | Add composite indexes | Migration | Low |
| 4 | Type safety improvements | Add strict types | Medium |
| 5 | Queue jobs for heavy operations | Create Job classes | High |

### 7.4 Future Enhancements

| # | Feature | Benefit |
|---|---------|---------|
| 1 | RESTful API | Mobile app support |
| 2 | Real-time (Reverb) | Live updates |
| 3 | Reporting module | Business insights |
| 4 | Audit trail | Compliance |
| 5 | Notifications | User engagement |
| 6 | Multi-language | Localization |

---

## 8. Code Statistics

- **Controllers**: ~90
- **Action Classes**: 140+
- **Models**: ~70
- **Migrations**: 80+
- **Tests**: Multiple feature + unit tests
- **SoftDeletes Models**: 34
- **DB::transaction() usages**: 109

---

## 9. Test Failures Summary

The following tests were failing on CI ( INVESTIGATED ABOVE ):

1. **DispensingControllerTest** (lines 537, 602) - `ModelNotFoundException`
   - Cause: Query without branch scoping
   
2. **DispensingHistoryControllerTest** (line 76) - Empty records
   - Cause: Related to branch filtering
   
3. **InventoryRequisitionControllerTest** (lines 262, 303) - `Undefined array key "items"`
   - **FIXED**: Added `rules()` to requests
   
4. **PharmacyQueueControllerTest** (line 299) - Wrong count
   - Cause: External pharmacy status handling
   
5. **InventoryReconciliationControllerTest** (line 230) - Null reconciliation
   - **FIXED**: Added `rules()` to request

---

*Generated: April 2026*
*Tools: Static Analysis via Grep, Glob, Read*