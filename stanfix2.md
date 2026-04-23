# PHPStan Fix Plan 2

This file groups the current errors from [stan2.md](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\stan2.md) by root cause instead of by raw line order.

The main pattern in this batch is not one big architectural break. It is mostly:

- loose controller-to-action payloads
- request normalization methods that still return `mixed`
- collection callbacks and relation closures that are too loosely typed
- `HasFactory` generic noise on models
- redundant nullsafe / `??` / `is_array()` checks after types have already been narrowed

Because this project now uses DTOs, the preferred fix is:

1. validate and normalize in the request
2. convert to a DTO in the request boundary where the payload is business-shaped
3. let controllers pass DTOs, not loose arrays
4. let actions consume typed DTOs or explicitly-shaped arrays only where a DTO is not worth the ceremony

## Group 1: Mixed Model/Collection Access In Controllers

Representative errors:

- mixed property access in a prescription/dispensing serialization block near the top of `stan2.md`
- `app/Http/Controllers/InventoryStockByLocationController.php`
- `app/Http/Controllers/LaboratoryDashboardController.php`
- `app/Http/Controllers/LaboratoryQueueController.php`
- `app/Http/Controllers/HandleInertiaRequests.php`

Root cause:

- collections or query results are treated as if every element has a known object shape, but PHPStan only sees `mixed`
- raw aggregate rows like `SUM(...) as total_qty` are treated like full Eloquent models
- relation closures return a builder object where Laravel expects a mutating `void` closure

Fix approach:

- add explicit collection element shapes before iterating or mapping
- for aggregate queries, annotate the returned row shape as `object{...}` or convert to typed arrays before use
- change `static fn (...) => $query->with(...)` relation closures into block closures that mutate and return `void`
- add builder generics like `Builder<LabRequestItem>` where helper methods return builders

Priority:

- high, because these errors often hide real data-shape ambiguity in UI serialization

## Group 2: Controller To Action Payload Mismatch

Representative files:

- `app/Http/Controllers/PurchaseOrderController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/UserProfileController.php`
- `app/Http/Controllers/VisitPaymentController.php`
- `app/Http/Controllers/WorkspaceRegistrationController.php`
- `app/Http/Controllers/LabRequestItemConsumableController.php`
- `app/Http/Controllers/LabTestCatalogController.php`

Root cause:

- controllers are still passing `validated()` arrays into actions whose signatures now expect narrower array shapes
- in some places the action should really accept a DTO now that the payload is business-level and reused

Fix approach:

- where the payload is complex or reused, add a DTO and expose `dto()` / `createDto()` / `updateDto()` on the request
- where the payload is simple, keep the array but add an exact local `@var array{...}` shape before calling the action
- for role arrays and line-item arrays, make sure the request normalization guarantees `list<string>` or `list<array{...}>`

DTO-first targets from this batch:

- purchase order create/update
- visit payment
- workspace registration
- lab request item consumable
- lab test catalog create/update if we want to formalize the catalog payload fully

Priority:

- very high, because this is the cleanest way to reduce both PHPStan noise and controller complexity

Implemented in this pass:

- purchase order create/update now flow through `CreatePurchaseOrderDTO` and `UpdatePurchaseOrderDTO`
- managed user create/update now flow through `CreateUserDTO` and `UpdateUserDTO`
- visit payments now use `StoreVisitPaymentRequest` and `CreateVisitPaymentDTO`
- workspace registration now uses `CreateWorkspaceRegistrationDTO`
- password-bearing requests now expose typed `password()` helpers so controllers no longer pull raw strings directly

Touched files:

- [app/Http/Controllers/PurchaseOrderController.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\PurchaseOrderController.php)
- [app/Http/Controllers/UserController.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\UserController.php)
- [app/Http/Controllers/UserProfileController.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\UserProfileController.php)
- [app/Http/Controllers/VisitPaymentController.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\VisitPaymentController.php)
- [app/Http/Controllers/WorkspaceRegistrationController.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\WorkspaceRegistrationController.php)
- [app/Actions/CreatePurchaseOrder.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\CreatePurchaseOrder.php)
- [app/Actions/UpdatePurchaseOrder.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\UpdatePurchaseOrder.php)
- [app/Actions/CreateUser.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\CreateUser.php)
- [app/Actions/UpdateUser.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\UpdateUser.php)
- [app/Actions/RecordVisitPayment.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\RecordVisitPayment.php)
- [app/Actions/RegisterWorkspace.php](C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\RegisterWorkspace.php)

Verification:

- focused feature tests passed for managed user creation, user profile update, and purchase order creation
- focused unit/data tests passed for the new visit-payment and workspace-registration DTO/action coverage
- focused PHPStan passed with `0` errors on the changed application files

## Group 3: Request Normalization Still Returns Mixed

Representative files:

- `app/Http/Requests/DispensePrescriptionRequest.php`
- `app/Http/Requests/StoreDispenseRequest.php`
- `app/Http/Requests/StoreLabResultEntryRequest.php`
- `app/Http/Requests/StoreLabTestCatalogRequest.php`
- `app/Http/Requests/UpdateLabTestCatalogRequest.php`
- `app/Http/Requests/StoreInventoryItemRequest.php`
- `app/Http/Requests/UpdateInventoryItemRequest.php`
- `app/Http/Requests/StoreInsurancePackageRequest.php`
- `app/Http/Requests/UpdateInsurancePackageRequest.php`
- `app/Http/Requests/StorePatientRequest.php`
- `app/Http/Requests/UpdateConsultationRequest.php`

Root cause:

- `input()` values are still treated as `mixed`
- helper methods like `numericOrDefault()`, `selectedResultTypeCode()`, `filledResultOptions()`, and `normalizedItems()` promise typed results but build them from unresolved `mixed`
- some request array shapes are already narrowed, but the code still uses `??`, `is_array()`, and string casts as if they were not

