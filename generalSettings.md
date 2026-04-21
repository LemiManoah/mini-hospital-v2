# General Settings Module Plan

**Date:** April 11, 2026  
**Goal:** Define a clean home for hospital-wide operational rules and remove the current mixing of master data, platform controls, and operational modules inside the sidebar `Settings` dropdown.

---

## Status Legend

- ✅ **DONE** — fully implemented and enforced
- 🟡 **PARTIAL** — implemented but not fully enforced or incomplete
- ❌ **NOT DONE** — not yet started

---

## 1) Problem Statement

The current sidebar `Settings` dropdown mixes together different kinds of things:

- hospital-wide policy settings
- master data lists
- operational modules
- branch/facility structure
- SaaS/platform controls

That makes it harder for hospital admins to know where to go when they want to change a rule like:

- whether services can be given before payment
- which currency the hospital uses
- whether dispensing must be batch-tracked

Those rules are not the same kind of thing as:

- addresses
- allergens
- inventory items
- facility switcher

They need a dedicated place.

---

## 2) Recommended Sidebar Structure

### Recommended Direction

Keep **user account settings** where they already belong, under the user menu.

For the main sidebar, I recommend changing the current structure like this:

### A. Keep Operational Modules In Their Own Sections ✅ DONE

These should stay where they already naturally belong:

- Inventory Items
- Inventory Locations
- Lab Management
- Lab Stock Management
- Pharmacy Stock / Requisitions / Movements / Receipts

These are operational or domain-specific modules, not general settings.

### B. Replace The Current `Settings` Dropdown With `Administration` ✅ DONE

The current sidebar `Settings` group should become a broader **Administration** group.

Inside `Administration`, split into:

- `General Settings`
- `Facility Setup`
- `Insurance Setup`
- `Master Data`
- `Platform`

### C. Suggested Administration Menu Contents

#### `General Settings` ✅ DONE

This should be the new module for hospital-wide rules and defaults.

Examples:

- payment-before-service rules
- default currency
- dispensing batch tracking
- partial dispensing rules
- lab result release rules
- numbering formats
- print/document defaults

#### `Facility Setup` ✅ DONE

These define the structure of the facility itself:

- Facility Branches
- Clinics
- Departments
- Facility Services

#### `Insurance Setup` ✅ DONE

These define payer configuration:

- Insurance Companies
- Insurance Packages

#### `Master Data` ✅ DONE

These are reusable setup lists:

- Addresses
- Allergens
- Units
- Currencies

#### `Platform` ✅ DONE

These are not ordinary hospital-admin settings and should stay separate:

- Facility Switcher
- Subscription Packages

`Platform` items should usually remain visible only to support / super-admin users.

---

## 3) What Should Move Out Of The Current Settings Dropdown ✅ DONE

These should **not** live under the future `General Settings` module:

- Inventory Items
- Inventory Locations
- Subscription Packages
- Facility Switcher

Why:

- `Inventory Items` and `Inventory Locations` belong to Inventory
- `Subscription Packages` is SaaS/platform administration
- `Facility Switcher` is support/super-admin workspace control

---

## 4) What The General Settings Module Should Do 🟡 PARTIAL

This module should let a hospital admin configure **facility-specific operational rules** without touching code.

The settings should be grouped and explained clearly, with toggles, selects, numeric inputs, and rule descriptions.

The module should support:

- hospital-wide defaults ✅ DONE
- optional branch-level overrides where needed ❌ NOT DONE — migration and model have no `facility_branch_id`; all settings are tenant-wide only
- audit trail of who changed what ❌ NOT DONE — no `general_setting_audits` table or change history
- safe defaults when a setting is missing ✅ DONE — registry defines defaults, resolver falls back to them

---

## 5) Suggested General Settings Categories

### 5.1 Billing And Payment Rules 🟡 PARTIAL

Examples:

