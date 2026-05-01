# Accounting Module Plan

## Executive Answer

The app does not yet have a full accounting module.

It has strong operational finance inputs: visit billing, OPD payments, payment methods, pharmacy POS sales, procurement records, goods receipts, inventory batches, stock movements, subscriptions, currencies, exchange rates, audit logs, and daily revenue reporting.

Those are not the same as accounting. Accounting still needs a general ledger, chart of accounts, journal entries, receivables control, payables control, cash and bank reconciliation, period close, and financial statements.

The right direction is to keep the existing operational modules as subledgers, then post their approved events into a central accounting ledger.

---

## Current Financial Foundations In The App

### Implemented or partially implemented

| Area | Current implementation | Accounting value |
|---|---|---|
| OPD billing | `visit_billings`, `visit_charges`, `payments` | Patient revenue, receivables, cash collection |
| Payment methods | `payment_methods` plus `payments.payment_method_id` | Future cash, bank, mobile money, and card reconciliation |
| Insurance setup | `insurance_companies`, `insurance_packages`, `insurance_package_prices` | Future insurer receivables and claim accounting |
| Insurer invoice tables | `insurance_company_invoices`, `insurance_company_invoice_payments` | Data foundation, but workflow is not active yet |
| Pharmacy POS | `pharmacy_pos_sales`, `pharmacy_pos_payments` | Retail revenue and cash collection |
| Inventory | `inventory_batches`, `stock_movements` | Inventory quantity and cost subledger |
| Procurement | `suppliers`, `purchase_orders`, `goods_receipts` | Purchasing and future accounts payable |
| Reporting | Daily revenue, stock, low-stock, appointment schedule | Starting point for finance reports |
| Audit | Activity log and financial event audit usage | Needed for financial control |
| Currency | Currencies and exchange rates | Future multi-currency accounting |

### Missing accounting foundations

- no chart of accounts
- no journal entries or journal lines
- no accounting periods or period close
- no automatic double-entry posting from billing, POS, inventory, procurement, or subscriptions
- no cashbook or bank ledger
- no cashier sessions or till reconciliation
- no accounts receivable ledger for patients, insurers, tenants, or other payers
- no accounts payable ledger for suppliers
- no supplier invoice, supplier payment, debit note, or credit note workflow
- no trial balance, income statement, balance sheet, cashbook, aged receivables, or aged payables
- no tax ledger or tax reporting
- no fixed-asset register
- no payroll accounting

---

## Accounting Design Principle

Do not replace the existing operational tables with accounting tables.

Use the existing modules as source systems:

- `visit_billings` and `payments` remain the OPD billing subledger
- `pharmacy_pos_sales` and `pharmacy_pos_payments` remain the POS subledger
- `stock_movements` remains the inventory movement subledger
- `purchase_orders` and `goods_receipts` remain procurement documents
- future `insured_visit_claims` should become the insurance receivables subledger

Then add a central accounting layer that receives posted events from those modules.

The accounting module should answer:

1. What account was debited?
2. What account was credited?
3. Which operational document caused it?
4. Which branch, tenant, staff member, and period does it belong to?
5. Can finance reverse or adjust it without silent edits?

---

## Target Accounting Tables

### `account_categories`

Purpose:
Define the high-level account families.

Minimum categories:

- asset
- liability
- equity
- revenue
- expense

### `chart_of_accounts`

Purpose:
The tenant-owned account list used by every journal entry.

Important fields:

- tenant
- optional branch
- account code
- account name
- account category
- normal balance: debit or credit
- parent account
- is control account
- is cash or bank account
- is active

Recommended starter accounts:

- Cash on Hand
- Bank
- Mobile Money Clearing
- Card Clearing
- Patient Receivables
- Insurance Receivables
- Tenant Subscription Receivables
- Inventory
- Supplier Payables
- Sales Revenue - OPD
- Sales Revenue - Pharmacy POS
- Consultation Revenue
- Laboratory Revenue
- Pharmacy Revenue
- Discounts Allowed
- Refunds and Reversals
- Write-Off Expense
- Cost of Goods Sold
- Inventory Adjustment Gain or Loss