Fix approach:

- normalize once in `prepareForValidation()` into a stable shape
- add exact array-shape PHPDoc at the point where `validated()` is read
- remove `??` checks for required offsets that already always exist
- replace `collect($mixed)` with `collect($knownArray)` only after guarding `is_array()`
- where the payload is large and business-significant, return a DTO from the request and move the detailed mapping there

DTO-specific note:

- `DispensePrescriptionRequest` and related pharmacy requests should stay on the DTO path already established in the app
- lab test catalog requests are a good candidate for `CreateLabTestCatalogDTO` and `UpdateLabTestCatalogDTO` if we want to eliminate most of these request errors at once

Priority:

- very high, because these are the noisiest and most repetitive errors in the file

## Group 4: Redundant Guards After Type Narrowing

Representative files:

- `app/Http/Controllers/VisitOrderController.php`
- `app/Http/Requests/CorrectLabResultEntryRequest.php`
- `app/Http/Requests/StoreConsultationPrescriptionRequest.php`
- `app/Http/Requests/UpdateAppointmentCategoryRequest.php`
- `app/Http/Requests/UpdateAppointmentModeRequest.php`
- many request helper methods in `DispensePrescriptionRequest`

Root cause:

- PHPStan already knows a value is present or non-null from the declared shape
- code still uses nullsafe access, `??`, `is_array()`, or string checks anyway

Fix approach:

- remove redundant `??` on required offsets
- replace `?->` with `->` once the relation/model is already guaranteed
- delete dead branches after a strict shape or enum narrowing
- if a method result is truly impure and PHPStan is caching it too aggressively, add `@phpstan-impure` to the helper instead of weakening the logic everywhere

Priority:

- medium

## Group 5: Route Model And Request User Narrowing Problems

Representative files:

- `app/Http/Requests/UpdateStaffRequest.php`
- `app/Http/Requests/UpdateUnitRequest.php`
- `app/Http/Controllers/UserController.php`
- `app/Http\Controllers/LaboratoryWorklistController.php`

Root cause:

- route values are read as `object|string|null`
- `user()` / route model lookups are used before narrowing to the expected model class

Fix approach:

- assign route values to a local variable and assert or guard the model class once
- use `assert($model instanceof ModelClass)` or explicit `if (! $model instanceof ...)`
- after narrowing, use the concrete model properties instead of concatenating `mixed`

Priority:

- medium

## Group 6: Subscription Activation And Enum/Meta Payload Confusion

Representative file:

- `app/Http/Controllers/SubscriptionActivationController.php`

Root cause:

- meta fields are treated as `array|string`
- enum/state values are treated as objects in one place and strings in another
- nullable package lookups are used without a guard

Fix approach:

- normalize meta to `array<string, mixed>` before unpacking
- narrow nullable package lookup results once, early
- keep enum values as enums until the final display serialization step
- if the controller is building a repeated subscription summary payload, consider a small DTO/view model to keep status serialization typed

Priority:

- medium-high

## Group 7: Inertia Shared Props And Middleware Typing

Representative file:

- `app/Http/Middleware/HandleInertiaRequests.php`

Root cause:

- shared prop closures return `mixed`
- relation `with()` closures are typed as returning relations instead of mutating them
- `mapWithKeys()` callbacks are narrower than the source collection PHPStan sees

Fix approach:

- annotate shared prop closures with their real return types like `string|null`
- use block closures for relation eager loads
- convert permission collections to `Collection<int, string>` or `list<string>` before `mapWithKeys()`

Priority:

- medium

## Group 8: HasFactory Generic Noise On Models

Representative files:

- `app/Models/Allergen.php`
- `app/Models/Department.php`
- many models now showing `Class ... uses generic trait HasFactory but does not specify its types`

Root cause:

- some models still have old `@use HasFactory<Database\Factories\...Factory>` docs for missing/non-generic factories
- other models use `HasFactory` with no generic annotation at all
- Rector and the codebase currently disagree on how aggressively `HasFactory` should be applied

Fix approach:

- choose one consistent policy and apply it app-wide:

Option A:
- keep `HasFactory` on models
- where a real factory exists, use `/** @use HasFactory<Factory<self>> */`
- where the old concrete factory PHPDoc is invalid, replace it with `Factory<self>` or remove the bad generic

Option B:
- if a model truly should not participate in factories, remove `HasFactory`

Practical recommendation for this repo:

- do not keep the old `Database\Factories\ConcreteFactory` generic form
- prefer `Factory<self>` when the trait stays
- keep the narrow PHPStan ignores only for legacy leftovers while we finish normalizing models

Priority:

- medium, but noisy

## Suggested Fix Order

1. Controller-to-action payload mismatches
2. Request normalization and DTO boundaries
3. Inertia/controller relation closure typing
4. Redundant nullsafe / dead-branch cleanup
5. Route model narrowing
6. Subscription activation typing
7. HasFactory model normalization

## Fast Wins

- add DTO/request boundaries for:
  - purchase orders
  - visit payments
  - workspace registration
  - lab request item consumables
- normalize `StoreLabTestCatalogRequest` and `UpdateLabTestCatalogRequest` helper methods so `collect()` only receives typed arrays
- fix `HandleInertiaRequests` closure return types and relation closures
- normalize model `HasFactory` docs to one convention

## Done Definition For This Round

A grouped batch should be considered done when:

- the relevant files pass focused `phpstan analyse`
- request payloads are either:
  - converted to DTOs, or
  - narrowed with exact array-shape docs before action calls
- redundant `nullsafe`, `??`, and `is_array()` checks are removed where the shape already guarantees the data
- changed PHP files pass `vendor/bin/pint --format agent`
