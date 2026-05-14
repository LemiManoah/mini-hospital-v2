# Charge Master Source Of Truth Plan

## Goal

Make `ChargeMaster` the canonical pricing catalog for every billable clinical service in the system.

Today, `ChargeMaster` exists, but it is mostly a mirror of `FacilityService` pricing. The actual billing actions still read fallback prices from domain tables such as `FacilityService.selling_price`, `LabOrderItem.price`, and `InventoryItem.default_selling_price`. The goal is to invert that relationship:

```text
ChargeMaster is the price source.
Domain models describe clinical workflow.
VisitCharge stores the final billed snapshot.
```

---

## Current State

### What `ChargeMaster` currently does

`ChargeMaster` stores:

- `tenant_id`
- optional `facility_branch_id`
- `item_code`
- `description`
- `billable_type`
- `billable_id`
- `unit_price`
- active/effective date fields
- audit fields

For `FacilityService`, `SyncFacilityServiceChargeMaster` creates or updates a charge master row whenever a facility service is created or updated.

The current facility-service flow is:

```text
FacilityService.selling_price
        |
        v
SyncFacilityServiceChargeMaster
        |
        v
ChargeMaster.unit_price
        |
        v
VisitCharge.charge_master_id
```

But the billing price is still read from `FacilityService.selling_price`, not from `ChargeMaster.unit_price`.

### Where pricing currently lives

- Consultations: `FacilityService` rows marked `is_consultation -> ChargeMaster`
- Facility services: `FacilityService.selling_price`
- Lab tests: `LabTestCatalog.base_price`, copied to `LabOrderItem.price`
- Pharmacy: `InventoryItem.default_selling_price`
- Insurance: `InsurancePolicyItem.price`
- Imaging: no pricing path yet

This means `ChargeMaster` is not really the source of truth. It is only partially synchronized metadata.

---

## Target Design

`ChargeMaster` should become the single default-price catalog for all billable items:

```text
ChargeMaster
  id
  tenant_id
  facility_branch_id nullable
  item_code
  description
  billable_type
  billable_id
  unit_price
  is_active
  effective_from
  effective_to
```

Domain models should reference or resolve to a charge master entry:

```text
FacilityService -> charge_master_id
LabTestCatalog -> charge_master_id
ImagingStudyCatalog -> charge_master_id
InventoryItem -> charge_master_id nullable
```

`VisitCharge` should continue to store a snapshot:

```text
VisitCharge
  charge_master_id
  source_type
  source_id
  charge_code
  description
  quantity
  unit_price
  line_total
  copay_amount
```

The source model says, "what clinical event created this charge?" The `ChargeMaster` says, "what billable catalog item and default price did we use?"

---

## Pricing Resolution Flow

The target billing flow should be:

```text
Clinical event
        |
        v
Resolve applicable ChargeMaster
        |
        v
ResolveVisitChargeAmount
        |
        |-- insured visit: look up InsurancePolicyItem override
        |-- cash visit: use ChargeMaster.unit_price
        v
UpsertVisitCharge
```

`ResolveVisitChargeAmount` should receive a `ChargeMaster` or `charge_master_id`, not a loose fallback price.

Example target signature:

```php
public function resolve(
    PatientVisit $visit,
    ChargeMaster $chargeMaster,
    float $quantity = 1.0,
): ?VisitChargePricing
```

Insurance policy lookup can still use `billable_type` and `billable_id` from the charge master initially:

```text
InsurancePolicyItem.item_type = ChargeMaster.billable_type
InsurancePolicyItem.item_id = ChargeMaster.billable_id
```

Later, insurance policy items could point directly to `charge_master_id` if we want stronger catalog identity.

---

## Required Model Changes

### 1. `FacilityService`

Keep:

- `service_code`
- `name`
- `category`
- `cost_price`
- `is_billable`
- `is_consultation`
- `consultation_type` nullable
- `charge_master_id`
- `is_active`

Eventually remove or stop using:

- `selling_price`

`ChargeMaster.unit_price` should become the displayed and billed price.

Consultation services are regular facility services with `is_consultation = true`.
Examples:

- General OPD Consultation
- Review Consultation
- Emergency Consultation

```text
Consultation -> ResolveConsultationFacilityService -> FacilityService -> ChargeMaster
```

### 2. Consultations

