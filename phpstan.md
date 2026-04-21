# PHPStan Error Review

**Date:** April 21, 2026
**Purpose:** explain the current `phpstan` output in plain language, group the errors by root cause, and show which issues are the same as the pharmacy POS problems and which are different.

## Short Answer

Yes, a lot of these are the **same underlying problem family** as the pharmacy POS issues, but not all of them.

The biggest repeating theme is:

- `phpstan` no longer knows the **concrete type** of the thing being used
- so it falls back to `mixed`, `object`, or generic `Model`
- then every property or method access after that starts failing

That is what drives many of these:

- `property.nonObject`
- `method.nonObject`
- `property.notFound`
- `argument.unresolvableType`
- `method.unresolvableReturnType`

But there are also other categories in your dump that are **not the same problem**:

- `cast.double` / `cast.string`
- `missingType.iterableValue`
- `argument.type`
- `nullsafe.neverNull`
- `deadCode.unreachable`
- `booleanOr.alwaysTrue`

## Main Error Families

## 1. Lost Concrete Type

This is the most common issue in your output.

Typical symptoms:

- `Cannot access property ... on mixed`
- `Cannot call method ... on mixed`
- `Access to an undefined property Illuminate\Database\Eloquent\Model::$...`

What it means:

- the runtime object is probably fine
- but `phpstan` cannot prove what class it is
- so it treats it as `mixed`, `object`, or base `Model`

### Example: pharmacy / dispensing style code

Errors like:

- `Cannot access property $inventoryItem on mixed`
- `Cannot access property $brand_name on mixed`
- `Cannot access property $status on mixed`
- `Cannot call method label() on mixed`

usually come from one of these situations:

1. iterating a collection whose item type is not known
2. reading nested relations without strong model typing
3. using `collect()` or `map()` on values that are not typed precisely enough

### Example: print controllers

In [DispensingRecordPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/DispensingRecordPrintController.php), `phpstan` complains about:

- `dispensedBy->staff`
- staff first/last name
- `dispensedBy->email`
- `visit->patient`

That is the same pattern: runtime relation is probably valid, but static analysis sees generic `Model` or `mixed`.

### Why this happens

Common causes:

- relation methods lack generics
- nested relation loads are not reflected in static type info
- collection callbacks do not tell `phpstan` what each item is
- variables produced by `collect()`, `map()`, `groupBy()`, `pluck()`, or `through()` become too loose

### What fixes it

1. Add relation generics to Eloquent model methods.
2. Use concrete callback parameter types when mapping collections.
3. Move nested relation reads into well-typed locals.
4. Add array-shape or collection-shape docblocks when building transformed data.
5. Avoid leaving `collect($unknown)` or `map(fn ($x) => ...)` completely untyped.

## 2. Mixed Casts

This is related, but not identical.

Typical symptoms:

- `Cannot cast mixed to float`
- `Cannot cast mixed to string`

Examples in your output:

- [PurchaseOrderController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/PurchaseOrderController.php)
- [ApproveInventoryRequisitionRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/ApproveInventoryRequisitionRequest.php)
- [PostDispenseRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/PostDispenseRequest.php)
- [StoreVitalSignRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreVitalSignRequest.php)

What it means:

- you are casting a value
- but `phpstan` still sees the source as `mixed`

This is very often caused by:

- raw validated arrays being passed around without array-shape docs
- offsets read from `mixed`
- generic model properties being cast without narrowing first

### Relationship to the pharmacy POS issue

This is the same **family**, but one step later.

The flow is usually:

1. `phpstan` loses the source type
2. the source becomes `mixed`
3. then casts from that source also fail

So yes, it is strongly related to the pharmacy POS `mixed` to `float` problem.

### What fixes it

1. Add shaped-array docs to validated payloads.
2. Normalize values into typed locals immediately.
3. Narrow model types before reading numeric properties.
4. Avoid performing calculations directly on raw request offsets.

## 3. Undefined Property On Generic Model

Typical symptom:

- `Access to an undefined property Illuminate\Database\Eloquent\Model::$generic_name`

This is not saying the property truly does not exist at runtime. It usually means:

- `phpstan` thinks the variable is only `Model`
- base `Model` does not declare `generic_name`
- so every custom field looks invalid

### Example

In [InventoryRequisitionPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/InventoryRequisitionPrintController.php), errors around batch fields happen for the same reason:

- `inventoryBatch` is not known strongly enough
- then `batch_number` and `expiry_date` look invalid

### What fixes it

1. Make relation return types explicit with generics.
2. Ensure mapped items are concrete models like `StockMovement`, `InventoryBatch`, `DispensingRecordItem`.
3. Use local variables after null checks if nested relations are involved.

## 4. Unresolvable Callback / Collection Return Types

Typical symptoms:

- `argument.unresolvableType`
- `method.unresolvableReturnType`

These show up a lot around:

- `map()`
- `sortBy()`
- `values()`
- `all()`
- grouped collections

What it means:

- `phpstan` cannot infer the type flowing through the collection pipeline
- once that happens, downstream transformed arrays also become fuzzy

### Example

