# Hospital Billing Module - Production Blueprint

## Purpose

Billing must answer one question for every patient visit:

**Who owes what, to whom, and has it been paid?**

For this system, that means billing is not just payment collection. It must cover:

- charge capture from all billable clinical events
- payer setup at visit start
- patient invoicing
- partial payments and receipts
- insurance claim accumulation
- insurer invoice batching
- debtor tracking
- refunds, discounts, and write-offs
- daily reconciliation and financial reporting
- auditability across every money-moving action

This document does three jobs:

1. defines what a production-grade billing module must contain
2. records what this application already has today
3. identifies what should be added, replaced, or reworked to make billing genuinely production-ready

Pre-authorisation is intentionally out of scope for now and should be revisited later.

---

## Executive Answer

As of May 1, 2026, the billing module is **not fully implemented**.

It is partially implemented and already useful for OPD billing: the app has visit payer setup, visit billing headers, charge lines, package-aware charge pricing, branch payment methods, payment capture, receipt printing, daily revenue reporting, and a dedicated OPD finance payment desk.

It is not yet a complete production hospital billing module because insurance claim submission/rejection handling, billing-officer insurer workflows, debtor management, write-off governance, inpatient deposits, controlled document sequencing, cashier reconciliation, and manager-grade receivables reporting are still missing or incomplete.

A complete billing module should let each role do the following:

- Patient: understand the bill, pay fully or partially, receive a receipt, and see what insurance covered.
- Receptionist: set the payer correctly at visit start and surface old balances.
- Doctor: see enough billing context to make informed care decisions without becoming a cashier.
- Cashier: collect, receipt, refund, and explain every patient-facing charge quickly and accurately.
- Billing Officer: turn insured visits into insurer-facing claims and batched invoices without spreadsheets.
- Finance Manager: monitor collections, receivables, discounts, write-offs, and cash-control risk across branches.

If the system cannot do those things reliably, audibly, and at scale, it is not production-ready.

---

## Role Experience Requirements

### Patient

Billing should let the patient experience a transparent invoice:

- consultation, labs, imaging, procedures, bed charges, consumables, and medicines appear as line items
- total due is clear
- co-pay is separated from insurer-covered amount when insurance applies
- partial payment is allowed when policy permits
- each payment generates a receipt with a unique reference
- outstanding balance remains visible after the visit

### Receptionist

Billing starts at registration, not at the cashier desk:

- select payer type for the visit: cash or insurance
- if insurance, capture insurer and package at visit start
- warn if the patient has an unpaid prior balance
- optionally carry previous balance into the new visit billing context
- block invalid combinations such as insurance selected without package

### Doctor / Clinical Team

Billing should inform care without forcing financial workflow into clinical workflow:

- show payer type on the visit
- show whether the visit is self-pay or insured
- show whether the patient has an old unpaid balance
- show whether the current visit still has an unpaid balance
- allow clinical ordering to create charges in the background through billing actions

Pre-authorisation hints are excluded for now.

### Cashier

Billing must act as a fast collection terminal:

- find visit by visit number, patient, or MRN
- view all active charges with totals already computed
- collect cash, card, mobile money, transfer, or other configured methods
- capture external reference numbers when required
- support partial payments
- print or reprint receipts
- process approved refunds
- apply approved discounts with reason capture
- see running balance immediately after payment
- close a visit financially when fully settled

### Billing Officer

Billing must act as an insurer claim and receivables workspace:

- list insured visits awaiting claim submission or invoice batching
- filter by insurer, package, branch, visit date, invoice status, and aging
- generate batched insurer invoices from eligible visit claims
- attach all included visits to one insurer invoice
- record insurer payments and allocate them back to visit-level claims
- track claim statuses such as open, invoiced, part-paid, disputed, rejected, paid
- monitor unpaid insurer balances without spreadsheets

### Finance Manager

Billing must act as a control center:

- see daily collections by branch, cashier, and payment method
- review patient receivables aging
- review insurer receivables aging
- review discounts by amount, reason, and approver
- review write-offs by reason and approver
- review refunds and reversals
- review deposit balances for inpatients
- audit every financial event by actor and timestamp