The separate `consultation_fees` flow has been removed. Consultation prices now live in the normal facility service catalog.

### 3. `LabTestCatalog`

Add:

- `charge_master_id`

Target billing:

```text
LabOrderItem -> LabTestCatalog -> ChargeMaster
```

`LabOrderItem.price` can remain as a historical snapshot if needed, but it should be filled from `ChargeMaster.unit_price`, not directly from `LabTestCatalog.base_price`.

Eventually remove or stop using:

- `LabTestCatalog.base_price`

### 4. `InventoryItem`

Add:

- `charge_master_id` nullable

Not every inventory item must be billable. Clinical drugs that can be dispensed and billed should have a charge master row.

Eventually remove or stop using for billing:

- `default_selling_price`

### 5. Imaging

Create an imaging catalog first:

```text
ImagingStudyCatalog
  id
  tenant_id
  facility_branch_id nullable
  code
  name
  modality
  body_part nullable
  charge_master_id
  is_active
```

Target billing:

```text
ImagingOrder -> ImagingStudyCatalog -> ChargeMaster
```

This avoids billing from raw `modality + body_part` text.

---

## Required Action Changes

### 1. Replace `SyncFacilityServiceChargeMaster`

Current behavior mirrors `FacilityService.selling_price` into `ChargeMaster`.

Target behavior should be one of:

1. `CreateChargeMasterForFacilityService`
2. `EnsureChargeMasterForBillableItem`
3. `UpdateChargeMasterPrice`

The key difference: price changes should update `ChargeMaster`, not the domain model first.

### 2. Update `ResolveVisitChargeAmount`

Current behavior:

```text
resolve(visit, billableType, billableId, fallbackAmount, quantity)
```

Target behavior:

```text
resolve(visit, chargeMaster, quantity)
```

It should:

1. Verify the charge master is active.
2. Verify the effective date window.
3. For insurance, look for a matching `InsurancePolicyItem`.
4. Fall back to `ChargeMaster.unit_price`.
5. Return `VisitChargePricing`.

### 3. Update `SyncConsultationCharge`

Target:

```text
Consultation
  -> ResolveConsultationFacilityService
  -> facilityService.chargeMaster
  -> ResolveVisitChargeAmount
  -> UpsertVisitCharge
```

Avoid using the removed consultation fee table.

### 4. Update `SyncFacilityServiceOrderCharge`

Target:

```text
FacilityServiceOrder
  -> service.chargeMaster
  -> ResolveVisitChargeAmount
  -> UpsertVisitCharge
```

Avoid reading `FacilityService.selling_price`.

### 5. Update `SyncLabOrderCharge`

Short-term target:

```text
LabOrder
  -> LabOrderItem
  -> test.chargeMaster
  -> one VisitCharge per LabOrderItem
```

This is better for invoices and insurance claims than one aggregate lab-order charge.

### 6. Update pharmacy billing

```text
Posted DispensingRecordItem
  -> inventoryItem.chargeMaster
  -> one VisitCharge per dispensed item
```

Billing from posted dispensing records is more accurate than billing from prescriptions.

### 7. Add `SyncImagingCharge`

Target:

```text
ImagingOrder
  -> imagingStudyCatalog.chargeMaster
  -> ResolveVisitChargeAmount
  -> UpsertVisitCharge
```

This requires imaging orders to reference an imaging study catalog row.

---

## Implementation Progress

### Completed slice 1: facility services and consultations

- `ResolveVisitChargeAmount` can resolve from an active/effective `ChargeMaster`.
- `SyncFacilityServiceOrderCharge` now bills from `ChargeMaster.unit_price`.
- `SyncConsultationCharge` now bills through the linked facility service's charge master.
- Billable facility services without `charge_master_id` are given one when they are billed.
- Visit charges store the resolved `charge_master_id`.

### Completed slice 2: lab catalog and lab order billing

- The base `lab_test_catalogs` migration now creates nullable `charge_master_id`.
- `SyncLabTestCatalogChargeMaster` creates/updates the lab test charge master row.
- `CreateLabTestCatalog` and `UpdateLabTestCatalog` sync the linked charge master.
- `CreateLabOrder` and `UpdateLabOrder` snapshot the charge master price when creating lab order items.
- `SyncLabOrderCharge` resolves lab pricing from the linked charge master.
- Lab tests without `charge_master_id` are given one when they are billed.

