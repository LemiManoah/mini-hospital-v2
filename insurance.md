# Insurance System

## Overview

The insurance system covers the full lifecycle of insured patient billing: from setting up insurance companies and their coverage policies, through visit billing with copay calculation, to generating invoices and recording payments from insurers.

---

## Data Model

### Setup Layer (Admin)

```
InsuranceCompany
  └── InsurancePackage          (a product the insurer offers)
        └── InsurancePolicy     (branch-scoped, typed: PHARMACY | LAB | SERVICES)
              └── InsurancePolicyItem  (one per covered item: price + copay rule)
```

- **InsuranceCompany** — master insurer record: name, contact, address, status.
- **InsurancePackage** — an insurance product under a company (e.g. "Gold Plan", "Staff Cover").
- **InsurancePolicy** — branch-specific and typed. One package can have three policies per branch: one for pharmacy items, one for lab tests, one for services. Each policy has active date windows and a status.
- **InsurancePolicyItem** — the actual price list. One row per covered item. Stores:
  - `charge_master_id` — FK to the charge master row for the covered item
  - `price` — what the insurer pays for this item
  - `copay_type` — NONE | FIXED | PERCENTAGE
  - `copay_value` — the copay amount or percentage (e.g. 10 means 10% or UGX 10,000)
  - Effective date range (items can expire or have future start dates)

### Visit Layer (Clinical)

- **VisitPayer** — set when a visit is registered. Either CASH or INSURANCE. If INSURANCE, stores the `insurance_company_id` and `insurance_package_id` chosen for this patient/visit.
- **VisitCharge** — each billable item added to a visit. Now includes a `copay_amount` field populated at charge time when the visit is insured.
- **VisitBilling** — the billing summary for the visit. Also carries `insurance_company_id` and `insurance_package_id` when payer is INSURANCE.

### Claims Layer (Finance)

- **InsuredVisitClaim** — one claim per insured visit. Tracks:
  - `claimed_amount` — total submitted to insurer
  - `approved_amount` — what insurer approved
  - `rejected_amount` — what insurer denied
  - `copay_amount` — patient's share (sum of copays across charges)
  - `paid_amount` — what insurer actually paid
  - `status` — see Status Progression below

- **InsuranceCompanyInvoice** — a batch invoice sent to an insurer, grouping multiple claims. Tracks `bill_amount` (claimed − rejected − copay) and `paid_amount`.
- **InsuranceCompanyInvoicePayment** — a payment received from the insurer against an invoice.
- **InsuranceClaimAllocation** — maps a payment to specific claims, enabling partial payment tracking per claim.

---

## Claim Status Progression

```
OPEN → READY_FOR_INVOICE → INVOICED → SUBMITTED → PARTIALLY_PAID / PAID
                                                 → REJECTED / DISPUTED / CANCELLED
```

| Status | Meaning |
|---|---|
| `OPEN` | Claim created, not yet ready to invoice |
| `READY_FOR_INVOICE` | Verified, queued to be included in the next invoice batch |
| `INVOICED` | Added to an InsuranceCompanyInvoice |
| `SUBMITTED` | Invoice sent to the insurer |
| `PARTIALLY_PAID` | Some payment allocated to this claim |
| `PAID` | Fully settled |
| `REJECTED` | Insurer refused the claim |
| `DISPUTED` | Under dispute resolution |
| `CANCELLED` | Voided |

---

## Billing Resolution (How Copay Is Calculated)

When a charge is added to an insured visit:

1. Confirm the visit payer is INSURANCE.
2. Read the visit's insurance package and branch.
3. Map the charged item to the matching policy type:
   - Drug / pharmacy item → PHARMACY policy
   - Lab test → LAB policy
   - Facility service / procedure → SERVICES policy
4. Find an active policy for that package + branch + type.
5. Find an active `InsurancePolicyItem` for the current `charge_master_id` whose effective dates cover today.
6. Use the policy item price as the insurance rate.
7. Calculate copay:
   - `NONE` → copay = 0
   - `FIXED` → copay = `copay_value`
   - `PERCENTAGE` → copay = `price × (copay_value / 100)`