---

## What The Current System Already Has

The current application already contains useful billing foundations. These should be treated as reusable primitives, not as the final production design.

### Existing schema and behavior

| Existing asset | Current role | Keep / Rework |
|---|---|---|
| `visit_payers` | Stores per-visit payer setup (`cash` or `insurance`, plus insurer/package) | Keep |
| `visit_billings` | Per-visit billing header with gross, discount, paid, balance, payer type, status | Keep |
| `visit_charges` | Per-visit line items tied to source records through a morph relation | Keep |
| `payments` | Patient-facing payment records with receipt number, payment method, refund flag | Keep |
| `payment_methods` | Branch-scoped payment method master data used by OPD payment capture | Keep and strengthen |
| `charge_masters` | Tariff catalog currently synced from billable facility services | Keep and expand |
| `insurance_companies` | Payer master data | Keep |
| `insurance_packages` | Insurer package master data | Keep |
| `insurance_package_prices` | Package-based price overrides by billable item and date range | Keep |
| `insurance_company_invoices` | Batched insurer invoice header | Keep |
| `insurance_company_invoice_payments` | Payments received against insurer invoices | Keep |
| `insured_visit_claims` | Visit-level insured claim lifecycle records generated from insured billing | Keep and expand |
| `insurance_claim_allocations` | Claim-level insurer payment allocation detail | Keep and wire into remittance posting |
| `pharmacy_pos_sales` and `pharmacy_pos_payments` | Walk-in pharmacy sale and payment records | Keep as a separate POS billing stream |
| `stock_movements` | Operational inventory movement ledger for receipts, issues, dispensing, POS sales, and reversals | Keep as the inventory subledger |
| `EnsureVisitBilling` | Creates billing header when a visit begins charging | Keep |
| `UpsertVisitCharge` | Creates or updates visit charge lines from billable events | Keep |
| `ResolveVisitChargeAmount` | Resolves insured package price or fallback cash price | Keep |
| `RecalculateVisitBilling` | Recomputes gross, paid, balance, and status from charges and payments | Keep |
| `RecordVisitPayment` | Records visit payment and emits audit activity | Keep |
| Visit overview billing summary | Visit profile now shows payer and service payment state without acting as a cashier screen | Keep |
| Finance & Accounting > Incoming OPD Payments | Dedicated cashier queue and payment desk for unsettled OPD visits | Keep and expand |
| Visit payment print flow | Produces printable payment receipt | Keep and improve |
| Daily revenue reporting | Gives a starting point for collections reporting | Keep and expand |

### What the current system already answers well

- A visit can already be marked as cash or insurance.
- Billable events can already create visit charges through actions.
- Insurance package prices can already override standard prices.
- A visit already has a summarized billing header.
- Payments already reduce the balance through recalculation.
- Payment activity is already being audited.
- Cash collection has been moved out of the visit profile into a dedicated finance queue and payment desk.
- Branch payment methods now exist and OPD payment capture validates `payment_method_id`.
- Payment receipts can be printed as PDFs.
- Consultation charges can now be created when a matching consultation tariff service is configured.
- Prescription orders now create visit charges using drug tariff or cash price resolution.
- Walk-in pharmacy POS sales can capture payments and post stock movements.
- Goods receipts can post inventory batches and stock movements.

### What is still missing or weak in the current system

