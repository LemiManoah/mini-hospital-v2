# Billing Milestone Implementation Plan

## Current Billing Milestones

The remaining billing blueprint work after OPD payments, discounts, insured claims, insurer invoices, remittance allocation, debtors, and write-offs was:

1. Add `billing_deposits` for inpatient or advance deposits. Implemented.
2. Add controlled document numbering. Implemented for patient receipts, deposits, and insurer invoices.
3. Expand reporting and reconciliation views. Implemented as an operational finance summary.
4. Prepare billing events for the future accounting ledger.

## Milestone 8: Billing Deposits

Goal:
Track advance money separately from normal invoice payments until finance applies it to a visit bill or refunds it.

Implementation steps:

1. Create a `billing_deposits` table.
   Required fields:
   - tenant and branch
   - patient and optional visit/billing links
   - deposit number
   - payment method and reference
   - amount, applied amount, refunded amount
   - status: held, partially_applied, applied, refunded, cancelled
   - received date, notes, audit users

2. Create `BillingDepositStatus` enum and `BillingDeposit` model.

3. Add deposit actions:
   - `RecordBillingDeposit`
   - `ApplyBillingDeposit`

4. When a deposit is applied, create a normal `payments` row using payment method snapshot `deposit`.
   This keeps `visit_billings.paid_amount` and `balance_amount` using the same existing recalculation path.

5. Add a finance deposit workspace:
   - list deposits by branch
   - show held, partially applied, applied, and refunded totals
   - apply held deposit balance to a visit billing

6. Add audit events:
   - `deposit.recorded`
   - `deposit.applied`

You can implement it yourself by following the exact pattern already used by `RecordVisitPayment`, `RequestBillingWriteOff`, and `FinanceDebtorController`.

Implemented files:
- `billing_deposits` migration, `BillingDeposit` model, and `BillingDepositStatus` enum.
- `RecordBillingDeposit` and `ApplyBillingDeposit` actions.
- Finance & Accounting > Deposits page for recording and applying deposits.
- Deposit unit coverage in `tests/Unit/BillingDepositWorkflowTest.php`.

## Milestone 9: Controlled Document Numbering

Goal:
Stop using random suffixes for finance documents where predictable controlled sequences are needed.

Implementation steps:

1. Create a `billing_document_sequences` table.
   Required fields:
   - tenant and optional branch
   - document type
   - prefix
   - next number
   - padding
   - reset period: never, yearly, monthly, daily
   - current period key

2. Create `BillingDocumentSequence` model and `BillingDocumentType` enum.

3. Create `GenerateBillingDocumentNumber` action.
   It must:
   - lock the sequence row
   - create default sequence if missing
   - increment atomically inside a transaction
   - return a formatted number

4. Use the generator for:
   - patient receipt numbers
   - billing deposits
   - insurer invoice numbers

5. Keep legacy generated numbers valid. Do not rewrite existing rows.

Implemented files:
- `billing_document_sequences` migration.
- `BillingDocumentSequence` model.
- `BillingDocumentType` and `BillingSequenceResetPeriod` enums.
- `GenerateBillingDocumentNumber` action.
- Receipt, deposit, and insurer invoice generation now use controlled sequences.

## Milestone 10: Reporting and Reconciliation Views

Goal:
Give finance a broader operational view without waiting for the future general ledger.

Implementation steps:

1. Build a finance summary report action.
   Include:
   - gross visit charges
   - patient payments
   - discounts
   - write-offs
   - deposits held and applied
   - debtor balance
   - insurer invoices billed and paid

2. Add a finance summary page under Finance & Accounting.

3. Add branch and date filters.

4. Keep this report operational. Once the accounting ledger exists, management financial statements should come from accounting tables instead.

Implemented files:
- `GenerateFinanceBillingSummary` action.
- `FinanceBillingSummaryController`.
- Finance & Accounting > Billing Summary page with date filters, operational billing totals, collections by method, deposit status totals, and insurer invoice position.

## Milestone 11: Accounting Ledger Preparation

Goal:
Prepare billing actions to post into accounting later without coupling billing to ledger internals today.

Implementation steps:

1. Add accounting event outbox tables after the chart of accounts design is approved.
2. Emit outbox events from billing actions after each money-moving action.
3. Add idempotency keys so a source document cannot post twice.
4. Keep operational billing working even if accounting posting is delayed.

Recommended next module after this file:
Implement the ledger foundation from `accounting.md`, starting with chart of accounts, accounting periods, journal entries, and journal lines.
