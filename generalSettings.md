# General Settings Module Plan

**Date:** April 11, 2026  
**Goal:** Define a clean home for hospital-wide operational rules and remove the current mixing of master data, platform controls, and operational modules inside the sidebar `Settings` dropdown.

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

### A. Keep Operational Modules In Their Own Sections

These should stay where they already naturally belong:

- Inventory Items
- Inventory Locations
- Lab Management
- Lab Stock Management
- Pharmacy Stock / Requisitions / Movements / Receipts

These are operational or domain-specific modules, not general settings.

### B. Replace The Current `Settings` Dropdown With `Administration`

The current sidebar `Settings` group should become a broader **Administration** group.

Inside `Administration`, split into:

- `General Settings`
- `Facility Setup`
- `Insurance Setup`
- `Master Data`
- `Platform`

### C. Suggested Administration Menu Contents

#### `General Settings`

This should be the new module for hospital-wide rules and defaults.

Examples:

- payment-before-service rules
- default currency
- dispensing batch tracking
- partial dispensing rules
- lab result release rules
- numbering formats
- print/document defaults

#### `Facility Setup`

These define the structure of the facility itself:

- Facility Branches
- Clinics
- Departments
- Facility Services

#### `Insurance Setup`

These define payer configuration:

- Insurance Companies
- Insurance Packages

#### `Master Data`

These are reusable setup lists:

- Addresses
- Allergens
- Units
- Currencies

#### `Platform`

These are not ordinary hospital-admin settings and should stay separate:

- Facility Switcher
- Subscription Packages

`Platform` items should usually remain visible only to support / super-admin users.

---

## 3) What Should Move Out Of The Current Settings Dropdown

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

## 4) What The General Settings Module Should Do

This module should let a hospital admin configure **facility-specific operational rules** without touching code.

The settings should be grouped and explained clearly, with toggles, selects, numeric inputs, and rule descriptions.

The module should support:

- hospital-wide defaults
- optional branch-level overrides where needed
- audit trail of who changed what
- safe defaults when a setting is missing

---

## 5) Suggested General Settings Categories

### 5.1 Billing And Payment Rules

Examples:

- require payment before services are rendered
- allow consultation before payment
- allow lab before payment
- allow imaging/procedures before payment
- allow dispensing before payment
- allow credit patients
- allow insured patients to bypass upfront payment
- require bill settlement before discharge
- auto-create bill items immediately when services are ordered

### 5.2 Currency And Pricing Rules

Examples:

- default operating currency
- currency symbol display
- decimal precision
- allow price override at point of service
- show patient prices inclusive or exclusive of tax
- rounding strategy for bills

### 5.3 Registration And Numbering Rules

Examples:

- patient number format
- visit number format
- billing invoice number format
- receipt number format
- prescription number format
- lab accession numbering format
- reset numbering daily / monthly / yearly / never

### 5.4 Clinical Workflow Rules

Examples:

- require triage before consultation
- allow consultation without vitals
- allow doctors to prescribe without diagnosis
- allow ordering labs without consultation note
- require completed payment before certain orders
- require discharge summary before marking visit complete

### 5.5 Laboratory Rules

Examples:

- require sample pickup before result entry
- require review before release
- allow same user to enter and release result
- show unreleased results to clinicians or not
- correction requires reason
- print released results with branch header/footer

### 5.6 Pharmacy Rules

Examples:

- enable batch tracking when dispensing
- enforce FEFO batch suggestion
- allow partial dispensing
- allow external pharmacy marking
- require pharmacist verification before dispense completion
- allow substitution
- require substitution reason
- require counselling note on dispense
- auto-reserve stock when prescription is received

### 5.7 Inventory Rules

Examples:

- enable batch tracking generally
- require expiry dates on selected item types
- low stock warning threshold behavior
- allow negative stock or not
- require approval for stock adjustments
- auto-generate reorder alerts

### 5.8 Documents And Printing

Examples:

- facility logo on prints
- default print paper size
- show clinician signature
- show pharmacist signature
- show lab reviewer / approver names
- include diagnosis on patient-facing prints or not

### 5.9 Notifications And Communication

Examples:

- SMS enabled
- appointment reminders enabled
- notify clinicians when results are released
- notify pharmacy when urgent prescriptions arrive

### 5.10 Security And Audit

Examples:

- session timeout length
- require reason for correction actions
- require reason for cancellations
- hide patient phone numbers from some roles
- mask prices for some roles

---

## 6) Strong Additional Settings Worth Considering

Beyond the examples you already listed, these are especially useful in real hospitals:

- insurer-specific bypass for prepayment rules
- separate “cash patient” and “insured patient” service rules
- branch override support for currency and numbering
- encounter lock rules after discharge
- dispense-from-assigned-pharmacy-location only
- mandatory allergy warning acknowledgement before prescribing
- mandatory abnormal-result acknowledgement before closing visit
- allow same-day revisit numbering strategy
- require stock batch selection only for expirable items
- automatic FEFO suggestion with manual override
- require supervisor approval for refund or bill reversal

---

## 7) Recommended Technical Design

### 7.1 Data Model

The cleanest implementation is a dedicated settings store instead of scattering flags across many tables.

### Recommended Tables

- `general_settings`
- optionally `general_setting_audits`

### Suggested `general_settings` Fields

- `id`
- `tenant_id`
- `facility_branch_id` nullable
- `setting_group`
- `setting_key`
- `value_json`
- `value_type`
- `is_overridden`
- `updated_by`
- `created_at`
- `updated_at`

### Why JSON Value Storage Works Well

It lets us store:

- booleans
- strings
- numbers
- arrays
- structured config

without a schema migration for every new rule.

---

## 8) Scope And Resolution Rules

Use this precedence:

1. branch override
2. tenant/facility default
3. application fallback default

This gives flexibility without forcing every branch to define every rule.

---

## 9) Recommended Backend Structure

### Controllers

- `GeneralSettingsController`

### Requests

- `UpdateGeneralSettingsRequest`

### Services

- `GeneralSettingsRegistry`
- `GeneralSettingsResolver`
- `GeneralSettingsDefaults`

### Purpose Of Each

`GeneralSettingsRegistry`

- defines known settings
- defines labels
- defines categories
- defines expected value type
- defines default value

`GeneralSettingsResolver`

- resolves effective value using branch override -> facility default -> app default

`GeneralSettingsDefaults`

- central source of fallback values

---

## 10) Recommended Frontend Structure

### Main Page

- `resources/js/pages/settings/general/index.tsx`

### Suggested UI Shape

Use grouped cards or tabbed sections:

- Billing & Payment
- Currency & Pricing
- Registration & Numbering
- Clinical Rules
- Laboratory Rules
- Pharmacy Rules
- Inventory Rules
- Documents
- Security

Each setting should include:

- name
- short explanation
- current value
- whether it is inherited or overridden

---

## 11) Permissions

Recommended new permissions:

- `general_settings.view`
- `general_settings.update`
- `general_settings.override_branch`

Suggested access:

- `admin`
- `super_admin`

Branch override permission may be restricted more tightly if needed.

---

## 12) Suggested Initial Setting Keys

### Billing

- `billing.require_payment_before_service`
- `billing.allow_consultation_before_payment`
- `billing.allow_lab_before_payment`
- `billing.allow_pharmacy_before_payment`
- `billing.allow_insured_without_upfront_cash`

### Currency

- `finance.default_currency_code`
- `finance.currency_symbol_position`
- `finance.decimal_places`

### Pharmacy

- `pharmacy.enable_batch_tracking`
- `pharmacy.enforce_fefo`
- `pharmacy.allow_partial_dispense`
- `pharmacy.allow_external_pharmacy`
- `pharmacy.require_substitution_reason`

### Laboratory

- `laboratory.require_review_before_release`
- `laboratory.allow_same_user_entry_and_release`
- `laboratory.show_only_released_results`
- `laboratory.correction_requires_reason`

### Clinical

- `clinical.require_triage_before_consultation`
- `clinical.require_diagnosis_before_prescription`
- `clinical.require_note_before_lab_order`

---

## 13) Implementation Plan

### Phase 1: Information Architecture Cleanup

Deliverables:

- decide final sidebar grouping
- remove operational items from `Settings`
- introduce `General Settings` under `Administration`

### Phase 2: Setting Registry And Storage

Deliverables:

- migration for `general_settings`
- registry of supported keys
- resolver service with fallback logic

### Phase 3: Initial General Settings UI

Deliverables:

- page shell
- grouped settings cards
- save/update flow
- branch override support where relevant

### Phase 4: Wire Settings Into Real Workflows

Start with the highest-value rules:

- payment-before-service
- currency
- dispensing batch tracking
- FEFO
- lab release rules

### Phase 5: Audit And Safeguards

Deliverables:

- audit log
- change reason for critical settings
- validation of dangerous combinations

---

## 14) Recommended First Release Scope

To keep the first version manageable, I recommend launching with:

- payment-before-service rules
- default currency
- numbering rules
- pharmacy batch-tracking and partial-dispense rules
- lab review/release rules

That will already make the module valuable without becoming too big.

---

## 15) Bottom Line

The current `Settings` menu feels mixed because it is carrying too many different responsibilities.

The cleanest fix is:

- move operational things back to their own modules
- move platform-only items out of hospital admin settings
- introduce a dedicated `General Settings` module under `Administration`

That new module should become the place where hospital admins control facility-specific business rules without code changes.
