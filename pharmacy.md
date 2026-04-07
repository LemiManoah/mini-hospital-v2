# Pharmacy Module Review and Implementation Guide

**Date:** April 7, 2026  
**Goal:** Explain what the application already does for pharmacy, what is still missing, and how to build the remaining pharmacy workflow cleanly on top of the current codebase.

---

## 1) Current Read

The pharmacy module is **partially built**, but it is not yet a full pharmacy operations module.

What already exists today:

- a dedicated pharmacy inventory workspace in the sidebar
- store-aware pharmacy stock access inside the active branch
- pharmacy requisitions from main store
- pharmacy goods receipts
- pharmacy stock movements
- clinical prescription creation from visits and consultations
- prescription and prescription-item statuses that already anticipate dispensing

What does **not** exist yet:

- a pharmacy dispensing queue
- a dispense action that reduces pharmacy stock
- batch-aware dispense selection
- stock validation during dispensing
- partial/full dispensing logic that updates prescription state
- a pharmacist-facing workflow for substitution, out-of-stock handling, and patient handover

So the current application has the **foundations** for pharmacy, but not yet the **operational dispensing engine**.

---

## 2) What the App Already Does for Pharmacy

### 2.1 Pharmacy Inventory Workspace Exists

The app already exposes pharmacy-specific inventory entry points in [app-sidebar.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/app-sidebar.tsx):

- `Pharmacy Stock`
- `Pharmacy Requisitions`
- `Pharmacy Movements`
- `Pharmacy Receipts`

These routes are defined in [web.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/routes/web.php):

- `/pharmacy/stock`
- `/pharmacy/requisitions`
- `/pharmacy/movements`
- `/pharmacy/receipts`

Important detail:

- these are currently **dedicated entry points**
- but most of the pages reuse the generic inventory components through the workspace system

Examples:

- [resources/js/pages/pharmacy/stock/index.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/pharmacy/stock/index.tsx)
- [resources/js/pages/pharmacy/requisitions/index.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/pharmacy/requisitions/index.tsx)
- [resources/js/pages/pharmacy/movements/index.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/pharmacy/movements/index.tsx)

These mostly re-export the inventory pages rather than introducing a separate pharmacy-specific controller layer.

### 2.2 Store-Aware Access Already Exists

The pharmacy workspace already behaves differently from main inventory because of:

- [InventoryWorkspace.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php)
- [InventoryNavigationContext.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php)
- [InventoryLocationAccess.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryLocationAccess.php)

This means:

- pharmacists are scoped mainly to `InventoryLocationType::PHARMACY`
- pharmacy pages show pharmacy-facing stock and requisitions
- main store users still retain broader branch-level stock authority

This is a strong foundation, because dispensing should happen from a specific pharmacy stock location, not from a branch-wide anonymous stock pool.

### 2.3 Pharmacy Locations Already Exist in the Inventory Model

[InventoryLocation.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InventoryLocation.php) already supports:

- `type`
- `is_dispensing_point`

The seed data in [InventoryLocationSeeder.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/database/seeders/InventoryLocationSeeder.php) already creates:

- `City General Main Pharmacy`

and marks it as:

- `type = pharmacy`
- `is_dispensing_point = true`

That is exactly the kind of location dispensing should target.

### 2.4 Pharmacy Already Gets Stock Through Inventory Workflows

The pharmacy can already receive stock in two ways:

- direct goods receipt into pharmacy
- requisition from main store into pharmacy

That means the upstream stock foundation needed for dispensing is already present:

- posted goods receipts create stock
- requisitions move stock from main store to pharmacy
- pharmacy stock is visible in pharmacy stock and movement pages

This is why pharmacy dispensing is now the natural next milestone instead of more inventory foundations.

---

## 3) What the App Already Does for Prescriptions

### 3.1 Prescriptions Are Already Created in Clinical Workflows

Prescriptions are already created from:

- visit context in [VisitOrderController.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/VisitOrderController.php)
- consultation context in [DoctorConsultationPrescriptionController.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/DoctorConsultationPrescriptionController.php)

The creation action is:

- [CreatePrescription.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/CreatePrescription.php)

### 3.2 Prescriptions Already Use Inventory Items

This is an important design strength.

`CreatePrescription` already stores:

- `inventory_item_id`

on each prescription item, and it explicitly limits choices to active drug inventory items.

Validation for that is in:

- [StoreConsultationPrescriptionRequest.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreConsultationPrescriptionRequest.php)

That means the prescription system is already linked to the real pharmacy catalog, not a separate drug table.

### 3.3 Prescription Data Already Anticipates Dispensing

The prescription schema already includes pharmacy-facing fields.

In [2026_03_15_090040_create_prescriptions_tables.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/database/migrations/2026_03_15_090040_create_prescriptions_tables.php):

`prescriptions` already has:

- `pharmacy_notes`
- `status`

`prescription_items` already has:

- `status`
- `dispensed_at`
- `is_external_pharmacy`

The models and enums:

- [Prescription.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/Prescription.php)
- [PrescriptionItem.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/PrescriptionItem.php)
- [PrescriptionStatus.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Enums/PrescriptionStatus.php)
- [PrescriptionItemStatus.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Enums/PrescriptionItemStatus.php)

already support:

- pending
- partially dispensed
- fully dispensed
- cancelled

and at item level:

- pending
- partial
- dispensed
- cancelled

That means the domain language is already there, but the workflow that actually **moves a prescription from pending to dispensed** is not yet implemented.

### 3.4 The UI Already Shows Prescriptions in Clinical Pages

Prescriptions are already visible and editable in:

- [resources/js/pages/visit/components/visit-clinical-tab.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/visit/components/visit-clinical-tab.tsx)
- [resources/js/pages/doctor/consultations/show.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/doctor/consultations/show.tsx)
- [resources/js/components/orders/prescription-orders-table.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/orders/prescription-orders-table.tsx)
- [resources/js/components/orders/prescription-order-modal.tsx](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/orders/prescription-order-modal.tsx)

So doctors and clinical users already have the **ordering side** of the pharmacy story.

---

## 4) What Is Missing for a Real Pharmacy Module

This is the main gap.

### 4.1 No Pharmacy Queue

There is no pharmacy operational queue yet.

What should exist but does not:

- pending prescriptions queue
- ready-to-dispense queue
- partial dispense follow-up queue
- external pharmacy / out-of-stock queue

Right now prescriptions live in the visit and consultation screens, but there is no dedicated place where pharmacy staff work through them.

### 4.2 No Dispense Transaction

There is currently no controller, action, or route that does the core pharmacy event:

- choose prescription item
- choose batch
- choose quantity
- post dispensing

I did find that [StockMovementType.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Enums/StockMovementType.php) already contains:

- `Dispense`

But I did **not** find an implemented workflow that uses it yet.

So the stock ledger is ready for dispensing, but the pharmacy workflow is not wired into it.

### 4.3 No Batch-Aware Dispensing

Dispensing should not just subtract a number from stock.

It should:

- pick a pharmacy location
- pick one or more source batches
- record the quantity from each batch
- snapshot expiry and batch details at time of dispense

That does not exist yet.

### 4.4 No Link Between Prescription Status and Stock Movements

The status enums exist, but the status transitions are not being driven by actual dispense events.

What is still missing:

- item status changes after partial dispense
- header status changes after all lines are fulfilled
- `dispensed_at` set from actual dispense posting
- consistent update rules between `Prescription` and `PrescriptionItem`

### 4.5 No Out-of-Stock / External Pharmacy Workflow

The schema already supports:

- `is_external_pharmacy`

But there is no operational workflow yet for:

- not in stock locally
- send out externally
- partial local dispense + partial external
- substitution approval

### 4.6 No Pharmacist-Facing Patient Handover Workflow

A real pharmacy workflow usually needs:

- patient label / instructions
- counselling note
- who dispensed
- when dispensed
- what was withheld and why

Those operational pieces are still missing.

---

## 5) What the Pharmacy Module Should Become

The correct target is:

**prescribing in clinical pages, dispensing in pharmacy pages, stock depletion in inventory ledger**

That means pharmacy should become the bridge between:

- clinical medication orders
- pharmacy stock
- patient-facing medication fulfillment

### Core Pharmacy Responsibilities

The final pharmacy module should handle:

- pending prescription review
- stock availability check
- batch-aware dispensing
- partial dispensing
- full dispensing
- external pharmacy handling
- substitution workflow
- returns and cancellations where allowed
- patient handover / counselling capture
- printable pharmacy-facing outputs later

---

## 6) Recommended Real-Life Workflow

### Step 1: Prescription Is Written

Doctor creates prescription from:

- visit
- or consultation

The current system already supports this.

### Step 2: Prescription Appears in Pharmacy Queue

Pharmacy should see:

- patient
- visit number
- prescription date
- medication lines
- urgency / discharge / long-term flags
- pharmacy notes

### Step 3: Pharmacist Reviews Stock

For each line:

- check pharmacy stock balance
- check available batches
- check expiry
- decide whether full, partial, substitute, or external fulfillment is possible

### Step 4: Pharmacist Dispenses

For each dispensed line:

- select batch allocations
- enter quantity dispensed
- optionally add counselling or handover note
- post the dispense

### Step 5: System Posts Stock Movements

For each allocated batch:

- create `stock_movements` entries using `dispense`
- reduce quantity from the pharmacy location

### Step 6: Prescription Status Updates

Item level:

- `pending`
- `partial`
- `dispensed`
- `cancelled`

Header level:

- `pending`
- `partially_dispensed`
- `fully_dispensed`
- `cancelled`

### Step 7: Patient Leaves With Medication Record

The system should preserve:

- what was prescribed
- what was actually dispensed
- from which batch
- by which pharmacist
- when it was dispensed

---

## 7) Recommended Data Model Additions

The cleanest next addition is a dispensing document layer.

### Recommended Tables

- `dispensing_records`
- `dispensing_record_items`
- optionally `dispensing_item_allocations`

### Suggested `dispensing_records` Fields

- `id`
- `visit_id`
- `prescription_id`
- `inventory_location_id`
- `dispensed_by`
- `dispensed_at`
- `notes`
- `status`

### Suggested `dispensing_record_items` Fields

- `id`
- `dispensing_record_id`
- `prescription_item_id`
- `inventory_item_id`
- `prescribed_quantity`
- `dispensed_quantity`
- `balance_quantity`
- `dispense_status`
- `substitution_inventory_item_id` nullable
- `external_pharmacy` boolean
- `external_reason` nullable
- `notes`

### Suggested Allocation Table

If multi-batch dispensing is allowed, add:

- `dispensing_item_allocations`

with:

- `dispensing_record_item_id`
- `inventory_batch_id`
- `quantity`
- expiry snapshot
- batch number snapshot

This keeps the stock ledger consistent and audit-friendly.

---

## 8) Recommended Pages

### Pharmacy Workspace Pages

- `pharmacy/queue`
- `pharmacy/prescriptions/{prescription}`
- `pharmacy/dispenses/index`
- `pharmacy/dispenses/{dispense}`

### What Each Page Should Do

`pharmacy/queue`

- list pending and partial prescriptions
- filter by patient, visit, date, and status
- show quick stock availability signal

`pharmacy/prescriptions/{prescription}`

- show prescription details
- show current stock options from pharmacy location
- allow partial or full dispense
- allow external-pharmacy marking where needed

`pharmacy/dispenses/index`

- audit trail of completed dispensing
- filters by pharmacist, date, patient, and item

`pharmacy/dispenses/{dispense}`

- detail of what was actually handed out
- batches
- quantities
- notes

---