- require payment before services are rendered ✅ DONE
- allow consultation before payment ✅ DONE (inverted as `require_payment_before_consultation`)
- allow lab before payment ✅ DONE
- allow imaging/procedures before payment ✅ DONE
- allow dispensing before payment ✅ DONE
- allow credit patients ❌ NOT DONE
- allow insured patients to bypass upfront payment ✅ DONE
- require bill settlement before discharge ❌ NOT DONE
- auto-create bill items immediately when services are ordered ❌ NOT DONE

### 5.2 Currency And Pricing Rules 🟡 PARTIAL

Examples:

- default operating currency ✅ DONE (stored as `default_currency_id`)
- currency symbol display ❌ NOT DONE
- decimal precision ❌ NOT DONE
- allow price override at point of service ❌ NOT DONE
- show patient prices inclusive or exclusive of tax ❌ NOT DONE
- rounding strategy for bills ❌ NOT DONE

### 5.3 Registration And Numbering Rules 🟡 PARTIAL

Examples:

- patient number format ✅ DONE — `patient_number_prefix` stored and now enforced in `BranchScopedNumberGenerator::nextPatientNumber()`
- visit number format ❌ REMOVED — `visit_number_prefix` setting removed; visit numbers use fixed `VIS` segment
- billing invoice number format ❌ NOT DONE
- receipt number format ✅ DONE — `receipt_number_prefix` stored and now enforced in `RecordVisitPayment::generateReceiptNumber()`
- prescription number format ❌ NOT DONE
- lab accession numbering format ❌ REMOVED — `lab_request_prefix` setting removed per user decision
- reset numbering daily / monthly / yearly / never ❌ NOT DONE

### 5.4 Clinical Workflow Rules ❌ NOT DONE

Examples:

- require triage before consultation ❌ NOT DONE
- allow consultation without vitals ❌ NOT DONE
- allow doctors to prescribe without diagnosis ❌ NOT DONE
- allow ordering labs without consultation note ❌ NOT DONE
- require completed payment before certain orders ❌ NOT DONE
- require discharge summary before marking visit complete ❌ NOT DONE

### 5.5 Laboratory Rules 🟡 PARTIAL

Examples:

- require sample pickup before result entry ❌ NOT DONE
- require review before release ✅ DONE
- allow same user to enter and release result ❌ NOT DONE
- show unreleased results to clinicians or not ❌ NOT DONE
- correction requires reason ❌ NOT DONE
- print released results with branch header/footer ❌ NOT DONE
- require approval before lab release ✅ DONE

### 5.6 Pharmacy Rules 🟡 PARTIAL

Examples:

- enable batch tracking when dispensing ✅ DONE
- enforce FEFO batch suggestion 🟡 IN PROGRESS — next to implement
- allow partial dispensing ✅ DONE
- allow external pharmacy marking ❌ NOT DONE
- require pharmacist verification before dispense completion ❌ NOT DONE
- allow substitution ❌ NOT DONE
- require substitution reason ❌ NOT DONE
- require counselling note on dispense ❌ NOT DONE
- auto-reserve stock when prescription is received ❌ NOT DONE

### 5.7 Inventory Rules ❌ NOT DONE

Examples:

- enable batch tracking generally ❌ NOT DONE
- require expiry dates on selected item types ❌ NOT DONE
- low stock warning threshold behavior ❌ NOT DONE
- allow negative stock or not ❌ NOT DONE
- require approval for stock adjustments ❌ NOT DONE
- auto-generate reorder alerts ❌ NOT DONE

### 5.8 Documents And Printing ❌ NOT DONE

Examples:

- facility logo on prints ❌ NOT DONE
- default print paper size ❌ NOT DONE
- show clinician signature ❌ NOT DONE
- show pharmacist signature ❌ NOT DONE
- show lab reviewer / approver names ❌ NOT DONE
- include diagnosis on patient-facing prints or not ❌ NOT DONE

### 5.9 Notifications And Communication ❌ NOT DONE

