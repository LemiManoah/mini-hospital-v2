# Inventory Module Implementation Plan

**Date:** April 2, 2026  
**Goal:** Build the inventory module as a complete operational system for pharmacy stock, general consumables, procurement, internal issue workflows, and stock-controlled dispensing across branches.

---

## 1) Current Read

The codebase now uses `InventoryItem` as the single source of truth for medications and stock catalog records.

What already exists:

- `InventoryItem` now holds both drug and non-drug catalog records
- `Prescription` and `PrescriptionItem` already exist in the consultation workflow
- consultation prescribing now uses `inventory_item_id` for medication items
- billing foundations exist through `VisitBilling`, `VisitCharge`, and visit payment capture
- branch-aware and tenant-aware architecture already exists
- laboratory can record consumables used on a request item, including actual execution cost
- supplier, purchase order, and goods receipt workflows now exist
- posted goods receipts now create inventory batches and stock movements
- stock-by-location and stock movement visibility pages now exist
- stock counts and stock adjustments now exist for controlled reconciliation
- City General Hospital main branch now has seeded inventory workflow data for manual testing

What does not yet exist:

- pharmacy store workflow
- branch-to-branch or store-to-store transfers
- departmental requisitions and issue workflow
- stock counts, cycle counts, expiry or damage write-offs, and reconciliation tooling
- dispensing records tied to real stock depletion
- reorder levels, alerts, and inventory reporting

This means the inventory module should be built as a full operational layer around the shared `InventoryItem` catalog, not as a sidecar around a separate drug module.

---

## 2) Design Direction

### Core Principle

Treat inventory as an event-driven stock ledger with immutable movements, not a single mutable quantity field scattered across unrelated tables.

### Architecture Decision

Use `InventoryItem` as the unified catalog for drugs, consumables, supplies, reagents, and other stockable items.

Why this is better in this codebase:

- there is now only one medication source of truth for cataloging and prescribing
- consultation order UI and prescription creation already read from `InventoryItem` records of type `drug`
- drug-specific metadata still fits cleanly on inventory items through generic name, dosage form, category, and expiry behavior
- inventory can still solve location, batch, movement, and procurement concerns without splitting medication data across two modules

### Important Modeling Decisions

- use `InventoryItem` as both the medication catalog and operational stock catalog
- support both drug and non-drug stock items:
  - medications
  - lab consumables
  - procedure consumables
  - ward/general supplies
- track stock by **location** and **batch**
- record every increase/decrease as a stock movement with a source document
- freeze selling price and cost snapshots on dispense/issue/receipt records
- make inventory branch-aware from day one

### What To Avoid

- creating a second medication master outside `inventory_items`
- allowing dispense logic without batch-aware stock validation
- letting users directly edit quantity-on-hand without an adjustment record
- mixing procurement approval rules into stock movement tables
- making pharmacy the only inventory consumer

---

## 3) What Counts As Complete

The inventory module should be considered complete when all of the following are true:

- stock items can be created and maintained in the app
- the system supports multiple branch-aware inventory locations
- stock can be received through supplier purchasing and goods receipt
- stock is tracked per batch with expiry support
- all stock changes are auditable through a movement ledger
- users can perform adjustments, counts, and reconciliations safely
- departments can request stock and stores can issue it
- branches or stores can transfer stock between locations
- prescriptions can be dispensed against available stock
- lab and other service areas can consume stock through inventory-backed issue workflows
- low-stock, expiring, and out-of-stock visibility exists
- permissions, branch isolation, and feature tests cover the critical workflows

---

## 4) Recommended Scope

This module should cover four connected areas, not only pharmacy:

### 4.1 Stock Foundations

- inventory catalog
- stores and sub-stores
- stock batches
- stock movement ledger
- stock valuation snapshots

### 4.2 Procurement

- suppliers
- purchase orders
- goods receipts
- invoice/reference capture
- receiving variance handling

### 4.3 Internal Supply Chain

- requisitions from departments
- store issues
- inter-store transfers
- stock counts and adjustments
- expiry, loss, damage, and return workflows

### 4.4 Pharmacy Operations

- pending prescription queue
- partial and full dispensing
- substitution or out-of-stock handling
- dispense-linked billing guard rails
- patient label and counselling capture

---

## 5) Domain Model

