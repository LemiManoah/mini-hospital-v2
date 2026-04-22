# PHPStan Fix Plan

## Working Rules

For every 100-line chunk from [stan.md](C:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/stan.md), we track:

- planned phases
- completed phases
- verified result

That keeps the document useful as both a fix plan and a progress log.

## Safe Defaults We Should Use

When unsure, prefer these patterns:

### HasFactory

```php
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** @use HasFactory<Factory<self>> */
use HasFactory;
```

### BelongsTo

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @return BelongsTo<RelatedModel, $this> */
```

### HasMany

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @return HasMany<RelatedModel, $this> */
```

### Builder scope

```php
use Illuminate\Database\Eloquent\Builder;

/** @param Builder<$this> $query
 *  @return Builder<$this>
 */
```

## Chunk 1: Lines 1-100

### Files

- `app/Models/ReconciliationItem.php`
- `app/Models/Role.php`
- `app/Models/SpecimenType.php`
- `app/Models/StaffPosition.php`
- `app/Models/Supplier.php`
- `app/Models/TenantGeneralSetting.php`
- `app/Models/TenantSubscription.php`
- `app/Models/TenantSupportNote.php`
- `app/Models/TriageRecord.php`
- `app/Models/Unit.php`
- `app/Models/User.php`

### Dominant Issues

- missing relation generics
- invalid or missing `HasFactory` generics
- missing factory classes
- missing builder generics on scopes
- missing `Attribute<TGet, TSet>` generics on `User`

### Planned Phases

1. standardize `HasFactory` usage
2. add relationship generics
3. type local scopes and accessors where needed
4. add fuller `@property-read` model docs
5. verify with focused PHPStan and Pint

### Completed Phases

- removed `HasFactory` from models with no actual factory class:
  - `ReconciliationItem`
  - `Role`
  - `SpecimenType`
  - `StaffPosition`
  - `TenantGeneralSetting`
  - `TenantSubscription`
  - `TenantSupportNote`
  - `TriageRecord`
  - `Unit`
- normalized real factory-backed models to `Factory<self>`:
  - `Supplier`
  - `User`
- added relation generics using `$this`
- typed the `Supplier` builder scope
- typed `User` accessors with `Attribute<..., never>`
- added fuller top-level `@property-read` model docs on the touched models

### Verified Result

- focused PHPStan on the implemented model batch passed with `0` errors
- Pint ran successfully on the touched files

## Chunk 2: Lines 101-200

### Files

- `app/Models/User.php`
- `app/Models/VisitBilling.php`
- `app/Models/VisitCharge.php`
- `app/Models/VisitPayer.php`
- `app/Models/VitalSign.php`

### Dominant Issues

- invalid or missing `HasFactory` generics
- missing relationship generics
- `MorphTo` generic typing on `VisitCharge`
- historical `User` accessor generic errors already resolved in Chunk 1

### Planned Phases

1. remove `HasFactory` where no real factory exists
2. add relationship generics using `$this`
3. add fuller `@property-read` model docs
4. verify `MorphTo` generic shape locally
5. verify with focused PHPStan and Pint

### Completed Phases

- removed `HasFactory` from:
  - `VisitBilling`
  - `VisitCharge`
  - `VisitPayer`
  - `VitalSign`
- added generic relationship annotations using `$this`
- added fuller top-level `@property-read` model documentation
- verified `MorphTo<Model, $this>` works for `VisitCharge::source()`

### Verified Result

- focused PHPStan on this batch passed with `0` errors
- Pint ran successfully on the touched files

## Chunk 3: Lines 201-300

### Files

- `app/Rules/NoOverlappingInsurancePriceWindow.php`
- `app/Support/DoctorConsultationAccess.php`
- `app/Support/GeneralSettings/TenantGeneralSettings.php`
- `app/Support/InventoryLocationAccess.php`
- `app/Support/InventoryRequisitionWorkflow.php`

### Dominant Issues

- mixed-value access without narrowing
- nullable enum access without guarding
- iterable parameters with no value type
- collection/list return types that are too loose
- builder generics missing on support-layer query helpers
- nullsafe operator used on non-nullable enum

### Planned Phases

1. narrow mixed values before property access
2. add explicit guards for nullable enums before reading `->value`
3. annotate iterable parameter value types precisely
4. tighten collection and list return types so they match real outputs
5. add missing builder generics on query helper methods
6. remove unnecessary nullsafe access where the type is not nullable
7. verify with focused PHPStan and Pint

### Completed Phases

- narrowed mixed values before property access in `NoOverlappingInsurancePriceWindow`
- added explicit nullable-enum guarding in `DoctorConsultationAccess`
- narrowed the plucked settings payload in `TenantGeneralSettings`
- added iterable value types and list-safe return handling in `InventoryLocationAccess`
- tightened collection typing in `InventoryLocationAccess` with explicit `EloquentCollection` empty returns
- added builder generics and removed unnecessary nullsafe access in `InventoryRequisitionWorkflow`

### Verified Result

- focused PHPStan on this batch passed with `0` errors
- Pint ran successfully on the touched files

## Chunk 4: Lines 301-400

### Files

- `app/Support/InventoryStockLedger.php`
- `app/Support/PrescriptionDispenseProgress.php`
- `app/Support/PrescriptionQueueQuery.php`
- `app/Support/ValidatesAppointmentScheduling.php`
- `app/Support/VisitOrderOptions.php`
- `app/Support/VisitWorkflowGuard.php`
- `app/Traits/BelongsToBranch.php`

### Dominant Issues

- casting `mixed` query-row values without narrowing
- support query results using shapes PHPStan cannot infer automatically
- missing paginator generics
- nullsafe and null-coalesce usage on non-nullable enum values
- helper methods typed too narrowly for collection output
- trait callbacks assuming `$this` or model properties too loosely