Examples:

- SMS enabled ❌ NOT DONE
- appointment reminders enabled ❌ NOT DONE
- notify clinicians when results are released ❌ NOT DONE
- notify pharmacy when urgent prescriptions arrive ❌ NOT DONE

### 5.10 Security And Audit ❌ NOT DONE

Examples:

- session timeout length ❌ NOT DONE
- require reason for correction actions ❌ NOT DONE
- require reason for cancellations ❌ NOT DONE
- hide patient phone numbers from some roles ❌ NOT DONE
- mask prices for some roles ❌ NOT DONE

---

## 6) Strong Additional Settings Worth Considering ❌ NOT DONE

Beyond the examples you already listed, these are especially useful in real hospitals:

- insurer-specific bypass for prepayment rules ❌ NOT DONE
- separate "cash patient" and "insured patient" service rules ❌ NOT DONE
- branch override support for currency and numbering ❌ NOT DONE
- encounter lock rules after discharge ❌ NOT DONE
- dispense-from-assigned-pharmacy-location only ❌ NOT DONE
- mandatory allergy warning acknowledgement before prescribing ❌ NOT DONE
- mandatory abnormal-result acknowledgement before closing visit ❌ NOT DONE
- allow same-day revisit numbering strategy ❌ NOT DONE
- require stock batch selection only for expirable items ❌ NOT DONE
- automatic FEFO suggestion with manual override ❌ NOT DONE
- require supervisor approval for refund or bill reversal ❌ NOT DONE

---

## 7) Recommended Technical Design ✅ DONE

### 7.1 Data Model

The cleanest implementation is a dedicated settings store instead of scattering flags across many tables.

### Recommended Tables

- `tenant_general_settings` ✅ DONE (named `tenant_general_settings` instead of `general_settings`)
- `general_setting_audits` ❌ NOT DONE

### Implemented `tenant_general_settings` Fields

- `id` (uuid) ✅
- `tenant_id` ✅
- `facility_branch_id` ❌ NOT DONE — not in migration; all settings are tenant-wide only
- `setting_group` ❌ NOT DONE — only `key` and `value` columns; grouping lives in the registry
- `setting_key` → `key` ✅
- `value_json` → `value` (text) ✅ (serialized as strings; booleans stored as '1'/'0')
- `value_type` ❌ NOT DONE — type is resolved via registry, not stored in the table
- `is_overridden` ❌ NOT DONE — no branch override concept
- `updated_by` ❌ NOT DONE — no audit column
- `created_at` / `updated_at` ✅

### Why JSON Value Storage Works Well

It lets us store:

- booleans
- strings
- numbers
- arrays
- structured config

without a schema migration for every new rule.

---

## 8) Scope And Resolution Rules 🟡 PARTIAL

Use this precedence:

1. branch override ❌ NOT DONE
2. tenant/facility default ✅ DONE
3. application fallback default ✅ DONE — registry defines defaults used when no row exists

---

## 9) Recommended Backend Structure ✅ DONE

### Controllers

- `GeneralSettingsController` ✅ DONE — implemented as methods on `AdministrationController`

### Requests

- `UpdateGeneralSettingsRequest` ✅ DONE — `app/Http/Requests/UpdateGeneralSettingsRequest.php`

### Services

- `GeneralSettingsRegistry` ✅ DONE — `app/Support/GeneralSettings/GeneralSettingsRegistry.php`
- `GeneralSettingsResolver` ✅ DONE — `app/Support/GeneralSettings/TenantGeneralSettings.php`
- `GeneralSettingsDefaults` 🟡 PARTIAL — defaults live inside the registry's `fields()` method rather than a separate class

### Purpose Of Each

`GeneralSettingsRegistry` ✅ DONE

- defines known settings
- defines labels
- defines categories
- defines expected value type
- defines default value

`GeneralSettingsResolver` ✅ DONE

- resolves effective value using branch override -> facility default -> app default

