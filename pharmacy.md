# Pharmacy Module Status Review

**Date:** April 25, 2026
**Verdict:** the pharmacy module is operational, but it is **not fully complete**

## Bottom Line

The pharmacy module has moved well beyond scaffolding. It now covers the main working pharmacy flows:

- pharmacy queue
- prescription review
- dispensing draft and post flows
- dispensing history and print
- pharmacy requisitions
- goods receipts under pharmacy routes
- pharmacy POS with cart, checkout, sale history, print, void, refund, and payments

That said, "implemented" is not the same as "complete". The module still has important gaps in consistency, authorization design, maintainability, and final hardening. So the right answer is:

- **operationally meaningful:** yes
- **feature-rich:** yes
- **fully complete and mature:** no

## What Looks Complete

### 1. Pharmacy Queue

**Status:** implemented

What exists:

- `/pharmacy/queue`
- queue filtering by search and status
- remaining quantity and stock-status indicators
- patient, visit, and item summaries
- branch-aware queue behavior

Evidence in code:

- `app/Http/Controllers/PharmacyQueueController.php`
- `app/Support/PrescriptionQueueQuery.php`
- `app/Support/PrescriptionDispenseProgress.php`
- `resources/js/pages/pharmacy/queue.tsx`
- `tests/Feature/Controllers/PharmacyQueueControllerTest.php`

### 2. Pharmacy Prescription Review

**Status:** implemented

What exists:

- `/pharmacy/prescriptions/{prescription}`
- item-level remaining quantity
- availability and stock-position display
- dispensing-location awareness

Evidence in code:

- `app/Http/Controllers/PharmacyPrescriptionController.php`
- `resources/js/pages/pharmacy/prescriptions/show.tsx`
- `tests/Feature/Controllers/PharmacyQueueControllerTest.php`

### 3. Dispensing Workflow

**Status:** implemented

What exists:

- create draft dispense record
- save draft with item snapshots
- post draft to reduce stock
- direct dispense flow
- external-pharmacy completion flow
- print and history views

Evidence in code:

- `app/Http/Controllers/DispensingController.php`
- `app/Http/Controllers/DispensingHistoryController.php`
- `app/Http/Controllers/Print/DispensingRecordPrintController.php`
- `app/Actions/CreateDispensingRecord.php`
- `app/Actions/PostDispense.php`
- `app/Actions/DispensePrescription.php`
- `tests/Feature/Controllers/DispensingControllerTest.php`
- `tests/Feature/Controllers/DispensingHistoryControllerTest.php`

### 4. Pharmacy POS

**Status:** implemented

What exists:

- POS landing page
- active cart creation
- add, update, remove cart items
- hold and resume cart
- checkout
- finalize sale
- history page
- sale detail page
- receipt print
- additional payments
- void and refund flows
- stock allocation and reversal behavior

Evidence in code:

- `app/Http/Controllers/PharmacyPosController.php`
- `app/Http/Controllers/PharmacyPosCartController.php`
- `app/Http/Controllers/PharmacyPosSaleController.php`
- `app/Http/Controllers/PharmacyPosPaymentController.php`
- `app/Http/Controllers/PharmacyPosSaleVoidController.php`
- `app/Http/Controllers/PharmacyPosSaleRefundController.php`
- `app/Actions/FinalizePharmacyPosSaleAction.php`
- `app/Actions/VoidPharmacyPosSaleAction.php`
- `app/Actions/RefundPharmacyPosSaleAction.php`
- `tests/Feature/PharmacyPosPhase1Test.php`
- `tests/Feature/PharmacyPosPhase2Test.php`
- `tests/Feature/PharmacyPosPhase3Test.php`
- `tests/Feature/PharmacyPosPhase4Test.php`
- `tests/Feature/PharmacyPosPhase5Test.php`

### 5. Pharmacy Inventory Operations Around Requisitions and Receipts

**Status:** implemented

What exists:

- pharmacy-specific requisition entry points
- goods receipt entry points under pharmacy routes
- pharmacy/main-store access separation in tests

Evidence in code:

- `app/Http/Controllers/InventoryRequisitionController.php`
- `app/Http/Controllers/GoodsReceiptController.php`
- `tests/Feature/Controllers/InventoryRequisitionControllerTest.php`
- `tests/Feature/Controllers/GoodsReceiptControllerTest.php`

## What Is Only Partial

### 1. Pharmacy Authorization Model

**Status:** improved, but still partial

The pharmacy flows now have a better boundary than before. Dedicated pharmacy permissions were added for queue, prescription review, and dispensing:

- `pharmacy_queue.view`
- `pharmacy_prescriptions.view`
- `pharmacy_dispensing.view`
- `pharmacy_dispensing.create`
- `pharmacy_dispensing.post`

These are now used by the main pharmacy controllers instead of relying on `visits.view` for those workflows.

Why this matters:

- the pharmacy surface is easier to reason about
- permission intent is clearer in controllers and role seeding
- future pharmacy access changes are less likely to leak through unrelated clinical permissions

What is still partial:

- the pharmacy permission model is better defined, but it is not yet fully exhaustive across every pharmacy-adjacent route and print/report surface
- there is still room to standardize naming across all inventory/pharmacy edges

### 2. POS Lifecycle Vocabulary

**Status:** improved, but still partial

The POS implementation now uses a clearer terminal status for voided sales:

- `PharmacyPosSaleStatus::Voided`
- success messaging now says `Sale voided and stock reversed.`
- the reversal note now says `Voided - stock reversed`

This is better aligned with the route, action, and UI language than the earlier mix of "void" actions with `Cancelled` status storage.

Why this matters:

- tests and UI language can drift
- business meaning becomes less obvious
- reporting semantics become harder to keep consistent

What is still partial:

- historical/local data created with the older `cancelled` term may still need cleanup if this change is being applied to an existing environment rather than a fresh test database
- refund versus void semantics are clearer now, but broader reporting language should still be reviewed for consistency

### 3. Shared Data-Shaping Layer

**Status:** partial

The module works, but a lot of controllers still hand-build overlapping payload arrays for queue rows, prescription items, dispense records, sale details, and stock summaries.

Why this matters:

- repeated serialization logic is harder to maintain
- type cleanup becomes more expensive
- one bug fix often has to be repeated in several controllers

### 4. Static Analysis Maturity

**Status:** partial

A good amount of pharmacy-related `phpstan` cleanup has already been done in focused slices, especially around:

- print controllers
- dispensing and prescription controllers
- pharmacy POS models, actions, and controllers

But the module as a whole is still not at the point where I would call it fully hardened from a static-analysis perspective.

## What Is Still Not Complete

### 1. Dedicated Pharmacy Permission Set

**Not complete**

Examples of what is still missing conceptually:

- `pharmacy.queue.view`
- `pharmacy.prescriptions.view`
- `pharmacy.dispense.create`
- `pharmacy.dispense.post`
- `pharmacy.pos.use`
- `pharmacy.pos.void`

The current implementation is usable, but not yet as explicit as it should be.

### 2. Full Consistency Pass Across Request, Controller, and Status Boundaries

**Not complete**

The module has had enough recent change that some workflows still need a final consistency pass:

- request payload normalization
- enum/status naming alignment
- shared serializer extraction
- controller duplication cleanup

### 3. Final Hardening Pass

**Not complete**

The module still needs a final pass focused on:

- edge-case coverage
- permission clarity
- duplication removal
- cleaner stock and status semantics

This is the difference between "works" and "finished".

## What Was Achieved Recently

### 1. Documentation Was Brought Closer to Reality

The old pharmacy write-up lagged behind the codebase. The current module is no longer a foundation-only effort; it contains real operational flows.

### 2. PhpStan Cleanup Has Already Started

Targeted cleanup work has already been done in several high-signal pharmacy-related areas, especially:

- dispensing and prescription flow typing
- print flow typing
- pharmacy POS typing and model relation annotations

That improves maintainability, but it does not yet mean the whole module is fully clean.

### 3. Model and Relation Typing Improved In Touched Areas

Where pharmacy models were touched during cleanup, they were moved toward clearer model PHPDoc and stronger relation typing, which makes future work safer.

### 4. Pharmacy Permission And POS Vocabulary Cleanup Started

The first two recommended hardening steps have now been partly implemented:

- dedicated pharmacy permissions were added and wired into queue, prescription, dispensing, dispensing history, and dispensing print controllers
- POS void terminology was aligned around `voided` instead of mixing "void" workflow labels with `cancelled` sale status language

## Main Reasons I Would Not Call It Complete Yet

1. The module has strong breadth, but some of the finishing work is still architectural rather than feature-additive.
2. Authorization is still broader than ideal in parts of the pharmacy surface.
3. There is still too much repeated controller serialization and stock-summary shaping.
4. POS language is better aligned now, but the overall lifecycle semantics still need a final consistency review.
5. The module needs a final hardening pass before it can be called mature.

## What Is Not Necessary Right Now

These would add complexity before the current gaps are closed:

- creating a separate standalone drug catalog outside `inventory_items`
- rebuilding dispensing around branch-wide stock without location control
- merging prescription dispensing and pharmacy POS into one workflow
- adding more UI polish before finishing consistency and authorization cleanup
- introducing more pharmacy tables before consolidating the current logic

## Recommended Next Steps

1. Finish expanding the dedicated pharmacy permission model across the remaining pharmacy-adjacent routes and reports.
2. Review existing environments for any needed status/data transition from older POS `cancelled` wording to `voided`.
3. Extract shared pharmacy presenters/transformers for repeated payload shapes.
4. Continue the remaining `phpstan` cleanup in the unresolved request and controller areas.
5. Run a final pharmacy-focused regression pass across queue, dispensing, requisitions, goods receipts, and POS.

## Final Assessment

If the question is "does the pharmacy module exist and work in meaningful ways?", the answer is **yes**.

If the question is "is the pharmacy module complete?", the answer is **not yet**.

The current state is best described as:

- **implemented**
- **usable**
- **broad in scope**
- **still needing final hardening and consistency work**
