# Phase 3 Implementation Plan: Hospital Infrastructure & Service Catalogs

## Scope
Phase 3 from `implementation.md` covers:
- `clinics`
- `wards`
- `beds`
- `charge_masters`
- `lab_test_catalogs`
- `medication_catalogs`

Goal: establish physical capacity + service catalogs required before patient flow (Phase 4+).

---

## 1. Preconditions (Must Be True Before Phase 3)

1. Phase 2 tenancy and branch context must be active.
2. RBAC must include permissions for all Phase 3 entities.
3. Migration graph must be clean (no FK cycles) before adding more dependencies.
4. For multi-branch installs, active branch context enforcement must already exist in middleware/scope.

Recommended pre-check list:
- `tenants`, `facility_branches`, `departments`, `staff`, `staff_branches` are stable.
- `TenantScope` and branch context are functioning.
- CRUD baseline + tests for Phase 2 pass.

---

## 2. Field-Level Build Plan (Using `hospital_database_schema.md`)

## 2.1 `clinics`
Schema fields to implement:
- `id`, `tenant_id`, `branch_id`, `clinic_code`, `clinic_name`, `department_id`
- `location`, `phone`, `daily_capacity`, `accepts_walk_ins`, `is_active`, timestamps
- Unique: `['tenant_id', 'clinic_code']`

Implementation notes:
- Apply both tenant and branch isolation.
- Validate `department_id` belongs to same `tenant_id`.
- Validate `branch_id` belongs to same `tenant_id`.

Suggested additions:
- Add `softDeletes()`
- Add audit fields (`created_by`, `updated_by`) for consistency with your existing convention.
- Add index `['tenant_id', 'branch_id', 'is_active']`

## 2.2 `wards`
Schema fields to implement:
- `id`, `tenant_id`, `branch_id`, `ward_code`, `ward_name`, `type`
- `department_id`, `capacity`, `current_occupancy`, `gender`, `is_active`, timestamps
- Unique: `['tenant_id', 'ward_code']`

Implementation notes:
- `current_occupancy` must be system-managed; do not trust UI input.
- Guard against over-allocation (`current_occupancy <= capacity`).

Suggested additions:
- `softDeletes()`
- audit fields (`created_by`, `updated_by`)
- add branch-aware uniqueness if ward codes can repeat per branch:
  - option A (current schema): keep `['tenant_id','ward_code']`
  - option B (recommended for large groups): `['tenant_id','branch_id','ward_code']`

## 2.3 `beds`
Schema fields to implement:
- `id`, `tenant_id`, `branch_id`, `ward_id`, `bed_number`
- `type`, `status`, `daily_rate`, `equipment`, `is_active`, timestamps
- Unique: `['ward_id', 'bed_number']`
- Index: `status`

Implementation notes:
- Validate ward-branch consistency (`beds.branch_id == wards.branch_id`).
- Keep bed status transitions controlled by service layer (not raw CRUD).

Suggested additions:
- `softDeletes()`
- audit fields (`created_by`, `updated_by`)
- `last_status_changed_at` timestamp (operational analytics)

## 2.4 `charge_masters`
Schema fields to implement:
- `id`, `tenant_id`, `item_code`, `description`, `category`
- `department_id`, `base_price`, `cost_price`, `price_type`, `unit_of_measure`
- `is_taxable`, `tax_rate`, `requires_doctor_authorization`, `insurance_eligible`
- `effective_from`, `effective_to`, `is_active`, timestamps
- Unique: `['tenant_id', 'item_code']`

Implementation notes:
- Keep history via date-effective rows, not overwrite prices in-place.
- Enforce non-overlapping active periods per `item_code`.

Suggested additions (important):
- Branch pricing strategy decision (missing in schema for multi-branch):
  - option A: add `branch_id` directly to `charge_masters`, use branch scope.
  - option B (recommended): keep tenant master + add `charge_master_branch_overrides` table with branch-specific price/tax/active flags.

## 2.5 `lab_test_catalogs`
Schema fields to implement:
- `id`, `tenant_id`, `test_code`, `test_name`, `category`, `sub_category`
- `department_id`, `specimen_type`, `container_type`, `volume_required_ml`
- `storage_requirements`, `turnaround_time_minutes`, `base_price`
- `requires_fasting`, `reference_ranges` (JSON), `is_active`, timestamps
- Unique: `['tenant_id', 'test_code']`

Implementation notes:
- `reference_ranges` should have a strict JSON schema (age/sex buckets).
- Ensure `department_id` belongs to tenant.

Suggested additions:
- `sample_collection_instructions` (text)
- `is_sendout_test` + `external_lab_partner_id` (if outsourced tests are common)
- branch override table for availability/pricing by branch

## 2.6 `medication_catalogs`
Schema fields to implement:
- `id`, `tenant_id`, `generic_name`, `brand_name`, `drug_code`
- `category`, `dosage_form`, `strength`, `unit`, `manufacturer`
- `is_controlled`, `schedule_class`, `therapeutic_classes` (JSON)
- `contraindications`, `interactions`, `side_effects`, `is_active`, timestamps
- Unique: `['tenant_id', 'drug_code']`

Implementation notes:
- This is formulary metadata, not stock.
- Do not mix inventory quantities into this table.

Suggested additions:
- `requires_prescription` boolean
- `is_high_alert` boolean (LASA/high-risk meds)
- branch override table for branch-level formulary availability

