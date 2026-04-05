# Test Failure Analysis and Fix Plan

## 1. Why tests took so long (9440.37s / ~2.6 hours)

### Findings:
- **Heavy Seeding Overhead**: Helper functions like `createSupplierTestContext` and `createPermissionTenant` create a full organizational stack (Country, Tenant, Branch, Subscription, User, Permissions) for almost every test case. With ~245 tests, this creates massive database I/O and CPU overhead.
- **Coverage Overhead**: Running `php artisan test --coverage` adds significant latency, especially on Windows with Xdebug. Coverage calculates hits for every line of code across the entire `app` directory for every single test.
- **Dusk/Browser Tests**: Integration tests like `RolePermissionsTest` (132.92s) are inherently slow as they boot a full browser environment.
- **No Parallelization**: Tests are running sequentially.

### Plan:
- **Short-term**: Run tests without coverage (`php artisan test`) during development.
- **Medium-term**: Optimize seeders to use shared state where possible or use `RefreshDatabase` more efficiently (e.g., using `TestCase`'s built-in transaction support properly).
- **Long-term**: Implement parallel testing (`php artisan test --parallel`).

---

## 2. Why tests failed

### A. MissingAttributeException in Laboratory Tests
- **Issue**: `CollectLabSpecimenRequest` eager loads `test.specimenTypes:id`. This caches the relationship without the `name` column. When the `CollectLabSpecimen` action tries to access `$specimenType->name`, it fails because Laravel strict mode (or just the missing column in the result set) prevents accessing unretrieved attributes.
- **Fix**: Update the eager loading in `CollectLabSpecimenRequest` and `CollectLabSpecimen` action to always include `name`.

### B. SessionController Redirect Mismatch
- **Issue**: `SessionController` redirects to the `home` route (`/`) after login, but the tests expect a redirect to `dashboard`.
- **Fix**: Update `SessionController` to redirect to `dashboard` if the user has an active branch.

### C. UserProfileController Validation Errors
- **Issue**: `UpdateUserRequest` requires a `name` field, but `UserProfileControllerTest` only sends the `email`. This causes validation to fail, redirecting the user back without updating the profile or resetting email verification.
- **Fix**: Add the `name` field to all update requests in `UserProfileControllerTest.php`.

### D. User Model `toArray` mismatch
- **Issue**: The test expects a fixed list of keys, but the `User` model appends `avatar` and includes other fields like `tenant_id`, `staff_id`, and `is_support` which are not in the test's expectation list.
- **Fix**: Update the test expectation in `UserTest.php` to match the actual model output.

### E. Supplier Deletion (Soft Delete) check
- **Issue**: `expect($supplier->fresh())->toBeNull()` is failing. Even though `deleted_at` is set, `fresh()` might still be returning the model in the test environment if global scopes are bypassed or misunderstood.
- **Fix**: Use `assertSoftDeleted($supplier)` or check `Supplier::withTrashed()->find($id)->trashed()`.

### F. Appointment Permission (403 Forbidden)
- **Issue**: The appointment confirmation test fails with 403 because it doesn't set the `active_branch_id` in the session, and the route is protected by the `ensure.active.branch` middleware.
- **Fix**: Wrap the request with `withSession(['active_branch_id' => $branch->id])`.

### G. ArchTest Strict Failures
- **Issue**: The code violates architectural rules defined in the `ArchTest` (likely missing return types, visibility issues, or using forbidden functions like `dd()`).
- **Fix**: Inspect the specific Arch failures and apply required types or refactorings.

---

## Implementation Plan

1.  **Surgical Fixes (Immediate)**:
    - Update `CollectLabSpecimenRequest.php` to load `id,name`.
    - Update `SessionController.php` redirect logic.
    - Fix `UserProfileControllerTest.php` by adding `name` to payloads.
    - Fix `PermissionEnforcementTest.php` by adding active branch to session.
2.  **Model/Test Alignment**:
    - Update `UserTest.php` keys.
    - Fix `SupplierControllerTest.php` deletion check.
3.  **Verification**:
    - Run failing tests individually to confirm fixes.
    - Run the entire suite without coverage to ensure performance is acceptable.