`GeneralSettingsDefaults` 🟡 PARTIAL

- central source of fallback values — embedded in registry rather than its own class

---

## 10) Recommended Frontend Structure ✅ DONE

### Main Page

- `resources/js/pages/administration/general-settings.tsx` ✅ DONE

### Suggested UI Shape ✅ DONE

Use grouped cards or tabbed sections:

- Billing & Payment ✅ DONE
- Currency & Pricing 🟡 PARTIAL (currency select done; decimal precision, rounding, display rules not done)
- Registration & Numbering 🟡 PARTIAL (patient_number_prefix and receipt_number_prefix enforced; visit/lab prefixes removed)
- Clinical Rules ❌ NOT DONE
- Laboratory Rules 🟡 PARTIAL (review/approval done; others not done)
- Pharmacy Rules 🟡 PARTIAL (batch tracking and partial dispense done; FEFO and others not done)
- Inventory Rules ❌ NOT DONE
- Documents ❌ NOT DONE
- Security ❌ NOT DONE

Each setting should include:

- name ✅ DONE
- short explanation ✅ DONE
- current value ✅ DONE
- whether it is inherited or overridden ❌ NOT DONE — no branch override display

---

## 11) Permissions 🟡 PARTIAL

Recommended new permissions:

- `general_settings.view` ✅ DONE — added to `PermissionSeeder`; `AdministrationController` now uses it
- `general_settings.update` ✅ DONE — added to `PermissionSeeder`; `AdministrationController` now uses it
- `general_settings.override_branch` ❌ NOT DONE

Suggested access:

- `admin`
- `super_admin`

Branch override permission may be restricted more tightly if needed.

---

## 12) Suggested Initial Setting Keys

### Billing ✅ DONE

- `billing.require_payment_before_service` → implemented as `require_payment_before_consultation`, `require_payment_before_laboratory`, `require_payment_before_pharmacy`, `require_payment_before_procedures`
- `billing.allow_consultation_before_payment` → covered
- `billing.allow_lab_before_payment` → covered
- `billing.allow_pharmacy_before_payment` → covered
- `billing.allow_insured_without_upfront_cash` → `allow_insured_bypass_upfront_payment` ✅

### Currency 🟡 PARTIAL

- `finance.default_currency_code` → stored as `default_currency_id` ✅; now pre-selects default when creating a new branch
- `finance.currency_symbol_position` ✅ DONE — `symbol_position` column added to `currencies` table and seeded per currency
- `finance.decimal_places` ✅ DONE — `decimal_places` column added to `currencies` table and seeded per currency
- Currency exchange rates ✅ DONE — new `currency_exchange_rates` table, model, controller, frontend page at `/currency-exchange-rates`, accessible from the Currencies index

### Pharmacy 🟡 PARTIAL

- `pharmacy.enable_batch_tracking` → `enable_batch_tracking_when_dispensing` ✅
- `pharmacy.enforce_fefo` 🟡 IN PROGRESS — next to implement
- `pharmacy.allow_partial_dispense` ✅
- `pharmacy.allow_external_pharmacy` ❌ NOT DONE
- `pharmacy.require_substitution_reason` ❌ NOT DONE

### Laboratory 🟡 PARTIAL

- `laboratory.require_review_before_release` ✅
- `laboratory.allow_same_user_entry_and_release` ❌ NOT DONE
- `laboratory.show_only_released_results` ❌ NOT DONE
- `laboratory.correction_requires_reason` ❌ NOT DONE
- `laboratory.require_approval_before_lab_release` ✅

### Clinical ❌ NOT DONE

- `clinical.require_triage_before_consultation` ❌
- `clinical.require_diagnosis_before_prescription` ❌
- `clinical.require_note_before_lab_order` ❌

---

## 13) Implementation Plan

### Phase 1: Information Architecture Cleanup ✅ DONE

Deliverables:

- decide final sidebar grouping ✅
- remove operational items from `Settings` ✅
- introduce `General Settings` under `Administration` ✅

### Phase 2: Setting Registry And Storage ✅ DONE

Deliverables:

- migration for `general_settings` ✅ (as `tenant_general_settings`)
- registry of supported keys ✅
- resolver service with fallback logic ✅

### Phase 3: Initial General Settings UI ✅ DONE

Deliverables:

- page shell ✅
- grouped settings cards ✅
- save/update flow ✅
- branch override support where relevant ❌ NOT DONE

### Phase 4: Wire Settings Into Real Workflows 🟡 PARTIAL

Start with the highest-value rules:

- payment-before-service ✅ DONE — enforced in consultation, lab, pharmacy, and procedures controllers
- currency ✅ DONE — `decimal_places` and `symbol_position` added to currencies; exchange rates module built; default currency pre-selects on new branch creation
- dispensing batch tracking ✅ DONE — enforced in `PostDispense` action
- FEFO 🟡 IN PROGRESS — next to implement
- lab release rules ✅ DONE — enforced in `ReviewLabResultEntry` and `LaboratoryQueueController`
- patient number prefix ✅ DONE — wired into `BranchScopedNumberGenerator::nextPatientNumber()`
- receipt number prefix ✅ DONE — wired into `RecordVisitPayment::generateReceiptNumber()`
- visit_number_prefix and lab_request_prefix ❌ REMOVED per user decision

### Phase 5: Audit And Safeguards ❌ NOT DONE

Deliverables:

- audit log ❌ NOT DONE
- change reason for critical settings ❌ NOT DONE
- validation of dangerous combinations ❌ NOT DONE

---

## 14) Recommended First Release Scope ✅ DONE

To keep the first version manageable, launched with:

- payment-before-service rules ✅
- default currency ✅ (stored)
- numbering rules ✅ (stored; enforcement pending)
- pharmacy batch-tracking and partial-dispense rules ✅
- lab review/release rules ✅

Implementation update:

- A first saved `General Settings` page now exists under `Administration`
- The first-release scope above is now persisted per tenant
- This initial slice stores the rules and makes them manageable from one page
- Initial workflow enforcement is now in place for:
  - consultation payment gate ✅
  - laboratory order payment gate ✅
  - prescription payment gate ✅
  - facility service order payment gate ✅
  - laboratory release policy, including optional release directly from review when approval is disabled ✅
- Additional workflow-by-workflow enforcement can now be added incrementally on top of that settings base

---

## 15) Bottom Line

The current `Settings` menu feels mixed because it is carrying too many different responsibilities.

The cleanest fix is:

- move operational things back to their own modules ✅ DONE
- move platform-only items out of hospital admin settings ✅ DONE
- introduce a dedicated `General Settings` module under `Administration` ✅ DONE

That new module should become the place where hospital admins control facility-specific business rules without code changes.

---

## 16) Recommendations — What To Do Next (In Order)

These are ordered by value and logical dependency.

### ~~Recommendation 1: Wire Numbering Prefixes Into Number Generation~~ ✅ DONE

- `visit_number_prefix` and `lab_request_prefix` removed per user decision
- `patient_number_prefix` now read in `BranchScopedNumberGenerator::nextPatientNumber()` — `TenantGeneralSettings` injected, `tenantId` param added
- `receipt_number_prefix` now read in `RecordVisitPayment::generateReceiptNumber()` — `TenantGeneralSettings` injected, prefix applied before timestamp

---

### ~~Recommendation 2: Wire Default Currency Into Billing And Display~~ ✅ DONE

- `decimal_places` and `symbol_position` columns added to `currencies` migration and seeded for all currencies
- `currency_exchange_rates` table, `CurrencyExchangeRate` model, `CurrencyExchangeRateController`, and frontend page at `currency/exchange-rates.tsx` all created
- `FacilityBranchController::create()` now reads `default_currency_id` from tenant settings and pre-selects it in the branch creation form
- `CurrencySeeder` expanded with 40+ currencies including major African and international currencies with correct symbols

