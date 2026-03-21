# Billing System

## Overview

This application uses an encounter-based billing system for a clinic or hospital.

The billing flow is centered on a `ClientVisit`. Every billable action during a visit adds value to either:

- `visit_payments` for self-paying patients
- `insured_visit_payments` for insured patients

The system is not built around subscriptions or recurring invoices. It is built around:

- visit-level charging
- double-entry accounting
- payment collection
- receipt generation
- debt tracking
- insurance invoice batching

## Core Idea

The main design is:

1. A patient starts a visit.
2. Services, lab orders, prescriptions, consumables, and some inpatient charges are added during that visit.
3. Every new billable item creates accounting entries and increases the visit invoice total.
4. Payments reduce the visit balance and create a payment log with a receipt number.
5. If a visit is closed with a remaining balance, that invoice becomes a debtor record.
6. If the visit is insured, claims are accumulated first, then later grouped into an insurance company invoice.

## Main Billing Modes

The app uses two billing modes defined in `App\Enum\BillType`:

- `personal`
- `insured`

That choice affects both the receivable owner and the invoice table used:

- `personal` bills the patient directly and stores totals in `VisitPayment`
- `insured` bills the insurance company and stores totals in `InsuredVisitPayment`

## Main Tables

### `visit_payments`

This is the personal-patient invoice table.

Important fields:

- `invoice_number`
- `client_visit_id`
- `date`
- `bill_amount`
- `paid_amount`
- `previous_balance`
- `discount`

This record is the running invoice summary for one visit.

Balance is calculated as:

`bill_amount - (paid_amount + discount)`

### `visit_payment_logs`

This stores each personal payment or refund event against a visit invoice.

Important fields:

- `payment_date`
- `visit_payment_id`
- `receipt`
- `paid_amount`
- `is_clearing_debt`

This gives you an auditable payment history separate from the invoice summary.

### `insured_visit_payments`

This is the running claim total for insured visits.

Important fields:

- `invoice_id`
- `insurance_company_id`
- `client_visit_id`
- `date`
- `bill_amount`
- `paid_amount`

Before insurer invoicing, records can exist without `invoice_id`.

### `insurance_company_invoices`

This is the batched invoice sent to an insurer.

Important fields:

- `code`
- `insurance_company_id`
- `start_date`
- `end_date`
- `due_date`
- `bill_amount`
- `paid_amount`

One insurance invoice can cover many insured visit payment records.

### `insurance_company_invoice_payments`

This stores each payment made by an insurance company against a grouped insurer invoice.

Important fields:

- `payment_date`
- `insurance_company_invoice_id`
- `receipt`
- `paid_amount`

### `discounts`

This stores discount history for personal visit invoices.

The visit invoice still keeps the running `discount` total, but this table preserves the individual discount records and references.

## Personal Billing Flow

For self-paying patients, the patient ledger is the receivable account.

When a billable item is added:

1. The system finds or creates the patient ledger.
2. It debits the patient receivable.
3. It credits the relevant revenue account.
4. It creates or updates the visit invoice in `visit_payments`.

This pattern is used in:

- `App\Services\ClientOrders`
- `App\Services\PrescriptionService`
- `App\Livewire\Patients\UsedConsumablesComponent`
- inpatient discharge billing through `ClientOrders::handleAccountingOnInpatientDischarge()`

## Insured Billing Flow

For insured visits, the receivable is moved from the patient to the insurance company ledger.

When a billable item is added:

1. The system uses the patient visit's insurance package.
2. It gets the insurance company ledger.
3. It debits that company receivable.
4. It credits the appropriate revenue account.
5. It creates or updates the visit claim summary in `insured_visit_payments`.

No patient receipt is collected at that point. Payment happens later when the insurance company pays the grouped invoice.

## How Charges Are Added

### Service and lab orders

`App\Services\ClientOrders::handleAccountingOnOrder()` is the main service-order billing method.

It:

- creates the receivable/revenue journal entries
- updates `VisitPayment` for personal visits
- updates `InsuredVisitPayment` for insured visits

This is used when consultations, lab requests, radiology, procedures, and other service orders are created.

### Prescriptions

`App\Services\PrescriptionService::handleAccountingOnPrescription()` handles pharmacy billing.

It:

- debits the patient or insurer receivable
- credits `Pharmacy Revenue`
- updates the correct visit-level invoice table

### Consumables

`App\Livewire\Patients\UsedConsumablesComponent` handles billed consumables.

It does two separate accounting actions:

- stock movement accounting:
  - debit `Cost of Items Sold`
  - credit `Stock/Inventory`
- billing accounting if `billed = true`:
  - debit patient or insurer receivable
  - credit `Pharmacy Revenue`

It also updates `VisitPayment` or `InsuredVisitPayment`.

### Previous balance carry-forward

When a returning patient starts a new personal visit, outstanding balance from older ledger activity can be carried forward into the new visit invoice.

This happens in `App\Livewire\Patients\Outpatients\ReturningPatientsComponent`.

The component:

- calculates prior balance from the client ledger
- marks the previous invoice inactive
- adds that amount to the new visit invoice
- stores it in `previous_balance`

This is a useful pattern if you want one active invoice per current visit while still preserving debt continuity.

## How Payments Work

### Personal payments

Personal payments are handled by `App\Services\TransactionsService::handleAccountingOnPayment()`.

The flow is:

1. Determine whether the payment is for an active visit or a closed-visit debt.
2. Create a receipt reference such as `SP...`.
3. Debit the selected payment mode account, usually cash or bank.
4. Credit the patient receivable ledger.
5. Increase `visit_payments.paid_amount`.
6. Insert a row into `visit_payment_logs`.
7. Optionally redirect to a printable receipt.

This is triggered from:

- `App\Livewire\Accounts\Payments\PendingPayments`
- `App\Livewire\Accounts\Debtors`

### Insurance payments

Insurance payments are handled by `App\Services\TransactionsService::handleInsurancePayment()`.

The flow is:

1. Debit the selected cash or bank account.
2. Credit the insurance company ledger.
3. Increase `insurance_company_invoices.paid_amount`.
4. Insert a row into `insurance_company_invoice_payments`.
5. Distribute the received amount across the linked `insured_visit_payments` records until the amount is exhausted.

This is triggered from `App\Livewire\Accounts\Insurance\Invoices`.

## How Insurance Invoices Are Generated

Insurance claim batching is handled in `App\Livewire\Accounts\Insurance\Invoices::generateInvoice()`.

The logic is:

1. Select one insurance company.
2. Pick a date range and due date.
3. Fetch `insured_visit_payments` where:
   - `invoice_id` is null
   - company matches
   - visit date falls in the selected range
4. Create one `insurance_company_invoices` record with the summed `bill_amount`.
5. Update each claim row so its `invoice_id` points to the new insurer invoice.

That separation is important. Individual insured visit claims are collected first, then grouped later for external billing.

## Discounts

Discounts are applied only to personal invoices in the current implementation.

`App\Livewire\Accounts\Payments\PendingPaymentDetail` handles this.

When a discount is added:

1. Debit `Sales Discounts`
2. Credit the patient ledger
3. Increase `visit_payments.discount`
4. Insert a row in `discounts`

This preserves both:

- the running invoice total
- the audit history of each discount event

Discount reversal creates the opposite journal entries and reduces the invoice discount total.

## Refunds and Order Reversals

Refunds are handled by `App\Services\GeneralServices::recordRefund()`.

The pattern is:

1. Debit the patient receivable to restore what is owed
2. Credit the cash or bank account used for the refund
3. Reduce `visit_payments.paid_amount`
4. Insert a `visit_payment_logs` row flagged as a refund

Order deletion or quantity reduction is handled separately for each billing source:

- `OrderServices` for lab and other service deletions
- `PrescriptionService` for prescription reductions or deletion

Those services reverse revenue/receivable entries and lower the visit invoice total.

If the removed item had already been paid for, the component first checks whether a refund is required before removing the charge.

## Debtors

Debtors are not stored in a separate billing table.

A debtor is effectively:

- a personal `VisitPayment`
- whose visit is `Closed`
- whose `bill_amount > paid_amount + discount`

That list is shown by `App\Livewire\Accounts\Debtors`.

This is a nice design because debt status is derived from invoice state instead of duplicated.

## Receipts and Printable Invoices

The printable documents are handled by `App\Http\Controllers\ReceiptController` and `App\Http\Controllers\InsuranceCompanyInvoiceController`.

Main outputs:

- personal payment receipt
- personal invoice print view
- insurance invoice print view
- insurance receipt

The personal invoice and receipt are itemized from related visit records:

- `lab_orders_private`
- `other_orders_private`
- `prescriptions_private`
- billed consumables
- previous balance

That means the invoice detail page is reconstructed from operational records, while the totals come from the summary invoice row.

## UI Entry Points

Main routes:

- `accounts/individual/pending-payments`
- `accounts/individual/payment-receipts`
- `accounts/individual/debtors`
- `accounts/individual/pending-payments-details/{id}`
- `accounts/insured/pending-payments`
- `accounts/insured/invoices`
- `accounts/insured/invoices/{id}`
- `accounts/insured/company-invoice/{id}`
- `accounts/insured/insurance-receipt/{id}`

## Why This Design Works Well

This billing system works because it separates four concerns clearly:

- operational records:
  - visits, orders, prescriptions, consumables
- invoice summaries:
  - `visit_payments`, `insured_visit_payments`, `insurance_company_invoices`
- payment history:
  - `visit_payment_logs`, `insurance_company_invoice_payments`
- accounting journals:
  - `transactions`

That separation makes it easier to:

- rebuild invoice detail screens from source records
- keep running balances fast to query
- audit every payment and reversal
- report accounting independently from clinical workflow

## How To Reuse This In Your Own App

If you want to copy this architecture into your own application, keep these ideas:

### 1. Make the visit or order your billing anchor

Use one parent record such as:

- patient visit
- appointment
- admission
- work order
- project job

All billable items should point back to that anchor.

### 2. Keep a summary invoice table

Do not recalculate total bill and paid amount from scratch every time.

Maintain a summary table like:

- `bill_amount`
- `paid_amount`
- `discount`
- `previous_balance`

Then derive balance from that summary.

### 3. Keep payment logs separate from the invoice

The invoice row should hold totals.

A separate payment log table should hold:

- each payment
- each receipt number
- each refund
- reversal history

### 4. Post accounting at the same moment the bill changes

Whenever a charge is added, reduced, discounted, paid, or refunded:

- write the business record
- write the accounting transaction
- update the invoice summary

Do all of that in one database transaction.

### 5. Split personal and third-party billing early

If you support insurance, corporate billing, sponsors, or agencies, decide early who owns the receivable:

- the patient
- the insurer
- the company

That one choice determines which ledger is debited.

### 6. Batch third-party claims into external invoices

Do not send every insured visit as a separate invoice.

Store visit-level claims first, then later group them into a company invoice with:

- date range
- due date
- invoice code
- total amount

### 7. Model debt as unpaid balance, not a separate module

A debtor can simply be an invoice whose balance is still greater than zero after closure.

That keeps your design simpler.

## Key Files

- `app/Services/ClientOrders.php`
- `app/Services/PrescriptionService.php`
- `app/Services/TransactionsService.php`
- `app/Services/GeneralServices.php`
- `app/Livewire/Accounts/Payments/PendingPayments.php`
- `app/Livewire/Accounts/Payments/PendingPaymentDetail.php`
- `app/Livewire/Accounts/Payments/PaymentReceipts.php`
- `app/Livewire/Accounts/Debtors.php`
- `app/Livewire/Accounts/Insurance/PendingPayments.php`
- `app/Livewire/Accounts/Insurance/Invoices.php`
- `app/Livewire/Patients/Outpatients/ReturningPatientsComponent.php`
- `app/Livewire/Patients/UsedConsumablesComponent.php`
- `app/Http/Controllers/ReceiptController.php`
- `app/Http/Controllers/InsuranceCompanyInvoiceController.php`
- `app/Models/VisitPayment.php`
- `app/Models/VisitPaymentLog.php`
- `app/Models/InsuredVisitPayment.php`
- `app/Models/InsuranceCompanyInvoice.php`
- `app/Models/InsuranceCompanyInvoicePayment.php`

## Implementation Notes

- Personal invoice numbers are generated from visit IDs using `1000000 + visit_id`.
- Insurance invoice codes are currently timestamp-based.
- Discounts are implemented for personal billing, not insurer billing.
- Payment mode selection is used as the debit cash or bank ledger.
- Receipt printing is optional at payment time through a `pay_and_print` flag.
- The system already supports multi-currency payment entry for personal payments, then converts to branch base currency for accounting.

## Recommended Improvements If You Rebuild It

If you are implementing this in a fresh app, I would keep the same structure but improve a few things:

- use decimal columns instead of double for money
- add explicit foreign keys for every invoice reference
- store discount amount directly in the `discounts` table instead of parsing it from text later
- store reversal reason on the reversed payment record itself
- make receipt references and invoice codes use dedicated sequence generators
- wrap all charge-creation flows in explicit service classes for consistency

Even with those improvements, the overall design in this project is solid: charges build up per visit, accounting is posted immediately, payments are logged separately, and insurance billing is batched cleanly.
