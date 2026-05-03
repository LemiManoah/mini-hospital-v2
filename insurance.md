# Insurance Module — How It Works

## Overview

The insurance module manages insurance companies, their packages/plans, pricing overrides per package, and the full claims lifecycle from patient visit through payment reconciliation.

---

## User Story Examples

### Story 1 — Registering an Insurance Company and Package

> **As an admin**, I want to register "APA Insurance" with a plan called "Gold Cover" so that patients on that plan can be billed at negotiated rates.

**What happens in the system:**
1. Admin creates an `InsuranceCompany` record for "APA Insurance".
2. Admin creates an `InsurancePackage` record named "Gold Cover" under APA Insurance.
3. Admin adds `InsurancePackagePrice` records for each billable item the package covers (e.g., Consultation = KES 500, Amoxicillin = KES 80, Full Blood Count = KES 300).
4. Each price has an `effective_from` date and optionally an `effective_to` date, enabling versioned/expiring pricing contracts.

---

### Story 2 — Patient Visit with Insurance

> **As a receptionist**, I want to register a patient visit and assign their insurance so the system knows how to price and bill their services.

**What happens:**
1. Receptionist creates a `PatientVisit`.
2. A `VisitPayer` record is created with `billing_type = INSURANCE`, linked to APA Insurance and the Gold Cover package.
3. When the doctor adds a service (e.g., a consultation), the system calls `ResolveVisitChargeAmount` which:
   - Looks up `InsurancePackagePrice` matching the package + billable item + current date.
   - If found, uses the negotiated price (e.g., KES 500 instead of the standard KES 800).
   - If not found, falls back to the standard price or returns null (blocking the charge if no price is configured).
4. `VisitBilling` is created with `status = INSURANCE_PENDING` instead of `PENDING`.

---

### Story 3 — Service Not Covered by the Package

> **As a billing officer**, I want to know when a service is not covered by a patient's insurance so I can request direct payment.

**What happens:**
- If `ResolveVisitChargeAmount` finds no matching `InsurancePackagePrice` for the billable item and no fallback is provided, the charge resolution returns `null`.
- The system cannot apply the insurance price — the item is not covered under that package.
- The billing officer must either change the payer to CASH for that item, or the patient pays the standard price out of pocket.

> **Current gap:** There is no explicit "covered/not covered" flag on `InsurancePackagePrice`. Coverage is currently implicit — if a price record exists, the item is covered; if it doesn't exist, it is not. See [Suggestions](#suggestions) below.

---

### Story 4 — Generating an Insurance Claim

> **As a billing officer**, I want to finalize a patient's bill and submit a claim to APA Insurance for reimbursement.

**What happens:**
1. Billing officer finalizes the visit. `SyncInsuredVisitClaim` runs and creates an `InsuredVisitClaim` record:
   - `claimed_amount = gross_amount - discount_amount`
   - `status = READY_FOR_INVOICE` (if claimed_amount > 0)
   - Auto-generated reference: `CLM-20250502-AB3X`
2. At end of month, billing officer runs `GenerateInsuranceCompanyInvoice` for APA Insurance:
   - All `READY_FOR_INVOICE` claims for APA Insurance are batched into one `InsuranceCompanyInvoice`.
   - Claims move to `INVOICED` status.
   - Invoice has a unique code, date range, total `bill_amount`, and due date.
3. Invoice is printed/exported and sent to APA Insurance.

---

### Story 5 — Recording Insurance Payment

> **As a finance officer**, I want to record a payment received from APA Insurance and allocate it to specific claims.

**What happens:**
1. APA Insurance sends a cheque/EFT for KES 45,000 against Invoice #INV-2025-001.
2. Finance officer runs `RecordInsuranceCompanyInvoicePayment`:
   - Creates an `InsuranceCompanyInvoicePayment` record (amount = KES 45,000, receipt number).
   - Allocates to individual claims — e.g., Claim CLM-001 gets KES 20,000, CLM-002 gets KES 25,000.
   - `InsuranceClaimAllocation` records link payment → each claim.