---

### ~~Recommendation 3: Add Dedicated Permissions For General Settings~~ ✅ DONE

- `general_settings.view` and `general_settings.update` added to `PermissionSeeder` catalog
- `currency_exchange_rates.view/create/update/delete` also added
- `AdministrationController::generalSettingsPermissions()` now returns only `['general_settings.view', 'general_settings.update']`
- `super_admin` and `admin` inherit all permissions automatically

---

### Recommendation 4: Add FEFO Setting And Enforce It In Batch Suggestion ← **CURRENT**

**Priority: Medium**

`pharmacy.enforce_fefo` is in the plan but not in the registry and not enforced. Pharmacies that track batches expect FEFO to be automatic.

Steps:
- Add `enforce_fefo` to `GeneralSettingsRegistry` sections (under Pharmacy Rules)
- Add the field to `UpdateGeneralSettingsRequest` validation
- Find the batch suggestion logic (where batches are suggested for dispensing) and apply FEFO ordering when this setting is enabled
- Add a test for FEFO-ordered vs non-ordered batch suggestion

---

### Recommendation 5: Add Audit Log For Setting Changes (Phase 5)

**Priority: Medium**

There is no record of who changed a setting or when. In a hospital context this is a compliance risk — if a payment rule was silently changed, there is no way to know.

Steps:
- Create a `general_setting_audits` migration with columns: `id`, `tenant_id`, `key`, `old_value`, `new_value`, `changed_by`, `changed_at`
- In `AdministrationController::updateGeneralSettings`, before persisting, read old values and write an audit row for each changed key
- Add a simple audit log view under Administration (or expose it as a tab on the General Settings page)

---

### Recommendation 6: Add Branch-Level Override Support (Phase 3 Gap)

**Priority: Medium**

The plan specifies branch overrides (e.g., a branch can have a different currency or numbering prefix) but the database has no `facility_branch_id` column and `TenantGeneralSettings` has no branch resolution logic.

Steps:
- Add `facility_branch_id` (nullable, foreign key) to `tenant_general_settings`
- Update `TenantGeneralSettings::resolved()` to accept an optional branch ID and apply the precedence: branch override → tenant default → app default
- Update the UI to show which settings are inherited vs overridden at branch level
- Add `general_settings.override_branch` permission

---

### Recommendation 7: Add Clinical Workflow Rules To Registry And Enforce Them (New Category)

**Priority: Medium**

Nothing from section 5.4 (Clinical Workflow Rules) is implemented. These rules control major clinical workflows.

Start with the two highest-value ones:
- `require_triage_before_consultation` — gate the consultation start action behind a triage check
- `require_diagnosis_before_prescription` — gate prescription creation behind a diagnosis being present on the visit

Steps:
- Add the settings to the registry
- Add validation rules to the request
- Add enforcement in the relevant controllers/actions using `VisitWorkflowGuard` pattern

---

### Recommendation 8: Expand Laboratory Rules (Section 5.5 Gaps)

**Priority: Low–Medium**

Two settings are done but several remain:

- `allow_same_user_entry_and_release` — currently there is no check preventing this
- `show_only_released_results` — clinicians may or may not see unreleased results depending on this flag
- `correction_requires_reason` — corrections to released results should require a reason field

---

### Recommendation 9: Add Documents And Printing Settings (Section 5.8)

**Priority: Low**

Useful for print output consistency across branches. Start with the most requested:
- whether clinician/pharmacist/reviewer names appear on printed documents
- whether diagnosis appears on patient-facing prints

These can be read in Blade print templates.

---

### Recommendation 10: Add Security Settings (Section 5.10)

**Priority: Low**

Start with `require_reason_for_cancellations` as it is the most operationally useful. The pattern from `VisitWorkflowGuard` makes adding new enforcement straightforward.