### `accounting_periods`

Purpose:
Control posting dates and month-end close.

Important fields:

- tenant
- branch scope if needed
- start date
- end date
- status: open, locked, closed
- closed by
- closed at

### `journal_entries`

Purpose:
Accounting document header.

Important fields:

- tenant
- branch
- entry number
- accounting date
- period
- source type
- source id
- source reference
- description
- status: draft, posted, reversed
- posted by
- posted at
- reversed by
- reversed at
- reversal reason

### `journal_entry_lines`

Purpose:
Debit and credit lines.

Important fields:

- journal entry
- account
- debit amount
- credit amount
- currency
- exchange rate
- base amount
- patient, insurer, supplier, payment method, inventory item, or other dimensions where needed
- memo

Rule:
Every posted journal entry must balance exactly: total debits must equal total credits.

### `accounting_event_outbox`

Purpose:
Reliable handoff from operational modules into accounting.

Why:
Billing, POS, inventory, and procurement actions are already transactional. The outbox lets those actions record "this financial event happened" without coupling every action directly to accounting internals.

Important fields:

- tenant
- branch
- event type
- source type
- source id
- payload
- status: pending, posted, failed
- attempts
- last error

### `cashier_sessions`

Purpose:
Cashier shift and till reconciliation.

Important fields:

- tenant
- branch
- cashier
- opening float
- opened at
- closed at
- expected cash
- counted cash
- variance
- status

### `bank_accounts`

Purpose:
Bank and mobile money account setup.

Important fields:

- tenant
- branch
- linked chart-of-account id
- bank name or provider
- account number or till number
- currency
- is active

### `bank_transactions`

Purpose:
Optional imported or manually entered bank statement lines.

Important fields:

- bank account
- transaction date
- value date
- description
- reference
- debit amount
- credit amount
- matched journal entry id
- reconciliation status

### `supplier_invoices`

Purpose:
Accounts payable document created from goods receipts or direct expense capture.

Important fields:

- tenant
- branch
- supplier
- purchase order
- goods receipt
- invoice number
- invoice date
- due date
- gross amount
- tax amount
- paid amount
- balance amount
- status

### `supplier_payments`

Purpose:
Payments made to suppliers.

Important fields:

- supplier invoice
- payment method or bank account
- payment date
- amount
- reference number
- created by

### `accounting_adjustments`

Purpose:
Controlled finance adjustments that are not operational billing events.

Examples:

- opening balances
- bank charges
- interest income
- manual correction journals
- approved accruals

---

## Posting Rules By Source Module

### OPD charge created

When a visit charge is created:

Debit:
Patient Receivables or Insurance Receivables

Credit:
Revenue account mapped from charge master, service category, lab, consultation, or pharmacy category

Source:
`visit_charges`

Current app state:
Charges are created and billing totals recalculate, but no accounting journal is posted yet.

### OPD patient payment received

When a visit payment is recorded:

Debit:
Cash, bank, mobile money, card clearing, or other account mapped from `payment_methods`

Credit:
Patient Receivables

Source:
`payments`

Current app state:
Payments are recorded, audited, and receipted. Accounting posting is missing.

### OPD refund

When a patient payment is refunded:

Debit:
Patient Receivables, Refunds, or Revenue Reversal depending on policy

Credit:
Cash, bank, mobile money, card clearing, or other payment account

Source:
`payments` with `is_refund = true`

Current app state:
Payment model supports refunds, but OPD refund governance is not complete.

### Discount approved

When a discount is approved:

Debit:
Discounts Allowed

Credit:
Patient Receivables

Source:
future `billing_discounts`

Current app state:
Only the running `visit_billings.discount_amount` exists. A governed discount event table is missing.

### Write-off approved

When debt is written off:

Debit:
Write-Off Expense

Credit:
Patient Receivables or Insurance Receivables

Source:
future `billing_write_offs`

Current app state:
Not implemented.

### Insurance claim invoiced

When insured visit claims are batched into an insurer invoice:

Option A:
No new journal if receivable was already posted at charge creation.

Option B:
Move from unbilled insurance receivables to billed insurance receivables.

Source:
future `insured_visit_claims` and `insurance_company_invoices`