In [InventoryRequisitionPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/InventoryRequisitionPrintController.php), the grouped stock movements are transformed with nested `map()` calls. Runtime is fine, but `phpstan` loses the exact value type inside the grouped collection chain.

### What fixes it

1. Add collection generics in PHPDoc.
2. Break complex chains into named locals with documented types.
3. Prefer a few short typed transformations over one long chain.

## 5. Nullsafe Used Where Type Is Already Non-Nullable

Typical symptoms:

- `Using nullsafe property access "?->value" on left side of ?? is unnecessary. Use -> instead.`
- `Using nullsafe method call on non-nullable type ...`

This is not the same problem as `mixed`.

It means:

- the code is being defensive
- but the current inferred type says the value is already non-null

This often appears after enum casts or certain relation assumptions.

### Example

If `phpstan` knows `dosage_form` is an enum, then:

```php
$item->inventoryItem?->dosage_form?->value ?? $item->inventoryItem?->dosage_form
```

may produce warnings because one side is considered definitely present.

### What fixes it

1. Remove unnecessary `?->` when the value is genuinely guaranteed.
2. Or, if null is actually possible, make the type declaration reflect that correctly.

## 6. Wrong Or Missing Array Types

Typical symptoms:

- `missingType.iterableValue`
- `Parameter ... expects array<int, string>, mixed given`
- `Argument of an invalid type mixed supplied for foreach`
- `Cannot access offset ... on mixed`

This is a different but very common issue in your request classes and controllers.

Examples:

- [RoleController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/RoleController.php)
- [StoreConsultationPrescriptionRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreConsultationPrescriptionRequest.php)
- [WorkspaceRegistrationController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/WorkspaceRegistrationController.php)

What it means:

- arrays are being accepted, returned, or passed around without saying what is inside them

### What fixes it

1. Add return types like `array<string, mixed>` or more precise array shapes.
2. For request `rules()`, return a typed rules array consistently.
3. For validated payloads, use array-shape docs before handing data to actions.

## 7. Logic Warnings, Not Type-Loss Warnings

Typical symptoms:

- `deadCode.unreachable`
- `booleanOr.alwaysTrue`
- `notIdentical.alwaysTrue`
- `nullCoalesce.expr`

These are not the same as the pharmacy POS `mixed` issue.

They mean:

- `phpstan` believes the condition will always behave one way
- some code path appears redundant or impossible

### Example

In [VisitOrderController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/VisitOrderController.php), a status comparison is flagged as always true because the compared types do not line up with the enum type actually inferred.

### What fixes it

1. Compare enum to enum, not enum to string.
2. Remove redundant `??` where left side is already non-null.
3. Simplify branches after type cleanup.

## File-by-File Meaning Of The Errors You Pasted

## A. Print Controllers

### [DispensingRecordPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/DispensingRecordPrintController.php)

Mostly the same family as the pharmacy POS issue:

- generic relation typing lost
- nested relation access becomes `mixed` or `Model`

Notable fixes:

- strengthen `DispensingRecord` relation typing
- narrow `dispensedBy`, `staff`, `visit`, and `patient` to typed locals

### [InventoryRequisitionPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/InventoryRequisitionPrintController.php)

Same family, plus grouped collection typing issues:

- nested `map()` on grouped collections is not typed strongly enough
- `inventoryBatch` becomes too generic

### [LabResultPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/LabResultPrintController.php)

Mostly same family:

- generic relation typing on `request`, `visit`, `patient`, `test`
- some nullsafe warnings are separate cleanup issues

### [VisitPaymentPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/VisitPaymentPrintController.php)

Same family:

- relation graph not typed strongly enough

## B. Controllers

### [PurchaseOrderController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/PurchaseOrderController.php)

This is closer to the pharmacy POS `mixed`-to-float problem:

- raw validated payload not narrowed enough
- item list probably arrives as `mixed`

### [RoleController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/RoleController.php)

Not mainly a model-typing problem. This is more:

- action expects `array<int, string>`
- controller is passing something still inferred as `mixed`

### [SubscriptionActivationController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/SubscriptionActivationController.php)

Mixed bag:

- unpacking array|string is an input-shape problem
- enum property/method access on string is an enum typing problem
- nullable package property access is a nullability problem

### [TriageController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/TriageController.php)

Mostly an enum collection typing issue:

- callback parameter type is too loose
- collection item inferred as `object`
- then `$value` and `label()` become invalid

## C. Request Classes

These are often **not relation problems first**. They are usually:

- missing array value types
- `mixed` coming from request input
- offsets on unknown arrays
- casts from unknown values

Good examples:

- [StoreConsultationPrescriptionRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreConsultationPrescriptionRequest.php)
- [PostDispenseRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/PostDispenseRequest.php)
- [StoreVitalSignRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreVitalSignRequest.php)

These are the places where array-shape docs and early normalization give the biggest payoff.

## D. Middleware

### [HandleInertiaRequests.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Middleware/HandleInertiaRequests.php)

This is a different flavor:

- closures return `mixed` where `string|null` is expected
- relation-loading callbacks are not typed the way `phpstan` expects
- user shared props are reading properties not declared clearly enough on the model