- visit-level insurance claim ledger now exists at the backend level, with invoice batching and remittance allocation actions, but full billing-officer UI is still pending
- claim lifecycle tracking now starts with open and ready-for-invoice states, but downstream invoiced, submitted, disputed, rejected, and paid workflows still need UI and actions
- discount history now exists at the backend level, but cashier and manager UI surfaces still need to be added
- no write-off workflow
- payment methods exist, but they are not yet tied to cash tills, bank accounts, cashier shifts, or a general ledger
- no inpatient deposit workflow
- no debtor-management workflow beyond the raw balance
- no receipt or invoice sequence strategy suitable for finance control
- remittance allocation now exists at the backend level, but needs UI, remittance import, and exception handling for rejected or disputed claim lines
- no production-grade reporting model for collections, claims aging, discounts, write-offs, and debtors
- no explicit billing document specification for patient invoices, receipts, insurer invoices, and credit/refund documents
- no general-ledger posting for revenue, receivables, cash, bank, inventory, cost of goods sold, supplier liabilities, discounts, write-offs, or refunds
- consultation charging is now tariff-driven, but it still needs fuller charge-catalog and revenue-account mapping
- prescription billing captures ordered drug value, but dispense-time billing, substitutions, and stock-linked pricing controls still need a fuller pharmacy-billing policy

So the current system is a strong base, but it is still a foundation rather than a finished hospital billing module.

---

## What We Can Borrow From `billing.md`

The older system describes several patterns worth reusing:

- encounter-based billing is the correct anchor
- charge creation should happen when the clinical event happens
- summary invoice totals should be stored, not recalculated from scratch for every screen
- payment logs should stay separate from invoice summaries
- debt should be modeled as unpaid balance, not as an unrelated module
- insurance claims should accumulate per visit first, then batch into insurer invoices later
- refunds and reversals must be tracked as financial events, not silent edits

These ideas still fit this application.

What we should not copy literally:

- the exact old table names like `visit_payments` and `insured_visit_payments`
- Livewire-specific UI assumptions
- any design that splits patient and insurer visit totals into two different primary invoice concepts if our current `visit_billings` can remain the single visit anchor

The better approach here is:

- keep `visit_billings` as the visit billing anchor
- keep `visit_charges` as the frozen charge ledger
- keep `payments` for patient-side cash collection
- add a dedicated insured-claim layer instead of overloading `visit_billings`

---

## Production Billing Scope

A production-grade hospital billing module in this app should cover all of the following.

### 1. Visit payer setup

- payer type at visit creation
- insurer and package selection when insured
- validation that insured visits have valid payer details
- branch-aware and tenant-aware payer setup
- carry-forward of previous balance when policy allows

### 2. Charge capture

- consultation fees
- service orders
- lab requests
- imaging requests
- prescriptions and dispensed medicines
- consumables and supplies
- procedures
- bed-day charges
- inpatient ancillary charges
- manual miscellaneous charges with permission control

Every charge must store:

- visit
- source record
- description
- charge code
- quantity
- unit price
- line total
- payer context at time of charge
- status
- actor
- timestamp

### 3. Pricing and tariff control

- cash pricing
- insurer-specific pricing
- date-effective price versions
- branch-aware pricing where needed
- charge-code catalog
- category and department grouping
- ability to retire old prices without losing historical invoice integrity

### 4. Patient invoice management

- one active billing header per visit
- itemized charges
- running totals
- discounts
- taxes if applicable
- paid amount
- outstanding balance
- billing status
- invoice numbering
- printable patient invoice

### 5. Payment collection

- full payment
- partial payment
- multiple payments against one visit
- refunds
- payment references
- cashier identity
- receipt generation
- receipt reprint
- branch-aware collection
- per-method reconciliation

### 6. Insurance claims and insurer invoicing

- per-visit insured claim summary
- claim status tracking
- claim amount versus approved amount
- co-pay tracking
- claim batching into insurer invoices
- insurer payment recording
- partial remittance allocation
- insurer receivables aging
- dispute and rejection tracking

### 7. Debtor management

- identify visits with outstanding patient balances
- collect debt after visit closure
- age debt by days outstanding
- show last payment date
- show branch and payer context
- track write-offs separately from discounts

### 8. Discounts, waivers, and write-offs

- discount event history
- reason capture
- approval workflow
- discount reversal history
- write-off event history
- separate reporting for commercial discount versus bad-debt write-off

### 9. Inpatient billing

- admission deposit collection
- deposit balance tracking
- periodic bed-day charging
- consolidated discharge invoice
- application of deposits at discharge
- unused deposit refund if policy allows