### Completed slice 3: pharmacy inventory and prescription billing

- The base `inventory_items` migration now creates nullable `charge_master_id`.
- `SyncInventoryItemChargeMaster` creates/updates charge master rows for active billable drug inventory items.
- Non-drug inventory items and inactive drugs do not create active charge master rows.
- `CreateInventoryItem`, `UpdateInventoryItem`, and `DeleteInventoryItem` keep drug charge master rows in sync.
- `CreatePrescription` loads inventory charge master details before billing.
- Prescription pricing now resolves from charge master when dispensing records are posted.
- Drug options shown during visit ordering prefer charge master pricing before falling back to legacy inventory selling price.

### Completed slice 4: imaging catalog and imaging order billing

- The base imaging migration now creates `imaging_study_catalogs`.
- Imaging study catalog rows can point to `charge_master_id`.
- Imaging orders can optionally reference an `imaging_study_catalog_id` while still supporting the existing free-text modality/body-part flow.
- `SyncImagingStudyCatalogChargeMaster` creates/updates imaging charge master rows.
- `SyncImagingOrderCharge` posts visit charges from the linked imaging study charge master.
- Visit order options now include active imaging studies with charge master-backed quoted prices.
- The imaging order modal can select a catalog study and automatically apply its modality/body part.

### Completed slice 5: consultation facility services and line-level charges

- The separate `consultation_fees` flow has been removed.
- Consultation prices are now regular `FacilityService` records with `is_consultation = true`.
- `FacilityService.consultation_type` selects the service used for consultation billing.
- `SyncConsultationCharge` resolves `Consultation -> FacilityService -> ChargeMaster`.
- The facility service form can mark services as consultation services.
- `SyncLabOrderCharge` now creates one `VisitCharge` per `LabOrderItem`.
- New prescriptions no longer create an aggregate prescription charge at order time.
- `SyncDispensingRecordCharge` creates one `VisitCharge` per posted `DispensingRecordItem`.
- `PostDispense` now triggers dispensing-item charge sync after the record is posted.

### Development migration policy

Because the database can be refreshed during active development, schema changes should be folded into the original/base migrations instead of adding transitional migrations. Backfill steps are not required until the project has production data to preserve.

The `charge_masters` base migration now runs before lab and inventory catalogs so their direct foreign keys can be created during `migrate:fresh`.

### Remaining high-value slices

- Move remaining imports so finance/catalog uploads write `ChargeMaster.unit_price` directly.
- Decide and implement versioned charge master rows for price history.

## Migration Phases

### Phase 1: Make `ChargeMaster` complete

Add `charge_master_id` to:

- consultation facility service fields - done
- `lab_test_catalogs` - done
- `inventory_items` - done
- `imaging_study_catalogs` - done

Create charge master rows through normal create/update actions for:

- facility services
- lab tests - done
- billable inventory drugs - done
- imaging studies - done
- consultation facility services - done

Keep old price fields during this phase.

### Phase 2: Read prices from `ChargeMaster`

Update billing actions to resolve prices from charge master rows:

- `SyncConsultationCharge`
- `SyncFacilityServiceOrderCharge`
- `SyncLabOrderCharge`
- `SyncDispensingRecordCharge`
- `SyncImagingOrderCharge`

At this point, old price fields become compatibility fields only.

### Phase 3: Update admin UI/imports

Move price management screens and imports to write `ChargeMaster.unit_price`.

Completed:

- Charge Master registry screen added for finance/admin price review.
- Charge Master edit screen updates `ChargeMaster.unit_price`, active status, and effective dates directly.
- Accountant role can view and update charge master prices.

Affected areas likely include:

- facility service forms/imports
- lab test catalog forms/imports
- inventory item forms/imports
- consultation facility service setup
- insurance policy item setup
- future imaging catalog setup

### Phase 4: Move to line-level billing where needed

Switch aggregated charges to item-level charges:

- lab: one charge per `LabOrderItem` - done
- pharmacy: one charge per posted `DispensingRecordItem` - done

This makes invoices, reversals, and insurance claims more accurate.

### Phase 5: Retire old price fields

Once all reads and writes use `ChargeMaster`, remove or ignore:

- `FacilityService.selling_price`
- `LabTestCatalog.base_price`
- `InventoryItem.default_selling_price` for billable drugs