The exact names can change later, but the module should roughly center on the following models and tables.

### Core Catalog

- `InventoryItem` -> `inventory_items`
- `InventoryLocation` -> `inventory_locations`
- `InventoryLocationItem` -> `inventory_location_items`
- `Supplier` -> `suppliers`

### Stock State And Ledger

- `InventoryBatch` -> `inventory_batches`
- `StockMovement` -> `stock_movements`
- `StockAdjustment` -> `stock_adjustments`
- `StockAdjustmentItem` -> `stock_adjustment_items`
- `StockCount` -> `stock_counts`
- `StockCountItem` -> `stock_count_items`

### Procurement

- `PurchaseOrder` -> `purchase_orders`
- `PurchaseOrderItem` -> `purchase_order_items`
- `GoodsReceipt` -> `goods_receipts`
- `GoodsReceiptItem` -> `goods_receipt_items`

### Internal Supply Chain

- `InventoryRequisition` -> `inventory_requisitions`
- `InventoryRequisitionItem` -> `inventory_requisition_items`
- `StockTransfer` -> `stock_transfers`
- `StockTransferItem` -> `stock_transfer_items`

### Pharmacy Fulfillment

- `DispensingRecord` -> `dispensing_records`
- `DispensingRecordItem` or reuse one record per `PrescriptionItem` depending on final design

### Suggested Inventory Item Fields

- tenant and branch scope
- item type:
  - `drug`
  - `consumable`
  - `supply`
  - `reagent`
  - `other`
- generic name for `drug` items
- name / display name for non-drug items
- drug category, strength, and dosage form for drug items
- unit of measure
- boolean flag for whether the item expires
- minimum stock level
- reorder level
- default selling price
- default purchase price
- active flag

### Suggested Inventory Location Types

- main store
- pharmacy
- laboratory
- procedure room
- ward store
- satellite store

### Suggested Stock Movement Types

- opening_balance
- receipt
- transfer_out
- transfer_in
- issue
- dispense
- adjustment_gain
- adjustment_loss
- return_in
- return_out
- expiry
- damage

### Key Relationship Rules

- one `InventoryItem` may belong to many locations
- one location may hold many batches of the same item
- stock balance should be derivable from `stock_movements`
- `GoodsReceiptItem` creates inbound stock movements and batches
- `DispensingRecord` creates outbound dispense movements
- requisition issue creates outbound store movement and inbound department movement only if the receiving location tracks stock formally
- lab consumable usage should eventually consume inventory through the same movement layer

---

## 6) Workflow Outline

## 6.1 Catalog And Store Setup

1. Create inventory locations for each branch
2. Create stock items
3. For medication items, set generic name, dosage form, category, expiry behavior, and strength
4. Set units, reorder thresholds, and default prices
5. Optionally restrict which locations may stock which items

## 6.2 Procurement And Receiving

1. Create supplier
2. Raise purchase order
3. Approve purchase order
4. Receive delivered items against the PO
5. Capture batch number, expiry date, unit cost, and received quantity
6. Create stock batches and receipt movements
7. Mark PO as partial or complete depending on received quantities

## 6.3 Stock Corrections

1. Start stock count or adjustment
2. Record counted quantity or correction reason
3. Require review/approval for sensitive adjustments
4. Post balancing stock movements
5. Preserve before/after values for audit

## 6.4 Internal Requisition And Issue

1. Department or sub-store raises requisition
2. Store reviews availability
3. Approve fully or partially
4. Issue stock from selected batches
5. Mark requisition as fulfilled, partially fulfilled, or cancelled

## 6.5 Inter-Store Transfers

1. Source store creates transfer
2. Reserve or issue stock out from source
3. Destination receives transfer
4. System creates transfer-out and transfer-in movements
5. Differences or losses require reconciliation notes

## 6.6 Pharmacy Dispensing

1. Clinician creates prescription from consultation
2. Pharmacy queue shows pending prescription items
3. Pharmacist selects batch and quantity to dispense
4. System validates stock availability in the pharmacy location
5. System creates dispense records and stock movements
6. Prescription item becomes partial or dispensed
7. Prescription header status updates accordingly
8. Counselling notes, label instructions, batch, and expiry snapshots are stored

## 6.7 Returns And Exceptions