### 10. Reporting and controls

- daily collections by cashier
- daily collections by method
- daily collections by branch
- charge revenue by department
- charge revenue by category
- patient debtor aging
- insurer receivables aging
- discount summary
- write-off summary
- refund summary
- deposit summary

### 11. Audit and permissions

- all financial events recorded with actor and time
- no silent edits of money values
- permission-separated collection, discounting, refunding, writing off, and insurer invoicing
- printable and screen audit surfaces for support and finance

---

## Recommended Target Domain Model

### Keep as core tables

- `visit_payers`
- `visit_billings`
- `visit_charges`
- `payments`
- `payment_methods`
- `charge_masters`
- `insurance_companies`
- `insurance_packages`
- `insurance_package_prices`
- `insurance_company_invoices`
- `insurance_company_invoice_payments`
- `pharmacy_pos_sales`
- `pharmacy_pos_payments`

### Add as required production tables

#### `insured_visit_claims`

Purpose:
One visit-level insurance claim ledger row per insured visit.

Why:
`visit_billings` is the visit invoice anchor, but insured billing still needs a dedicated claim object with statuses like open, invoiced, rejected, part-paid, and paid.

Suggested minimum fields:

- `visit_billing_id`
- `patient_visit_id`
- `insurance_company_id`
- `insurance_package_id`
- `insurance_company_invoice_id`
- `claim_reference`
- `claimed_amount`
- `approved_amount`
- `rejected_amount`
- `copay_amount`
- `status`
- `submitted_at`
- `paid_at`
- `notes`

#### `insurance_claim_allocations`

Purpose:
Allocation rows linking insurer payments or remittances to individual visit claims.

Why:
When an insurer pays one bulk invoice partially, finance still needs to know how much of that payment settled each visit claim.

Implemented baseline:

- `tenant_id`
- `facility_branch_id`
- `insured_visit_claim_id`
- `insurance_company_invoice_id`
- `insurance_company_invoice_payment_id`
- `allocation_date`
- `allocated_amount`
- `notes`
- audit timestamps and user stamps

#### `billing_discounts`

Purpose:
Event history for discounts, including reason, approver, and reversal details.

Why:
A running `discount_amount` on `visit_billings` is not enough for finance control.

#### `billing_write_offs`

Purpose:
Formal record of balances removed from receivables as bad debt, charity, or administrative write-off.

Why:
Write-off is not the same as discount and must be reported separately.

#### `billing_deposits`

Purpose:
Track inpatient or advance deposits before final invoice settlement.

Why:
Deposits are operationally different from normal invoice payments.

#### `billing_document_sequences`

Purpose:
Store sequence rules for invoice numbers, receipt numbers, credit notes, and insurer invoice codes.

Why:
Production finance teams need predictable, controlled numbering rather than ad hoc random references.

---

## Required Changes To Existing Tables

### `visit_billings`

Add or strengthen:

- `invoice_number` generation strategy
- `previous_balance`
- `tax_total` if tax applies
- `deposit_applied`
- `written_off_amount`
- `copay_amount` if patient and insurer portions must be shown separately
- better status lifecycle if needed

### `visit_charges`

Add or strengthen:

- `charge_master_id`
- `discount_amount` at line level if line discounting is needed
- `tax_amount`
- clearer reversal or cancellation linkage
- optional insurer amount versus patient amount if mixed billing is needed later

### `payments`

Add or strengthen:

- keep `payment_method_id` required for production payment capture
- `received_by`
- `cashier_shift_id` if shift reconciliation is planned
- `currency_id`, `foreign_amount`, `exchange_rate` if foreign-currency collection matters
- stronger refund metadata

### `payment_methods`

Add or strengthen:

- cash or bank control account mapping once the accounting ledger exists
- branch till or cashier-shift rules
- external reference validation rules by method type
- reconciliation grouping for cash, card, mobile money, transfer, cheque, and other methods

### `charge_masters`

Add or strengthen:

- department and revenue-category mapping
- full support for drugs, lab tests, procedures, imaging, bed charges, and miscellaneous charges
- date-effective price versions for all billable item types
- approval or audit trail for tariff changes
- accounting revenue account mapping once the accounting ledger exists

### `insurance_company_invoices`

Add or strengthen:

- `claim_count`
- `submission_date`
- `sent_at`
- `reference_number`
- `notes`

### `insurance_company_invoice_payments`

Add or strengthen:

- payment method
- reference number
- remittance number
- allocation status

Implemented baseline:

- `RecordInsuranceCompanyInvoicePayment` records insurer invoice payments.
- Payments require explicit claim allocations.
- Claim allocations update claim `paid_amount` and claim status.
- Invoice `paid_amount` and status are recalculated from recorded payments.

---

## Billing Statuses

At minimum, the module should support these concepts:

- `pending`
- `partial_paid`
- `fully_paid`
- `insurance_pending`
- `invoiced`
- `partially_settled`
- `written_off`
- `refunded`
- `cancelled`

Claim-specific statuses should be separated from visit billing status:

- `open`
- `ready_for_invoice`
- `invoiced`
- `submitted`
- `partially_paid`
- `paid`
- `rejected`
- `disputed`

---

## Core Workflows

### Cash patient workflow

1. Visit starts with payer type `cash`.
2. Billable events create `visit_charges`.
3. `visit_billings` recalculates gross and balance.
4. Cashier collects one or more payments.
5. Receipt is issued for each payment.
6. If balance remains, visit becomes a debtor item.

### Insured patient workflow

1. Visit starts with payer type `insurance`.
2. Billable events create `visit_charges` using insurer package pricing where available.
3. `visit_billings` reflects visit totals.
4. `insured_visit_claims` accumulates the insurer-facing claim.
5. Co-pay may be collected from the patient if required.
6. Billing officer batches eligible claims into `insurance_company_invoices`.
7. Insurer payment is recorded and allocated back to claims.
8. Any unpaid remainder continues aging as insurer receivable.

### Debtor workflow

1. Visit closes with positive patient balance.
2. Billing appears in debtor list.
3. New payments can still be collected after visit closure.
4. If irrecoverable, write-off is approved and recorded.

### Inpatient workflow

1. Deposit may be collected before or during admission.
2. Bed and ancillary charges accumulate during stay.
3. Discharge produces final consolidated bill.
4. Deposit is applied.
5. Remaining balance is collected, carried, or written off according to policy.

---

## Production User Stories

### Cashier

As a cashier, I need to open a visit and immediately see every charge already priced so I can collect payment without recalculating anything manually.

As a cashier, I need to accept multiple payment methods and print a receipt for each transaction so end-of-day reconciliation is clean.

As a cashier, I need to take a partial payment and leave the balance outstanding so the patient can continue the process without losing billing history.

As a cashier, I need refunds and discounts to require a reason and appear in audit history so I am protected during reconciliation.

### Billing Officer

As a billing officer, I need to see all insured visits that are ready for insurer billing so I can generate batched claims without spreadsheets.

As a billing officer, I need insurer payments to allocate back to individual visits so I can track what is paid, underpaid, disputed, or still pending.

### Finance Manager

As a finance manager, I need to see yesterday's collections by cashier, method, and branch so I can reconcile quickly.

As a finance manager, I need discount, refund, and write-off visibility by approver so revenue leakage is visible.

As a finance manager, I need patient and insurer aging reports so overdue receivables do not disappear into operational noise.

### Receptionist

As a receptionist, I need to choose cash or insurance at registration and attach the correct insurer package so the rest of the visit bills correctly from the start.

As a receptionist, I need to be warned about previous balances so staff can address debt consistently at arrival.

### Doctor

As a doctor, I need to see whether the patient is cash or insured and whether there is an outstanding balance so I understand the financial context of care.

### Patient

As a patient, I need a clear itemized invoice and receipt so I understand what I am paying for and what remains unpaid.

---

## Document Requirements

The billing module should be able to produce:

- patient receipt
- patient invoice
- debit note if needed later
- refund receipt
- insurer invoice
- insurer payment receipt or remittance acknowledgement
- debtor statement

Each document should show:

- facility and branch identity
- patient identity and visit reference where relevant
- document number
- line items and totals
- payment or claim references
- actor and timestamp where operationally useful

---

## Permissions

At minimum, separate permissions should exist for:

- view billing
- create or sync charges
- collect patient payment
- print receipt
- refund payment
- apply discount
- approve discount
- create write-off
- approve write-off
- manage payment methods
- manage charge catalog
- manage insurer claims
- generate insurer invoices
- record insurer payments
- view billing reports
- export billing reports

---

## Recommended Implementation Order

1. Keep the current visit-billing core and stabilize it as the single visit billing anchor. Done.
2. Add `charge_masters` and link charges to a governed tariff source. Partially done.
3. Add `payment_methods` and move away from free-text payment methods. Done for OPD payments.
4. Add `billing_discounts` and approval workflow.
5. Add `insured_visit_claims` and claim lifecycle tracking.
6. Add insurer payment allocation detail with `insurance_claim_allocations`.
7. Add debtor management views and write-off workflow.
8. Add `billing_deposits` for inpatient use.
9. Add controlled document numbering.
10. Expand reporting and reconciliation views.
11. Connect billing events to the future accounting ledger described in `accounting.md`.

Pre-authorisation should come after the above, not before.

---

## Implementation Progress

### Current status snapshot

| Area | Status | Notes |
|---|---|---|
| Visit billing anchor (`visit_billings`) | Done | Single visit billing header is active and still the core anchor. |
| Charge ledger (`visit_charges`) | Done | Lab, facility service, consultation, and prescription creation now sync charges. |
| Tariff governance (`charge_masters`) | Partial | Facility services are governed; drugs and consultation tariffs still rely on service and item catalogs rather than a full governed charge catalog. |
| Payer setup at registration | Done | Visit-level cash or insurance setup already exists. |
| Visit profile billing UI | Partial | Read-only payer and paid/unpaid state remain on visit profile; cashier actions were intentionally removed. |
| Finance cashier queue | Partial | Incoming OPD payments queue and payment desk exist, but broader cashier workflows like refunds and discount approvals are still missing. |
| Patient payment collection | Partial | Payment capture, receipts, and partial settlement exist; refund and write-off governance are not complete. |
| Consultation charging | Done | Consultation records now carry a `consultation_type`, and branch admins manage explicit consultation tariff mappings by visit type and consultation type. |
| Prescription charging | Partial | Prescription creation now raises visit charges from drug pricing, but downstream dispense reconciliation and substitution adjustments are still pending. |
| Pharmacy POS billing | Partial | Walk-in sales, payments, receipts, refunds, voids, and stock movements exist, but POS payments are not yet tied to the branch payment-method master or accounting ledger. |
| Procurement and inventory cost capture | Partial | Purchase orders, goods receipts, batches, and stock movements exist, but supplier invoices and accounts payable accounting are not implemented. |
| Insurance claim lifecycle | Partial | `insured_visit_claims` now stores one visit-level claim per insured billing, with claim reference, payer, package, claim amounts, and lifecycle status. Ready claims can now be batched into `insurance_company_invoices`, and insurer payments can be allocated to claims; submission, rejection, dispute, and UI workflows remain pending. |
| Insurer invoice batching | Partial | `GenerateInsuranceCompanyInvoice` creates insurer invoices from ready claims, freezes those claims as invoiced, and audits the batch. A finance UI and submission/export workflow are still pending. |
| Insurer remittance allocation | Partial | `RecordInsuranceCompanyInvoicePayment` records insurer payments, requires claim-level allocations, updates claim paid status, updates invoice paid status, and audits the remittance. A finance UI, remittance import, and exception handling are still pending. |
| Discount governance | Partial | `billing_discounts` now records requested, approved, and reversed discounts, and billing totals recalculate from approved discount records; finance UI and reports are still missing. |
| Debtor management and write-offs | Not done | Balance exists, but the operational debt workspace and write-off controls are still missing. |
| Deposit workflows | Not done | No inpatient deposit model yet. |
| Document sequencing | Not done | Receipt numbering exists, but controlled finance document sequencing is not complete. |
| Reporting and reconciliation | Partial | Finance queue and some revenue reporting exist; production-grade finance reporting is still missing. |
| General accounting ledger | Not done | There is no chart of accounts, journal entry table, accounting period close, or automatic double-entry posting yet. |