### Planned Phases

1. annotate raw query-row shapes before casting
2. switch aliased aggregate reads to shaped base-query rows where needed
3. add paginator generics
4. remove invalid nullsafe/null-coalesce usage on non-nullable enums
5. normalize helper input types for plucked IDs
6. tighten trait callback model access through `getAttribute` / `setAttribute`
7. verify with focused PHPStan and Pint

### Completed Phases

- annotated raw stock-ledger query rows before casting in `InventoryStockLedger`
- switched dispense-progress aggregation to shaped base-query rows and aligned `Carbon` return types
- added paginator generics in `PrescriptionQueueQuery`
- removed invalid enum nullsafe usage in `ValidatesAppointmentScheduling`
- normalized billable ID handling in `VisitOrderOptions`
- added a nullable billing-status guard in `VisitWorkflowGuard`
- tightened `BelongsToBranch` model access through `getAttribute()` and `setAttribute()`

### Verified Result

- focused PHPStan on this batch passed with `0` errors
- Pint ran successfully on the touched files

## Chunk 5: Lines 401-500

### Files

- `app/Traits/BelongsToBranch.php`
- `app/Traits/BelongsToTenant.php`

### Dominant Issues

- repeated `property.nonObject` errors across many model contexts
- trait boot hooks assuming direct property access on loosely typed `$model`
- authenticated user access in traits not narrowed before reading tenant data

### Planned Phases

1. treat trait boot callback models as `Model`
2. use `getAttribute()` / `setAttribute()` instead of direct property access
3. narrow `Auth::user()` before reading tenant information
4. rely on shared trait fixes to clear repeated model-context errors
5. verify with focused PHPStan and Pint

### Completed Phases

- hardened `BelongsToBranch` boot logic to use `Model` plus `getAttribute()` / `setAttribute()`
- hardened `BelongsToTenant` boot logic to use `Model`
- narrowed the authenticated user to `User` and used `tenantId()` instead of direct property access

### Verified Result

- focused PHPStan on the shared traits passed with `0` errors
- Pint ran successfully on the touched trait files

## Chunk 6: Lines 501-600

### Files

- `app/Traits/BelongsToTenant.php`
- repeated in-context reports for consuming models such as:
  - `Appointment`
  - `AppointmentCategory`
  - `AppointmentMode`
  - `Clinic`
  - `Consultation`
  - `Department`
  - `DispensingRecord`
  - `DoctorSchedule`
  - and other tenant-scoped models further down the report

### Dominant Issues

- repeated `property.nonObject` errors caused by the same shared trait boot logic
- no new independent failure mode compared with Chunk 5

### Planned Phases

1. confirm this chunk is only a repeated projection of the `BelongsToTenant` issue
2. avoid duplicate edits in consuming models if the trait-level fix is already correct
3. rely on focused verification of the shared trait before moving on

### Completed Phases

- confirmed this chunk is the same `BelongsToTenant` root cause already addressed in Chunk 5
- made no additional code changes because the shared fix is the correct fix location

### Verified Result

- the focused PHPStan pass on `BelongsToTenant` already passed with `0` errors after the Chunk 5 fix
- this chunk is treated as historical repeated output from the original report, not a new unresolved category

## Chunk 7: Lines 601-700

### Files

- `app/Traits/BelongsToTenant.php`
- repeated in-context reports for additional consuming models such as:
  - `DoctorScheduleException`
  - `FacilityBranch`
  - `FacilityService`
  - `FacilityServiceOrder`
  - `GoodsReceipt`
  - `InsuranceCompany`
  - `InsuranceCompanyInvoice`
  - `InsuranceCompanyInvoicePayment`
  - and more tenant-scoped models further down the report

### Dominant Issues

- repeated `property.nonObject` errors caused by the same shared `BelongsToTenant` boot logic
- still no new independent failure mode compared with Chunks 5 and 6

### Planned Phases

1. confirm this is still repeated historical output from the same root cause
2. avoid duplicate edits in consuming models when the trait fix is already in place
3. continue only when a genuinely new error family appears in the report

### Completed Phases

- confirmed this slice is another repeated projection of the already-fixed `BelongsToTenant` issue
- made no additional code changes

### Verified Result

- the shared trait verification from Chunk 5 remains the authoritative check for this repeated group
- this chunk is treated as historical repeated output from the original report, not a new unresolved category

## Chunk 8: Lines 701-793

### Files

- `app/Traits/BelongsToTenant.php`
- repeated in-context reports for additional tenant-scoped models such as:
  - `InsurancePackage`
  - `InsurancePackagePrice`
  - `InventoryBatch`
  - `InventoryItem`
  - `InventoryLocation`
  - `InventoryLocationItem`
  - `InventoryRequisition`
  - `LabRequest`
  - `LabRequestItemConsumable`
  - and the remaining tenant-scoped models at the end of the report

### Dominant Issues

- repeated `property.nonObject` errors caused by the same shared `BelongsToTenant` boot logic
- no new independent failure mode compared with Chunks 5, 6, and 7

### Planned Phases

1. confirm the report tail is still just repeated output from the same shared trait issue
2. avoid duplicate edits in consuming models when the trait-level fix is already correct
3. treat the verified shared trait fix as the authoritative resolution for this tail section

### Completed Phases

- confirmed the remainder of the report is still a repeated projection of the already-fixed `BelongsToTenant` issue
- made no additional code changes

### Verified Result

- the focused PHPStan pass on `BelongsToTenant` from Chunk 5 remains the authoritative verification
- this final chunk is treated as historical repeated output from the original report, not a new unresolved category