Current app state:
Invoice header tables exist, but claim lifecycle and invoice workflow are missing.

### Insurer payment received

When an insurer pays:

Debit:
Cash, bank, or mobile money account

Credit:
Insurance Receivables

Source:
`insurance_company_invoice_payments`

Current app state:
Payment table exists, but workflow and allocation to visit-level claims are missing.

### Pharmacy POS sale

When a POS sale is completed:

Debit:
Cash, bank, mobile money, card clearing, or walk-in customer receivable

Credit:
Pharmacy POS Revenue

Source:
`pharmacy_pos_sales` and `pharmacy_pos_payments`

Current app state:
POS sale and payment records exist, but payment methods are still string-based and no journal is posted.

### Pharmacy POS stock cost

When POS stock is issued:

Debit:
Cost of Goods Sold

Credit:
Inventory

Source:
`stock_movements` with POS sale movement types

Current app state:
Stock movements are posted, but financial inventory accounting is missing.

### Goods receipt posted

When inventory is received:

Debit:
Inventory

Credit:
Goods Received Not Invoiced or Supplier Payables

Source:
`goods_receipts`, `goods_receipt_items`, `inventory_batches`, `stock_movements`

Current app state:
Goods receipts post batches and stock movements. Supplier invoices and accounting entries are missing.

### Supplier invoice recorded

When supplier invoice is approved:

Debit:
Goods Received Not Invoiced, Inventory, or Expense

Credit:
Supplier Payables

Source:
future `supplier_invoices`

Current app state:
Not implemented.

### Supplier payment made

When supplier payment is posted:

Debit:
Supplier Payables

Credit:
Bank, cash, or mobile money

Source:
future `supplier_payments`

Current app state:
Not implemented.

### Inventory adjustment

When stock reconciliation posts:

If stock increases:

Debit:
Inventory

Credit:
Inventory Adjustment Gain

If stock decreases:

Debit:
Inventory Adjustment Loss

Credit:
Inventory

Source:
stock reconciliation and `stock_movements`

Current app state:
Operational stock reconciliation exists. Accounting is missing.

---

## Priority And Implementation Plan

### Priority 1: Accounting foundation

Importance:
Critical. Everything else depends on this.

Build:

- account categories
- chart of accounts
- accounting periods
- journal entries
- journal lines
- posting validation that debits equal credits
- opening balance support
- tenant and branch isolation
- permissions for accountant and admin roles

Deliverable:
Finance can configure accounts and post a manual balanced journal.

### Priority 2: Payment method to cash/bank control

Importance:
Critical for cashier reconciliation.

Build:

- map each payment method to a chart-of-account cash, bank, card, or mobile money account
- require reference numbers based on payment method rules
- add cashier sessions for cash tills
- add daily close with expected versus counted cash

Deliverable:
Every OPD payment can be assigned to a real cash or bank control account.

### Priority 3: OPD billing accounting integration

Importance:
Critical because OPD billing is already live enough to collect money.

Build:

- accounting event outbox
- post journal entries when `visit_charges` are created or reversed
- post journal entries when `payments` are recorded
- post refund journals
- prevent duplicate posting per source document
- show accounting status on finance payment screens

Deliverable:
OPD charges and payments produce balanced journals.

### Priority 4: Discount, refund, and write-off governance

Importance:
High. This protects revenue and controls leakage.

Build:

- `billing_discounts`
- discount approval workflow
- discount reversal workflow
- `billing_write_offs`
- write-off approval workflow
- refund approval workflow for OPD payments
- reports by approver, reason, branch, and date

Deliverable:
Revenue reductions become approved, auditable accounting events.

### Priority 5: Insurance receivables

Importance:
High. Insurance is already modeled at registration and pricing, but claims are not operationally complete.

Build:

- `insured_visit_claims`
- claim statuses
- claim batching into insurer invoices
- remittance allocation to claims
- insurer payment posting
- insurer receivables aging
- disputed and rejected claim handling

Deliverable:
Insurance visits can move from charge capture to claim, invoice, payment, allocation, and aging.

### Priority 6: Pharmacy POS accounting