- [x] Milestone 1: Keep the current visit-billing core and stabilize it as the single visit billing anchor.
  Current visit billing, charge syncing, recalculation, and payment recording remain the anchor pattern for the module.
- [x] Milestone 2: Add `charge_masters` and link charges to a governed tariff source.
  `charge_masters` now exists, billable facility services now sync to charge master records, and facility-service visit charges now store `charge_master_id`.
- [x] Milestone 3: Add `payment_methods` and move away from free-text payment methods.
  `payment_methods` now exists, branch defaults are auto-provisioned, visit payment capture now validates `payment_method_id`, and payments now store the FK while still preserving the string snapshot.
- [x] Milestone 4: Add `billing_discounts` and approval workflow.
  `billing_discounts` now exists with pending, approved, and reversed states. Billing recalculation derives `visit_billings.discount_amount` from approved discount records, and actions now request, approve, reverse, and audit discount decisions.
- [x] Milestone 5: Add `insured_visit_claims` and claim lifecycle tracking.
  `insured_visit_claims` now exists and is automatically synced from insured visit billing while the claim is open or ready for invoice. Claims are frozen from automatic amount rewrites once they move into later lifecycle states such as invoiced or submitted.
- [x] Milestone 6: Add insurer payment allocation detail with `insurance_claim_allocations`.
  `insurance_claim_allocations` now exists, ready insured claims can be batched into `insurance_company_invoices` through `GenerateInsuranceCompanyInvoice`, and insurer remittances can be recorded through `RecordInsuranceCompanyInvoicePayment` with explicit claim-level allocation rows and paid-status updates.
- [ ] Milestone 7: Add debtor management views and write-off workflow.
- [ ] Milestone 8: Add `billing_deposits` for inpatient use.
- [ ] Milestone 9: Add controlled document numbering.
- [ ] Milestone 10: Expand reporting and reconciliation views.

### Current consultation charging rule

Consultation billing is now driven by a dedicated `consultation_tariffs` registry.

Each tariff row defines:

- active branch
- consultation type
- optional visit type scope
- linked billable facility service

When a consultation starts, the system stores the selected `consultation_type` on the consultation record, then resolves the best consultation tariff for:

- the visit branch
- the consultation type
- the exact visit type first
- otherwise a branch-level fallback tariff for that consultation type where visit type scope is left open

That removes the previous magic-code lookup and makes consultation charging an explicit, admin-managed billing rule.

---

## Keep, Rework, Discard

### Keep

- visit-based billing anchor
- charge posting through Action classes
- insurance package pricing
- payment recording with audit activity
- insurer invoice header and payment tables

### Rework

- reporting depth
- insurer claim lifecycle
- discount control
- debt control
- payment-method governance
- document numbering
- billing UI for cashier, billing officer, and finance workflows

### Discard if necessary

- any assumption that the current billing header alone is enough for insurance claims
- any reliance on free-text payment methods for production control
- any workflow that requires spreadsheets outside the system
- any future pre-authorisation design in the current doc, since it is explicitly deferred

---

## Final Position

The current app already has real billing bones: visit payer setup, visit billing headers, visit charges, patient payments, insurance package pricing, insurer invoice tables, and billing actions that sync and recalculate money values.

That is enough to build on.

What it does not yet have is the full production billing operating model for hospital use: controlled tariffs, claim lifecycle management, debtor workflows, discount and write-off governance, deposit handling, payment-method control, and manager-grade reporting.

That is the gap this billing blueprint is meant to close.