## 9) Recommended Backend Entry Points

### Controllers

- `PharmacyQueueController`
- `PharmacyPrescriptionController`
- `DispensingController`

### Actions

- `CreateDispensingRecord`
- `PostDispense`
- `CancelDispense`
- `MarkPrescriptionExternal`
- `SubstitutePrescriptionItem`

### Validation

- `StoreDispenseRequest`
- `UpdateDispenseRequest`

### Support Services

- `PharmacyStockAllocator`
- `PrescriptionDispenseStatusResolver`
- `PrescriptionQueueQuery`

---

## 10) How to Achieve It Cleanly in This Codebase

### Phase 1: Build the Pharmacy Queue

Start with read-only operational visibility.

Deliverables:

- pharmacy queue route and page
- query for pending and partially dispensed prescriptions
- status badges
- quick link from sidebar

Why first:

- it gives pharmacy users a real workspace immediately
- it does not yet risk stock corruption
- it clarifies the data shape needed for dispensing

### Phase 2: Add Dispensing Records

Introduce real dispense documents before touching stock.

Deliverables:

- migrations
- models
- controller
- create/show page

Why next:

- it gives the module a proper pharmacy transaction boundary
- stock movement posting can then attach to a clear source document

### Phase 3: Post Stock Movements

Once dispense records exist, connect them to inventory.

Deliverables:

- batch selection
- quantity validation
- `dispense` stock movements
- prescription item and header status updates

This is the step where pharmacy becomes truly stock-controlled.

### Phase 4: Handle Exceptions

After standard dispensing works, add:

- partial dispense
- substitution
- external pharmacy
- cancellation before completion
- returns if the business wants them

### Phase 5: Add Pharmacy Outputs

Later:

- print prescription
- print dispense slip / patient handout
- dispensing history exports

---

## 11) Important Design Decisions

### Keep Prescribing and Dispensing Separate

This is important.

- doctor writes the prescription
- pharmacy fulfills it

Do not make dispensing happen inside the doctor or visit order controllers.

### Dispense From a Real Pharmacy Location

Dispensing should always come from a concrete inventory location, ideally one marked:

- `is_dispensing_point = true`

That avoids branch-wide ambiguity and keeps the ledger trustworthy.

### Use Batch Allocations, Not Direct Quantity Edits

Do not update on-hand quantities directly on prescription items.

Always post:

- dispensing record
- stock movement

### Keep External Pharmacy as an Explicit Outcome

If the hospital sometimes sends patients to buy outside:

- capture that explicitly
- do not fake a local dispense

---

## 12) What Is Already Reusable

This codebase already gives the pharmacy module a lot of reusable foundation:

- `InventoryItem` as the medication catalog
- pharmacy inventory locations
- stock ledger and batches
- requisitions from main store
- pharmacy receipts
- prescription creation from visit and consultation
- prescription statuses
- `StockMovementType::Dispense`

That means the pharmacy module does **not** need a redesign of inventory or prescriptions first. It mainly needs the missing workflow layer between them.

---

## 13) Definition Of Done for Pharmacy

The pharmacy module should be considered complete when:

- pharmacists have a dedicated queue
- prescriptions can be reviewed and dispensed from pharmacy stock
- partial and full dispensing work
- dispense events reduce stock through `stock_movements`
- prescription statuses reflect what was actually dispensed
- external pharmacy and substitution are auditable
- pharmacy can review dispense history
- patient-facing medication fulfillment is traceable end to end

---

## 14) Bottom Line

The app already has:

- pharmacy stock visibility
- pharmacy requisitions and receipts
- prescription authoring
- inventory foundations strong enough to support dispensing

The big missing step is:

**turning prescriptions into real pharmacy fulfillment transactions**

So the best path forward is:

1. add a pharmacy queue
2. add dispensing records
3. post batch-aware dispense stock movements
4. update prescription statuses from actual dispense outcomes
5. then add substitution, external pharmacy, and print polish later