3. Each claim's status updates:
   - Fully allocated → `PAID`
   - Partially allocated → `PARTIALLY_PAID`
4. If the total paid equals the invoice's `bill_amount` → invoice moves to `FULLY_PAID`.

---

### Story 6 — Insurance Partially Rejects a Claim

> **As a billing officer**, I want to record that APA Insurance rejected part of a claim so I can chase the patient for the balance.

**What happens:**
1. APA sends back a remittance: Claim CLM-001 approved for KES 400, rejected KES 100 (service not pre-authorized).
2. Billing officer updates `InsuredVisitClaim`:
   - `approved_amount = 400`, `rejected_amount = 100`, `copay_amount = 0`
3. The rejected portion becomes the patient's responsibility — a cash charge can be created for the copay/rejected amount.

---

## How Package Pricing Works

### The `InsurancePackagePrice` Table

Each row answers: *"How much does Package X pay for Item Y, at Branch Z, between Date A and Date B?"*

| Field | Meaning |
|---|---|
| `insurance_package_id` | Which package this price belongs to |
| `billable_type` | Type: `SERVICE`, `DRUG`, `TEST`, `IMAGING`, `PROCEDURE`, `BED_DAY`, `OTHER` |
| `billable_id` | The specific service/drug/test ID |
| `price` | The negotiated price for this item under this package |
| `effective_from` | When this price starts |
| `effective_to` | When this price expires (null = no expiry) |
| `branch_id` | Optional: branch-specific pricing |
| `status` | ACTIVE / INACTIVE |

### Price Resolution (`ResolveVisitChargeAmount`)

When charging a billable item on an insured visit:

```
1. Is the visit payer type INSURANCE with a package? → YES
2. Query InsurancePackagePrice WHERE:
      tenant      = current tenant
      branch      = visit branch
      package     = visit's package
      billable_type = item type (SERVICE / DRUG / etc.)
      billable_id = specific item
      status      = ACTIVE
      effective_from <= today
      effective_to IS NULL OR effective_to >= today
3. Order by effective_from DESC → take the latest matching price
4. Found? → use that price
5. Not found? → use fallback amount (or null = not billable)
```

### Ensuring Unique Prices per Item

The `NoOverlappingInsurancePriceWindow` validation rule ensures no two active price records for the same `(package, billable_type, billable_id, branch)` have overlapping date ranges. This prevents ambiguity when resolving which price to apply.

---

## How Coverage Works (Current Behaviour)

Coverage is **implicit** right now:

- **Covered** = an active `InsurancePackagePrice` record exists for that item under the package.
- **Not covered** = no record exists → `ResolveVisitChargeAmount` returns `null`.

This means you control coverage by simply adding or not adding a price record. If you add a price of `0`, the item is "covered at no cost to the insurer" (free to patient under insurance). If you don't add a record, the item is not covered.

---

## Suggestions

### 1. Explicit Coverage Flag (High Priority)

**Problem:** There is no clear distinction between "this item is covered but I haven't set a price yet" and "this item is intentionally not covered." A missing price could mean either.

**Suggestion:** Add a `covered` boolean to `InsurancePackagePrice`, or introduce a separate `insurance_package_coverage` table:

```
insurance_package_coverage
  - insurance_package_id
  - billable_type
  - billable_id
  - is_covered (boolean)
  - coverage_limit (nullable decimal) — max the insurer will pay per visit/year
  - requires_preauthorization (boolean)
```

Then `InsurancePackagePrice` stores the price, but `insurance_package_coverage` controls *whether* it's covered at all. This separation allows:
- "Item is covered, price TBD" (covered = true, no price yet)
- "Item is explicitly excluded" (covered = false)
- Pre-authorization requirements per item

---

### 2. Annual / Visit Limits (High Priority)