8. Store `copay_amount` on the `VisitCharge`.
9. If no policy item is found, fall back to the current charge master price.

---

## Full Flow: Setup → Billing → Invoice → Payment

### 1. Admin Setup

1. Create `InsuranceCompany` with contact details.
2. Create `InsurancePackage` under the company.
3. Create `InsurancePolicy` per branch and type (PHARMACY / LAB / SERVICES).
4. Add `InsurancePolicyItem` rows — individually or via CSV bulk import.
   - Import flow: select policy → download template → upload for preview → confirm → queued job imports.

### 2. Patient Visit

1. Register patient visit, set `VisitPayer` to INSURANCE, choose company + package.
2. Add charges (drugs, tests, services). Each charge resolves price and copay from the policy.
3. A `VisitBilling` record is created referencing the insurance company and package.
4. An `InsuredVisitClaim` is created in `OPEN` status.

### 3. Claim Preparation

1. Finance team reviews open claims and marks them `READY_FOR_INVOICE`.

### 4. Invoice Generation (`GenerateInsuranceCompanyInvoice`)

1. All `READY_FOR_INVOICE` claims for a given insurer are batched.
2. `bill_amount` per claim = `claimed_amount − rejected_amount − copay_amount`.
3. An `InsuranceCompanyInvoice` is created with the total `bill_amount`.
4. Claims move to `INVOICED`.

### 5. Payment Recording (`RecordInsuranceCompanyInvoicePayment`)

1. When the insurer pays, record an `InsuranceCompanyInvoicePayment`.
2. Allocate payment to specific claims via `InsuranceClaimAllocation`.
3. Validation: allocation totals must equal the payment amount; no allocation can exceed a claim's outstanding balance.
4. Each claim moves to `PARTIALLY_PAID` or `PAID`.
5. Invoice moves to `PARTIAL_PAID` or `FULLY_PAID`.

---

## Routes

| Route | Purpose |
|---|---|
| `GET /insurance-companies` | List companies |
| `GET /insurance-packages` | List packages |
| `GET /insurance-packages/{package}` | Package detail with policies |
| `GET /insurance-packages/{package}/policies/{policy}/items` | Policy item list |
| `GET /insurance-packages/{package}/policies/{policy}/template` | Download CSV template |
| `POST /insurance-packages/{package}/policies/{policy}/import` | Upload import |
| `GET /finance/insurance-invoices` | List invoices |
| `GET /finance/insurance-invoices/{invoice}` | Invoice detail with claims |
| `POST /finance/insurance-invoices` | Generate invoice from ready claims |
| `POST /finance/insurance-invoices/{invoice}/payments` | Record payment + allocations |

---

## Policies: Branch Handling

Policies are branch-specific. When a user creates a policy from an insurance package, the branch is set from the user's active branch. This is intentional — package prices are used during visit billing, and visit charges are tied to the visit branch. Allowing manual branch selection on the same screen would make accidental cross-branch pricing too easy.

A super-admin bulk setup screen for multiple branches would be a separate workflow if needed.

---

## Imports

The import targets a specific policy, not a generic item type.

1. User opens a package, selects or creates a policy.
2. Downloads that policy's CSV template.
3. Uploads for preview.
4. Confirms the preview.
5. A queued job imports the policy item prices.

Preview-first is intentional: insurance prices directly affect billing and claims.

---

## Key Files