Importance:
High because POS directly touches cash and inventory.

Build:

- connect POS payments to `payment_methods`
- post POS revenue journals
- post POS cost-of-goods-sold journals from stock allocations
- post POS refund and void journals
- include POS in cashier sessions and daily cash reconciliation

Deliverable:
Walk-in pharmacy sales are included in finance reports and the general ledger.

### Priority 7: Inventory and procurement accounting

Importance:
High for stock value, supplier liabilities, and cost control.

Build:

- inventory account mapping by item category or location
- goods receipt accounting
- supplier invoice workflow
- supplier payment workflow
- goods-received-not-invoiced control account
- inventory adjustment journals
- stock valuation report

Deliverable:
Inventory quantity movements can be reconciled to inventory value in accounts.

### Priority 8: Accounts receivable workspaces

Importance:
Medium-high. The app already has balances, but finance needs collections control.

Build:

- patient debtor workspace
- insurer receivables workspace
- aged receivables reports
- statement generation
- collection notes and follow-up dates
- bad-debt proposal workflow

Deliverable:
Finance can see who owes money, how old it is, and what action is next.

### Priority 9: Accounts payable workspaces

Importance:
Medium-high. Procurement exists, but supplier accounting does not.

Build:

- supplier invoice entry and approval
- invoice matching against purchase order and goods receipt
- supplier payment scheduling
- supplier statements
- aged payables report

Deliverable:
Finance can track what the hospital owes suppliers.

### Priority 10: Financial statements and close

Importance:
Medium once journals are reliable.

Build:

- trial balance
- general ledger account detail
- income statement
- balance sheet
- cashbook
- period close and lock
- reversal-only corrections in closed periods

Deliverable:
The app can produce management accounts without exporting raw operational data.

### Priority 11: Tax, assets, payroll, and advanced finance

Importance:
Later unless the hospital needs statutory accounting inside the app immediately.

Build later:

- VAT or sales tax rules
- withholding tax
- fixed asset register
- depreciation journals
- payroll accounting imports or native payroll
- budgets
- cost centers
- department profitability

Deliverable:
The accounting module becomes a broader ERP finance layer.

---

## Suggested Phased Build

### Phase 1: Ledger core

Build chart of accounts, accounting periods, manual journals, journal line validation, and account reports.

Expected result:
Accounting can exist independently and prove that journal posting works.

### Phase 2: Cash control

Map payment methods to accounts, add cashier sessions, and reconcile OPD payments.

Expected result:
Cashiers can close a day with expected cash, counted cash, and variance.

### Phase 3: OPD posting

Post visit charges, patient payments, refunds, discounts, and write-offs into journals.

Expected result:
Current visit billing becomes accounting-aware.

### Phase 4: Insurance accounting

Add insured claims, insurer invoice workflow, insurer payments, allocations, and aging.

Expected result:
Insurer receivables become manageable without spreadsheets.

### Phase 5: POS and inventory accounting

Post pharmacy POS revenue, COGS, goods receipts, supplier invoices, supplier payments, and stock adjustments.

Expected result:
Inventory value, pharmacy sales, and supplier liabilities reconcile.

### Phase 6: Reports and close

Add trial balance, general ledger, income statement, balance sheet, cashbook, AR aging, AP aging, and period close.

Expected result:
The app can support monthly management accounts.

---

## Implementation Rules

- Operational modules should not silently edit posted accounting amounts.
- Corrections after posting should create reversals or adjustment journals.
- Every accounting entry should point back to its source document.
- Posting must be idempotent: the same payment or charge cannot post twice.
- Journal entries must be tenant-scoped and branch-aware.
- Closed accounting periods should block normal posting.
- Refunds, discounts, write-offs, voids, and reversals should require reasons and permissions.
- Reports should read from accounting tables once accounting exists, not recalculate finance from operational tables forever.

---

## Most Important Next Step

Build the accounting foundation first:

1. `account_categories`
2. `chart_of_accounts`
3. `accounting_periods`
4. `journal_entries`
5. `journal_entry_lines`
6. manual journal posting
7. account balance report

After that, connect OPD payments and visit charges because those workflows already exist and generate real money events.