**Problem:** The current schema has no way to enforce coverage limits — e.g., "APA Gold covers up to KES 50,000 per year per member" or "max 2 physiotherapy sessions per visit."

**Suggestion:** Add limit fields to the coverage model:
- `annual_limit` — max amount insurer pays per year per member
- `per_visit_limit` — max per visit
- `quantity_limit` — e.g., max 2 sessions of physiotherapy per visit
- `lifetime_limit` — for high-cost benefits like surgery

---

### 3. Copay / Coinsurance Configuration (Medium Priority)

**Problem:** Copay is recorded on claims after the fact, but there's no configuration that says "Gold Cover requires a 20% copay on all drugs."

**Suggestion:** Add copay/coinsurance fields to coverage:
- `copay_type` enum: `FIXED`, `PERCENTAGE`, `NONE`
- `copay_value` decimal — e.g., 500 (fixed) or 20 (percentage)

Then when charging, the system auto-calculates the patient's share vs. insurer's share, so reception knows upfront what the patient pays before leaving.

---

### 4. Package-Level Benefit Summary (Medium Priority)

**Problem:** There's no way to quickly see "what does the Gold Cover plan cover?" without scanning all `InsurancePackagePrice` rows.

**Suggestion:** Add a `benefit_summary` or `description` field to `InsurancePackage`, or build a coverage summary view that groups covered items by type (services, drugs, imaging, etc.) for easy review when onboarding a patient.

---

### 5. Pre-Authorization Workflow (Medium Priority)

**Problem:** Some high-cost services (surgeries, MRI, admissions) require insurer approval before the hospital can proceed. There's no pre-authorization model.

**Suggestion:** Add a `PreAuthorization` model:
```
pre_authorizations
  - patient_visit_id
  - insurance_company_id
  - insurance_package_id
  - billable_type / billable_id
  - auth_reference (from insurer)
  - requested_at, approved_at, rejected_at
  - approved_amount
  - status: PENDING / APPROVED / REJECTED / EXPIRED
```

Billing can then check whether a pre-auth is required (from coverage config) and whether one exists before allowing the charge.

---

### 6. Drug Formulary Support (Low Priority)

**Problem:** Insurers often have approved drug lists (formularies). Currently all drugs are treated equally.

**Suggestion:** Add an `is_formulary` flag to the drug model, or let coverage records tag drugs as formulary/non-formulary per package. Non-formulary drugs would either be blocked or require pre-auth.

---

### 7. Claim Submission Status (Low Priority)

**Problem:** The `InsuredVisitClaim` has `INVOICED` and `SUBMITTED` as separate statuses, but there's no tracking of *how* a claim was submitted (portal, email, paper) or a submission reference from the insurer.

**Suggestion:** Add:
- `submission_method` enum: `PORTAL`, `EMAIL`, `PAPER`, `EDI`
- `insurer_reference` — the reference number the insurer assigns when they receive the claim
- `submitted_by_id` — which staff member submitted it

---

## Status Flow Reference

```
Patient Visit Created
       │
       ▼
VisitPayer (INSURANCE) ──► VisitBilling (INSURANCE_PENDING)
       │
       ▼
Services/Drugs Added ──► ResolveVisitChargeAmount ──► InsurancePackagePrice lookup
       │
       ▼
SyncInsuredVisitClaim ──► InsuredVisitClaim (OPEN → READY_FOR_INVOICE)
       │
       ▼
GenerateInsuranceCompanyInvoice ──► InsuranceCompanyInvoice (OPEN)
       │                            InsuredVisitClaim → INVOICED
       ▼
Printed & Sent to Insurer
       │
       ▼
RecordInsuranceCompanyInvoicePayment
       │
       ├──► Full payment  → Claim: PAID,          Invoice: FULLY_PAID
       ├──► Partial       → Claim: PARTIALLY_PAID, Invoice: PARTIALLY_PAID
       └──► Rejection     → Claim: REJECTED        (patient billed for balance)
```