---

## 3. Migration Order for Phase 3

Use this order:
1. `create_clinics_table`
2. `create_wards_table`
3. `create_beds_table`
4. `create_charge_masters_table`
5. `create_lab_test_catalogs_table`
6. `create_medication_catalogs_table`
7. Optional override tables:
   - `charge_master_branch_overrides`
   - `lab_test_branch_overrides`
   - `medication_branch_overrides`

Why this order:
- Physical infrastructure first (`clinics/wards/beds`) for capacity planning.
- Catalogs next to support billing/lab/pharmacy ordering in later phases.

---

## 4. Model, Scope, and Domain Rules

Apply:
- `BelongsToTenant` on all Phase 3 models.
- Branch scoping:
  - `clinics`, `wards`, `beds`: strict branch isolation.
  - `charge_masters`, `lab_test_catalogs`, `medication_catalogs`: tenant master + branch overrides (recommended), or direct branch scope if branch-specific tables are chosen.

Core service/domain invariants:
- Bed occupancy cannot exceed ward capacity.
- Inactive clinic/ward/bed/catalog cannot be selected in downstream workflows.
- A branch cannot see another branch’s wards/beds/clinics.

---

## 5. Validation Rules (Request Layer)

Examples to enforce:
- `clinic_code`, `ward_code`, `item_code`, `test_code`, `drug_code` uniqueness by tenant (and branch where applicable).
- `effective_to >= effective_from` for `charge_masters`.
- `daily_rate >= 0`, `base_price >= 0`, `tax_rate between 0 and 100`.
- `turnaround_time_minutes > 0`.
- `current_occupancy` not writable from UI.
- Cross-entity tenant consistency checks:
  - ward.department tenant = ward tenant
  - bed.ward branch = bed branch
  - clinic.department tenant = clinic tenant

---

## 6. RBAC Additions for Phase 3

Add permissions:
- `clinics.view|create|update|delete`
- `wards.view|create|update|delete`
- `beds.view|create|update|delete`
- `charge_masters.view|create|update|delete`
- `lab_test_catalogs.view|create|update|delete`
- `medication_catalogs.view|create|update|delete`

Map at least:
- `admin`: full access
- `doctor`: view catalogs
- `nurse`: view clinics/wards/beds + limited catalog visibility
- `pharmacist`: full `medication_catalogs`
- `lab_technician`: full `lab_test_catalogs`

---

## 7. Seeder Plan

Seed minimum operational data per tenant + per branch:
1. 2-4 clinics per active branch.
2. 3-8 wards with realistic capacities.
3. beds generated per ward based on capacity.
4. starter charge master items by category.
5. starter lab tests by department.
6. starter medication formulary entries.

If using branch overrides:
- seed branch-level availability/price for at least one tenant with 2+ branches.

---

## 8. UI/Workflow Plan

Build admin CRUD pages in this order:
1. Clinics
2. Wards
3. Beds
4. Charge Masters
5. Lab Test Catalog
6. Medication Catalog

Required UX details:
- Branch-aware filters and badges on all lists.
- Active/inactive toggle actions.
- Safe-delete checks (block deletion if referenced downstream).
- CSV import for catalogs (optional but high value).

---

## 9. Test Plan (Baseline)

Feature tests:
1. Branch A user cannot view Branch B clinics/wards/beds.
2. Create/update validations enforce tenant/branch consistency.
3. Charge effective period conflicts are rejected.
4. Inactive resources are excluded from selection endpoints.
5. Deletion protection for referenced records.

Unit tests:
1. Bed status transition rules.
2. Occupancy counter logic.
3. Price calculation helpers (if created).

Seeder tests:
1. Catalog seeds are tenant-isolated.
2. Branch override seeds apply only to target branch.

---

## 10. Missing Data / Design Gaps to Add in Phase 3

These are the key additions recommended beyond the current Phase 3 table list:

1. Branch-level catalog strategy (critical for multi-branch reality)
- Missing in schema: branch-specific pricing/availability for charge/lab/med catalogs.
- Add override tables or add `branch_id` directly to catalogs.

2. Audit + soft delete consistency
- Several schema snippets omit `softDeletes`, `created_by`, `updated_by`.
- Add for operational traceability and reversibility.

3. Operational metadata
- `beds.last_status_changed_at`
- `lab_test_catalogs.sample_collection_instructions`
- `medication_catalogs.requires_prescription`, `is_high_alert`

4. Controlled vocab tables (optional but recommended)
- Replace fragile enums/text with managed lookup tables where data is likely to evolve:
  - specimen types
  - medication dosage forms/routes
  - charge categories (if business expands)

5. Integrity guards
- Add DB check constraints where supported:
  - occupancy and capacity
  - non-negative prices/rates
  - date range validity

---

## 11. Suggested Deliverables for Phase 3

1. Migrations for all six core tables (+ optional override tables)
2. Eloquent models + relationships + scopes
3. Form requests and actions/services
4. Resource controllers + Inertia pages
5. Seeders
6. Permissions + role mapping updates
7. Feature + unit tests
8. Short runbook (`phase3-runbook.md`) with migration order and rollback notes

---

## Final Recommendation

Implement Phase 3 with **tenant master catalogs + branch override tables** rather than hard-coding branch into every catalog row. It preserves schema intent, supports shared standards across branches, and gives you branch-specific control where needed.
