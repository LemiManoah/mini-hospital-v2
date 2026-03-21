# QrooEMR Billing Schema Draft

**Date:** March 20, 2026  
**Scope:** Phase 8.1 billing foundations for visit-based outpatient billing.

---

## 1) Design Choice

QrooEMR should adopt the strong parts of the production billing design in [billing.md](c:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\billing.md), but keep the first slice simpler:

- use the visit as the billing anchor
- keep one visit billing summary row
- keep charge rows separate from the summary
- keep payment logs separate from the summary
- keep insurer batching on the existing insurer invoice layer

This means:

- `visit_billings` is the running billing summary for one visit
- `visit_charges` stores each billable line added during care
- `payments` stores payment and refund events against a visit billing

---

## 2) Proposed Tables

## `visit_billings`

One summary row per patient visit.

### Purpose

- fast balance lookup
- visit-level billing status
- direct connection between visit workflow and money owed

### Key Fields

- `patient_visit_id`
- `visit_payer_id`
- `payer_type`
- `insurance_company_id`
- `insurance_package_id`
- `invoice_number`
- `gross_amount`
- `discount_amount`
- `paid_amount`
- `balance_amount`
- `status`
- `billed_at`
- `settled_at`

### Notes

- `patient_visit_id` should be unique
- insurer batching can later link from visit billing to `insurance_company_invoices`

## `visit_charges`

Immutable or append-only charge lines belonging to a visit billing.

### Purpose

- preserve exactly what was billed
- freeze quantity and price at charge time
- link charges back to operational records

### Key Fields

- `visit_billing_id`
- `patient_visit_id`
- `source_type`
- `source_id`
- `charge_code`
- `description`
- `quantity`
- `unit_price`
- `line_total`
- `status`
- `charged_at`

### Source Examples

- appointment
- lab request
- imaging request
- prescription
- facility service order
- manual charge

## `payments`

Visit-level payment log for direct payment collection and refunds.

### Purpose

- keep payment history separate from billing totals
- support receipts
- support refunds and reversals later

### Key Fields

- `visit_billing_id`
- `patient_visit_id`
- `receipt_number`
- `payment_date`
- `amount`
- `payment_method`
- `reference_number`
- `is_refund`
- `notes`

### Notes

- insurer remittance can stay on `insurance_company_invoice_payments`
- this first table is for direct visit settlement history

---

## 3) Status Strategy

## `visit_billings.status`

Reuse [BillingStatus.php](c:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Enums\BillingStatus.php):

- `pending`
- `partial_paid`
- `fully_paid`
- `insurance_pending`
- `waived`
- `refunded`
- `written_off`

## `visit_charges.status`

Use a new lightweight enum:

- `active`
- `cancelled`
- `refunded`

This keeps charge lifecycle separate from invoice settlement status.

---

## 4) Why This Fits The Existing App

It fits current code because:

- visits already exist in [PatientVisit.php](c:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Models\PatientVisit.php)
- payer ownership already exists in [VisitPayer.php](c:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Models\VisitPayer.php)
- insurer invoice batching already exists in [InsuranceCompanyInvoice.php](c:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Models\InsuranceCompanyInvoice.php)

So the new schema fills the missing middle:

- visit receives charges
- visit billing tracks balance
- payments reduce balance
- insurer invoices can batch eligible insurance-backed balances later

---

## 5) Recommended First Charge Sources

For the first implementation slice, generate charges from:

1. facility service orders
2. lab requests
3. imaging requests

Then later add:

4. prescriptions
5. appointment-related charges
6. manual cashier charges

---

## 6) What We Are Deliberately Not Doing Yet

- full accounting journals
- discount ledger/history tables
- debtor carry-forward
- insurer remittance allocation into individual visit claims
- refund orchestration across already-paid charge lines

Those can come after the billing foundation is stable.
