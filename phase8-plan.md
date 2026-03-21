# Phase 8.1: Billing Foundations

**Date:** March 20, 2026  
**Goal:** Turn the existing OPD workflow into a billable workflow by introducing visit charges, visit billings, and payments in a way that fits the current architecture.

---

## 1) Why This Is Next

The application already supports:

- patient registration
- visit creation
- appointments and check-in
- triage
- consultations
- consultation-linked orders

What it does not yet support is charging, invoicing, and payment capture around that care journey. That makes billing the clearest next milestone after Phase 2.

---

## 2) Scope

Phase 8.1 should focus on the billing foundation only, not full finance/compliance breadth.

### In Scope

- `visit_charges`
- `visit_billings`
- `payments`
- price freezing at charge time
- basic billing states on the visit
- the first cashier/billing surface for viewing what is owed and what is paid

### Out Of Scope For This Slice

- claims adjudication
- insurer remittance workflows
- advanced accounting
- refund handling
- full audit/compliance reporting

---

## 3) Recommended Build Order

1. define the billing domain records
2. create migrations and models for charges, billings, and payments
3. attach charge generation to the first real workflow events
4. add a billing summary to the visit workspace
5. add a simple cashier/billing page
6. update docs and identify the next billing slice

---

## 4) Concrete First Slice

## Slice 8.1.1: Visit Charges + Billing Summary

### Deliverables

- `visit_charges` model and migration
- `visit_billings` model and migration
- initial charge-generation rules
- visit-level billing totals
- billing summary on the visit page

### Suggested First Charge Sources

- appointment check-in if the visit type is billable
- facility service orders
- lab requests
- imaging requests

### Definition Of Done

- a visit can accumulate charge rows
- each charge stores a frozen unit price and total
- a visit can display total billed amount and payment balance
- the billing shape is compatible with later payment capture

---

## 5) After 8.1.1

The next slice should be:

## Slice 8.1.2: Payments + Cashier Workflow

- payment entry
- partial and full settlement
- payment method capture
- visit balance updates
- cashier-facing screens and permissions

---

## 6) Bottom Line

Phase 2 is strong enough now that billing is the highest-value next milestone. Start with charge records and visit billing summaries, then add payments and cashier flows on top of that.