Keep historical price snapshots on service events and charges where needed.

---

## Price Versioning Rules

Before implementation, define these rules clearly:

### Recommended rule

`ChargeMaster` is the current catalog price. `VisitCharge` is the historical billed snapshot.

That means:

- Changing `ChargeMaster.unit_price` affects future charges.
- Existing `VisitCharge.unit_price` does not change automatically.
- If a pending/unpaid order is edited, the sync action may re-resolve current pricing.
- Paid, invoiced, or claimed charges should not be silently repriced.

### Effective dates

Use:

- `effective_from`
- `effective_to`
- `is_active`

Avoid overwriting historical charge master rows when price changes are meant to be versioned. Instead, close the old row and create a new row.

Example:

```text
Old CBC test price:
  effective_from = 2026-01-01
  effective_to = 2026-06-30

New CBC test price:
  effective_from = 2026-07-01
  effective_to = null
```

---

## Important Design Decisions

### Decision 1: One row per billable item or versioned rows?

Current code updates the same charge master row by id. For a true source of truth, versioned rows are better.

Recommended:

- Use new charge master rows for new effective prices.
- Keep `billable_type + billable_id` as the stable domain identity.
- Resolve the active row by date.

### Decision 2: Should insurance policy items point to `ChargeMaster`?

Short term:

- Keep `item_type + item_id`.
- Resolve those fields from `ChargeMaster`.

Long term:

- Consider `insurance_policy_items.charge_master_id`.

The short-term option is easier because it avoids rewriting the insurance module immediately.

### Decision 3: Should consultation fees still use facility services?

Implemented:

- There is no separate `consultation_fees` table.
- Consultation fees are facility services marked with `is_consultation = true`.
- `consultation_type` chooses which service applies to a consultation.

This makes consultation billing easier to reason about.

### Decision 4: Should drugs always be in `ChargeMaster`?

Only billable drugs need charge master rows. Non-sale supplies, consumables, and internal stock can remain inventory-only.

---

## Risks

### 1. Duplicate price sources during migration

For a while, old fields and `ChargeMaster.unit_price` will both exist. The code must clearly prefer `ChargeMaster`.

### 2. Existing imports may write old fields

Facility service and inventory imports may need to be updated so they do not silently bypass charge master pricing.

### 3. Repricing old charges could create billing disputes

Do not automatically update existing charges when catalog prices change unless the charge is still safely editable.

### 4. Insurance policy matching must stay stable

If charge master versioning creates new ids for the same service, insurance policies should still be able to match the underlying service identity. This is why keeping `billable_type + billable_id` is useful at first.

### 5. Imaging needs a catalog before it can use charge master cleanly

Do not bill imaging directly from free-text body parts. Add an imaging study catalog first.

---

## Suggested First Implementation Slice

Started with facility services because they already had `charge_master_id`; then extended the pattern to lab tests.

Completed:

1. Update `ResolveVisitChargeAmount` to accept a `ChargeMaster`.
2. Update `SyncFacilityServiceOrderCharge` to use `service.chargeMaster.unit_price`.
3. Update `SyncConsultationCharge` to use charge master pricing instead of `selling_price`.
4. Add tests proving that changing `ChargeMaster.unit_price` changes new facility-service/consultation charges.
5. Add `charge_master_id` to lab tests.
6. Add lab charge master sync.
7. Make lab order creation and lab charge sync use charge master prices.
8. Add `charge_master_id` to inventory items.
9. Add inventory drug charge master sync.
10. Make prescription-related pricing use charge master prices.
11. Add imaging study catalog with charge master linkage.
12. Make catalog-backed imaging orders sync visit charges from charge master prices.
13. Replace consultation fees with consultation facility services.
14. Move lab billing to one charge per lab order item.
15. Move pharmacy billing to one charge per posted dispensing record item.
16. Add a finance/admin Charge Master registry for direct price editing.

Next move to import cleanup and charge master price versioning.

---

## Bottom Line

If `ChargeMaster` stays in the system, it should become the actual pricing catalog, not a synchronized copy of facility service prices. The clean direction is:

```text
Domain model -> ChargeMaster -> InsurancePolicyItem/current price -> VisitCharge snapshot
```

That gives the hospital one place to manage billable prices while still preserving clinical workflow models and historical billed amounts.