- patient return of unopened medication
- supplier return for damaged/expired items
- expired stock quarantine and write-off
- out-of-stock or external pharmacy handling
- substitution flow with pharmacist override and audit trail

---

## 7) UI Surface

The inventory module should feel like a real operational workspace, not a collection of admin CRUD pages.

### Main Areas

- inventory dashboard
- catalog
- stores/locations
- suppliers
- purchases
- receipts
- requisitions
- transfers
- stock counts
- adjustments
- pharmacy queue
- dispensing history
- reports and alerts

### Recommended Pages

- `inventory/dashboard`
- `inventory/items/index`
- `inventory/items/create`
- `inventory/items/edit`
- `inventory/items/show`
- `inventory/locations/index`
- `inventory/suppliers/index`
- `inventory/purchases/index`
- `inventory/purchases/show`
- `inventory/receipts/index`
- `inventory/stock-by-location`
- `inventory/requisitions/index`
- `inventory/transfers/index`
- `inventory/counts/index`
- `inventory/adjustments/index`
- `pharmacy/queue`
- `pharmacy/prescriptions/{prescription}`
- `pharmacy/dispenses/index`
- `inventory/reports/stock-status`
- `inventory/reports/movements`
- `inventory/reports/expiry`

### Dashboard Widgets

- low stock items
- out of stock items
- expiring soon batches
- pending purchase orders
- pending requisitions
- pending transfers to receive
- pending prescriptions to dispense
- stock value by location

---

## 8) Permissions

Use explicit permissions instead of one large `inventory.manage` gate.

Recommended permissions:

- `inventory.view`
- `inventory.items.manage`
- `inventory.locations.manage`
- `inventory.suppliers.manage`
- `inventory.purchases.manage`
- `inventory.receipts.manage`
- `inventory.adjustments.manage`
- `inventory.counts.manage`
- `inventory.requisitions.manage`
- `inventory.transfers.manage`
- `inventory.reports.view`
- `pharmacy.queue.view`
- `pharmacy.dispense`
- `pharmacy.override_substitution`
- `pharmacy.manage_catalog_links`

Branch isolation should follow the same active-branch approach already used elsewhere in the app.

---

## 9) Integration With Existing Modules

### Catalog

- keep medication and stock catalog data in `InventoryItem`
- use `item_type=drug` for prescribable medication items
- do not introduce a second medication master outside inventory

### Prescriptions

- dispensing should attach to `PrescriptionItem`
- partial dispensing must update both line and header statuses
- external pharmacy items should not consume local stock

### Billing

- billing should use frozen dispense or charge snapshots, not live stock values
- if drug charges are generated at order time, dispensing must not silently change billed totals
- if drug charges are generated at dispense time, that rule should be explicit and consistent

### Laboratory And Procedures

- lab consumable logging should evolve toward inventory-backed issues
- the same stock movement engine should support lab reagents and clinical consumables later

### Branches

- inventory should be branch-scoped by default
- transfers between branches must remain explicit, auditable documents

---

## 10) Milestone Checklist

- [x] Milestone 0 completed: surrounding foundations already exist (`InventoryItem`, `Prescription`, branch scoping, billing base)
- [ ] Milestone 1 in progress: inventory foundations and stock catalog
- [x] Milestone 2 completed: suppliers, purchase orders, and goods receipt
- [ ] Milestone 3 in progress: stock ledger, balances, counts, and adjustments
- [ ] Milestone 4 pending: requisitions and inter-store transfers
- [ ] Milestone 5 pending: pharmacy dispensing workflow
- [ ] Milestone 6 pending: alerts, reporting, permissions, and audit coverage
- [ ] Milestone 7 pending: test hardening and rollout polish

---

## 11) Milestone Details

## Milestone 0: Existing Foundations

### Current Assets We Should Reuse

- `InventoryItem` item types plus drug enums
- `Prescription` and `PrescriptionItem`
- branch and tenant scoping traits
- visit billing and charge foundations
- lab consumable cost recording patterns

### Implication

The inventory module should plug into existing clinical workflows instead of redesigning prescriptions or billing from scratch.

## Milestone 1: Inventory Foundations And Stock Catalog

### Objective

Create the base inventory domain so the app can understand what items exist, where they are stocked, and how stock should be tracked.

### Deliverables