So this is related to typing quality, but not exactly the same as the pharmacy POS collection issue.

## So Is It "The Same Problem"?

The best answer is:

- **yes, partially**

It is the same **root category** when:

- model relation types are lost
- collections are not typed precisely
- nested property access happens on `mixed` / `Model`

It is **not the same exact problem** when:

- request arrays are untyped
- nullable values are not checked
- enums are compared or accessed as strings
- redundant nullsafe or null-coalescing operators are used
- logic is flagged as unreachable

## Recommended Cleanup Order

If you want the fastest improvement with the least churn, do this in order:

1. **Model relation generics**
   Start with pharmacy, dispensing, print-heavy models, and the models named in the noisiest relation errors.

2. **Controller typed locals**
   In print controllers and pharmacy controllers, assign nested relations to local variables after null checks instead of chaining deeply in one expression.

3. **Validated payload shapes**
   Add array-shape docs to controller/action/request payloads where `mixed` arrays are being passed around.

4. **Collection pipeline typing**
   Break long `groupBy()->map()->values()->all()` chains into smaller typed steps.

5. **Nullsafe and enum cleanup**
   Remove unnecessary `?->` and stop comparing enum objects to strings.

6. **Rules array return types**
   Sweep request classes that only need simple iterable typing fixes.

## Highest-Signal First Targets

These would give good returns quickly:

1. [DispensingRecordPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/DispensingRecordPrintController.php)
2. [InventoryRequisitionPrintController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/Print/InventoryRequisitionPrintController.php)
3. [PurchaseOrderController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/PurchaseOrderController.php)
4. [PostDispenseRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/PostDispenseRequest.php)
5. [StoreConsultationPrescriptionRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreConsultationPrescriptionRequest.php)

## Bottom Line

Your `phpstan` output is not one single bug repeated 1000 times, but it is heavily dominated by a few repeating causes:

- lost model and relation types
- untyped arrays
- casts from `mixed`
- weak collection inference

That is why the error list looks huge. Once those root causes are tightened, a lot of the downstream warnings collapse quickly.

## Progress Since This Review

The cleanup has moved in slices rather than one risky repo-wide refactor.

### Cleaned and verified slices

- `FacilityImpersonationController` now matches the surrounding controller style better:
  - explicit `Gate::authorize('viewAny', Tenant::class)`
  - typed `FacilityBranch` mapping
  - user payload extraction into a typed helper
- print and payment controller slice was cleaned and previously verified at `0` targeted `phpstan` errors:
  - `DispensingRecordPrintController`
  - `InventoryRequisitionPrintController`
  - `LabResultPrintController`
  - `VisitPaymentPrintController`
  - related models received stronger relation typing and `Address`-style model PHPDoc
- pharmacy / dispensing slice was cleaned and previously verified at `0` targeted `phpstan` errors:
  - `DispensingController`
  - `PharmacyPrescriptionController`
  - pharmacy POS models, actions, and controllers
- purchase order / role / triage controller slice was cleaned and verified at `0` targeted `phpstan` errors:
  - `PurchaseOrderController`
  - `RoleController`
  - `TriageController`

### Request sweep progress

- a broad request-layer pass has started to reduce repeated `missingType.iterableValue` noise by adding explicit `rules()` return typing
- targeted input-normalization fixes were also made in:
  - `CollectLabSpecimenRequest`
  - `CorrectLabResultEntryRequest`
  - `StoreInsurancePackagePriceRequest`
  - `UpdateInsurancePackagePriceRequest`
  - `StoreVitalSignRequest`
  - `StoreDispenseRequest`
- session-safe impersonation checks were added in `ImpersonationContext`, and `HandleInertiaRequestsTest` is now passing again

## Remaining Work

The request layer still needs a careful second pass. Two things are true at the same time:

- the repetitive request docblock cleanup reduced a lot of low-value `phpstan` noise
- a few request files also lost helper methods or `rules()` structure during that broad sweep and had to be restored

That means the request layer is **partially improved but not yet fully stabilized**.

### Highest-priority remaining request work

1. Re-run `phpstan` on the request layer once the environment memory issue is resolved.
2. Audit the request files touched by the bulk sweep for helper preservation:
   - `StoreConsultationPrescriptionRequest`
   - `PostDispenseRequest`
   - `StoreInventoryRequisitionRequest`
   - `StoreGoodsReceiptRequest`
3. Finish the “real” request fixes, especially:
   - request helper methods that normalize nested `items` arrays
   - casts from request `mixed` input
   - `collect($mixed)` and `map()` pipelines in request validators
4. Resume the remaining medium/high-noise request files after verification:
   - lab result entry requests
   - dispense / prescription workflow requests
   - inventory requests
   - consultation update requests

## Verification Note

Targeted `phpstan` verification was working earlier in the session, but later request verification became blocked by the machine's paging-file / memory limit before `phpstan` could bootstrap reliably. Because of that:

- the controller/model slices above were verified when the environment still allowed it
- the later request-layer work could only be syntax-checked and test-checked, not fully `phpstan`-verified yet