| File | Role |
|---|---|
| `app/Models/InsuranceCompany.php` | Master insurer |
| `app/Models/InsurancePackage.php` | Insurance product |
| `app/Models/InsurancePolicy.php` | Branch + type scoped policy |
| `app/Models/InsurancePolicyItem.php` | Per-item price + copay |
| `app/Models/InsuredVisitClaim.php` | Visit claim with financials |
| `app/Models/InsuranceCompanyInvoice.php` | Invoice batch |
| `app/Models/InsuranceCompanyInvoicePayment.php` | Payment from insurer |
| `app/Models/InsuranceClaimAllocation.php` | Payment → claim distribution |
| `app/Models/VisitPayer.php` | Visit payer type + insurance link |
| `app/Models/VisitCharge.php` | Charge with `copay_amount` |
| `app/Actions/GenerateInsuranceCompanyInvoice.php` | Invoice batch generation |
| `app/Actions/RecordInsuranceCompanyInvoicePayment.php` | Payment + allocation |
| `app/Actions/ProcessInsurancePriceListImport.php` | CSV bulk import |
| `app/Enums/InsuranceCopayType.php` | NONE / FIXED / PERCENTAGE |
| `app/Enums/InsurancePolicyType.php` | PHARMACY / LAB / SERVICES |
| `app/Enums/InsuredVisitClaimStatus.php` | Full claim status set |

---

## Future Suggestions

- Policy copy tool: copy a policy from one branch or package to another.
- Import replacement mode: currently skips overlapping items and reports conflicts; add an overwrite option.
- Coverage rules beyond price: authorization requirement, claim codes, annual coverage limits.
- Price change history: audit log showing which policy item price was in effect when a charge was created.
- Policy comparison view: compare two packages side by side.

---

## Charge Master Cleanup Implementation Plan

### Goal

Make insurance policy prices point directly at `ChargeMaster` rows instead of carrying a parallel `item_type + item_id` catalog identity.

After this cleanup:

```text
InsurancePolicyItem -> ChargeMaster
ChargeMaster -> billable_type + billable_id
VisitCharge -> charge_master_id + insurance_policy_item_id snapshot
```

There is no need to preserve legacy `item_type` or `item_id` columns because the project is still in active development and can use `migrate:fresh`.

### Schema

1. Update the base `insurance_policy_items` table.
2. Remove:
   - `item_type`
   - `item_id`
3. Add:
   - `charge_master_id` constrained to `charge_masters`
4. Make policy item uniqueness use:
   - `tenant_id`
   - `insurance_policy_id`
   - `charge_master_id`
   - `effective_from`
5. Add a lookup index for:
   - `tenant_id`
   - `insurance_policy_id`
   - `charge_master_id`
   - `status`

### Model

1. Update `InsurancePolicyItem` casts.
2. Remove `item_type` and `item_id`.
3. Add `charge_master_id`.
4. Add a `chargeMaster()` relationship.

### Validation And Actions

1. Replace policy item request input `item_id` with `charge_master_id`.
2. Validate selected charge master:
   - belongs to the same tenant
   - is active/effective enough for selection
   - has a `billable_type`
   - matches the policy type via `InsurancePolicyType::fromBillableItemType`
3. Update `CreateInsurancePolicy`, `CreateInsurancePolicyItem`, `UpdateInsurancePolicyItem`, and related request payloads to write `charge_master_id`.
4. Update overlap validation to compare by `charge_master_id`.

### Price Resolution

1. Update `ResolveVisitChargeAmount` to find policy items by `charge_master_id`.
2. Keep matching current/active insurance policies by branch, package, policy type, and date window.
3. Preserve fallback to `ChargeMaster.unit_price` when no insurance policy item exists.
4. Continue storing `insurance_policy_item_id`, `unit_price`, and `copay_amount` on `VisitCharge` as snapshots.

### Options And UI

1. Replace policy item option values with charge master ids.
2. Display charge master code, description, type, and unit price in insurance screens.
3. Keep policy creation grouped by policy type, but source options from active charge masters matching that type.
4. Update import templates to use a `charge_master_code` or `charge_master_id` column instead of item ids.
5. Resolve imports to charge master rows, then write `charge_master_id`.

### Tests To Update

1. Policy item create/update tests.
2. Policy import tests.
3. Visit charge price resolution tests.
4. Visit order option price map tests.
5. Any helper that seeds insurance prices.

### Completion Criteria

- `insurance_policy_items` has no `item_type` or `item_id`.
- No app, database, resource, or test code writes insurance policy item legacy item keys.
- Insured visit pricing resolves through `charge_master_id`.
- Insurance import, policy screens, and helper seeders all use charge master identity.