- `InventoryItem` model, migration, controller, requests, pages
- `InventoryLocation` model and admin surface
- drug-specific fields directly on `InventoryItem` when `item_type=drug`
- stock balance service or query strategy
- inventory dashboard shell
- initial permissions

### Milestone Checklist

- [x] Create `inventory_items` table
- [x] Create `inventory_locations` table
- [x] Decide whether `inventory_location_items` is needed for location-specific settings
- [x] Add `InventoryItem` and `InventoryLocation` models
- [x] Add CRUD routes and pages
- [ ] Add item search and filters
- [x] Add item type support
- [x] Make `InventoryItem` the medication source of truth for pharmacy items
- [ ] Add active/inactive handling instead of destructive deletes
- [x] Add branch-aware permission enforcement

### Current Status

The schema, base models, requests, actions, controllers, routes, and first Inertia CRUD pages for `InventoryItem` and `InventoryLocation` are now in place. The next slice is polish and operational depth: location-item assignment, richer filtering, safer deactivation rules, and then deeper stock-ledger workflows.

### Definition Of Done

- stock items and inventory locations can be managed in the app
- the system has a stable operational stock catalog with one medication source of truth

## Milestone 2: Suppliers, Purchase Orders, And Goods Receipt

### Objective

Build the inbound supply workflow so stock enters the system through controlled procurement documents instead of ad hoc quantity edits.

### Deliverables

- `Supplier` CRUD
- purchase order workflow
- goods receipt workflow
- batch capture on receiving
- receipt-created stock movements

### Milestone Boundary Note

The procurement workflow is now complete from supplier through posted receipt, and posted receipts now write into the stock-ledger foundation added in Milestone 3. Milestone 2 is therefore complete, while the broader stock-control tooling continues in Milestone 3.

### Milestone Checklist

- [x] Create `suppliers` table and CRUD
- [x] Create `purchase_orders` and `purchase_order_items`
- [x] Create `goods_receipts` and `goods_receipt_items`
- [x] Support PO statuses:
  - draft
  - submitted
  - approved
  - partial
  - received
  - cancelled
- [x] Support receipt statuses:
  - draft
  - posted
  - cancelled
- [x] Capture supplier invoice/reference details
- [x] Capture batch number, expiry, quantity, and unit cost at receipt time
- [x] Create inbound stock movements from posted receipts
- [x] Add receiving UI and PO detail page
- [x] Add tests for full and partial receiving

### Definition Of Done

- new stock enters the system through auditable receipts
- received stock is batch-aware and priced at receipt time

## Milestone 3: Stock Ledger, Balances, Counts, And Adjustments

### Objective

Make stock trustworthy by introducing a movement ledger and controlled reconciliation tools.

### Deliverables

- `InventoryBatch`
- `StockMovement`
- stock balance calculation
- stock-by-location visibility page
- stock adjustments
- stock counts / cycle counts
- expiry and damaged stock write-off support

### Milestone Checklist

- [x] Create `inventory_batches`
- [x] Create `stock_movements`
- [x] Create `stock_adjustments` and related items
- [x] Create `stock_counts` and related items
- [x] Build stock summary queries by item, batch, and location
- [x] Add stock-by-location page showing each item's quantity across locations
- [ ] Add adjustment reasons and approval rules
- [ ] Add cycle count workflow
- [ ] Add expiry and damage write-off workflow
- [x] Add movement history page
- [x] Add tests that balances match posted movements

### Definition Of Done

- stock on hand can be explained by posted movements
- users cannot change quantity silently outside approved workflows

### Current Status

The stock ledger foundation is now in place. Posted goods receipts create `InventoryBatch` and `StockMovement` records, the stock-by-location matrix reads from movement-backed balances, a stock movement history page is available for branch users, and both stock adjustments and stock counts can now be created and posted to reconcile inventory against the ledger. City General Hospital main branch also has seeded inventory users and workflow data so the milestone 3 surfaces can be exercised manually after seeding. Milestone 3 is still not complete, because cycle-count workflow, adjustment approval rules, and expiry or damage write-off tooling are still outstanding.

## Milestone 4: Requisitions And Inter-Store Transfers

### Objective

Support operational movement of stock inside the hospital between stores and service points.

### Deliverables

- requisition workflow
- store issue workflow
- transfer workflow
- partial fulfillment support
- receiving and reconciliation at destination

### Milestone Checklist

- [ ] Create `inventory_requisitions` and items
- [ ] Create `stock_transfers` and items
- [ ] Add requisition statuses:
  - draft
  - submitted
  - approved
  - partially_issued
  - fulfilled
  - cancelled
- [ ] Add transfer statuses:
  - draft
  - in_transit
  - received
  - reconciled
  - cancelled
- [ ] Add source/destination location handling
- [ ] Post transfer-out and transfer-in movements
- [ ] Add requisition and transfer pages
- [ ] Add tests for partial issue and transfer receipt

### Definition Of Done

- departments and sub-stores can receive stock without bypassing the ledger
- internal stock movement is auditable end to end

## Milestone 5: Pharmacy Dispensing Workflow

### Objective

Turn prescriptions into real, stock-controlled pharmacy fulfillment.

### Deliverables

- pharmacy queue page
- prescription dispense page
- `DispensingRecord`
- partial/full dispense logic
- batch selection and stock validation
- counselling and label data capture

### Milestone Checklist

- [ ] Add pharmacy routes and pages
- [ ] Create `dispensing_records`
- [ ] Decide whether one record per dispense event or header/item split is cleaner for this app
- [ ] Validate available stock before dispense
- [ ] Support partial dispensing
- [ ] Support external pharmacy and out-of-stock outcomes
- [ ] Store batch and expiry snapshot on dispense
- [ ] Update `PrescriptionItemStatus` and `PrescriptionStatus`
- [ ] Add audit-friendly actor and timestamp fields
- [ ] Add tests for partial, full, and blocked dispense scenarios

### Definition Of Done

- pharmacists can fulfill prescriptions from actual stock
- prescription status accurately reflects fulfillment progress

## Milestone 6: Alerts, Reporting, Permissions, And Audit Coverage

### Objective

Make the module operationally useful for day-to-day oversight and safe enough for real-world use.

### Deliverables

- low-stock alerts
- expiry reporting
- stock valuation view
- dashboard summaries
- hardened permissions
- branch isolation coverage

### Milestone Checklist

- [ ] Add dashboard summary cards
- [ ] Add low-stock and out-of-stock reports
- [ ] Add expiring-batches report
- [ ] Add stock movement report
- [ ] Add stock valuation report
- [ ] Add per-permission enforcement across routes and actions
- [ ] Add branch isolation tests
- [ ] Add printable/export-friendly views where useful

### Definition Of Done

- store and pharmacy teams can monitor stock risk without manual spreadsheets
- sensitive inventory actions are permissioned and auditable

## Milestone 7: Test Hardening And Rollout Polish

### Objective

Stabilize the module for confident use across branches and workflows.

### Deliverables

- end-to-end feature coverage
- seed data for inventory demo flows
- consistent navigation and module entry points
- documentation updates for system and schema alignment

### Milestone Checklist

- [ ] Add factory coverage for inventory core models
- [ ] Add seeders for sample locations, suppliers, and items
- [ ] Add end-to-end tests for:
  - receive stock
  - adjust stock
  - transfer stock
  - issue requisition
  - dispense prescription
- [ ] Add navigation links from modules and sidebar
- [ ] Update `system.md` after implementation lands
- [ ] Update `hospital_database_schema.md` to match final table design

### Definition Of Done

- the inventory module is stable, discoverable, and documented
- downstream teams can build on it without reworking the stock foundation

---

## 12) Suggested Build Order

The safest implementation order is:

1. inventory catalog and locations
2. suppliers and purchasing
3. receipt-driven stock movements and batches
4. stock counts and adjustments
5. requisitions and transfers
6. pharmacy dispensing
7. alerts, reporting, and test hardening

This order matters because dispensing, lab consumption, and internal issue workflows become risky if the stock ledger is not already trustworthy.

---

## 13) Bottom Line

The right “full inventory module” for this app is broader than pharmacy alone. It should become the stock-control foundation for pharmacy, lab consumables, and general hospital supplies, while still giving pharmacy its own operational queue and dispensing workflow.

The most important architectural choice is to build inventory around locations, batches, and immutable movements first. Once that foundation exists, procurement, requisitions, transfers, and dispensing can all fit into one coherent system instead of becoming disconnected quantity-update features.
