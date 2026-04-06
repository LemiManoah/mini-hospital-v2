# Inventory Module Implementation Plan

**Date:** April 2, 2026  
**Goal:** Build the inventory module as a complete operational system for pharmacy stock, general consumables, procurement, internal issue workflows, and stock-controlled dispensing across branches.

---

## 1) Current Read

The codebase now uses `InventoryItem` as the single source of truth for medications and stock catalog records.

Current document status:

- this plan is up to date with the current application state
- the inventory module itself is not complete yet
- major completed areas are:
  - catalog and locations
  - procurement and receiving
  - stock ledger and reconciliations foundation
  - requester-to-main-store requisitions
- major deferred or unfinished areas are:
  - inter-store transfers
  - reconciliation presets for cycle count, expiry, and damage
  - pharmacy dispensing
  - alerts and reporting

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
- user-facing inventory reconciliations now exist for controlled stock correction and review
- same-branch inventory requisitions now support draft, submit, approve, reject, and issue workflow
- inventory access can now be store-aware inside a branch, with pharmacy and laboratory users scoped to their own locations while main store users keep broader branch-store access
- requisitions now follow a requester-to-main-store workflow:
  - pharmacy and laboratory raise and track their own requisitions
  - main store users work from an incoming requisitions queue
  - approving, rejecting, and issuing happens from the main store side
- requisitions are now queue-only from the main inventory workspace:
  - generic inventory-side requisition creation has been removed
  - pharmacy and laboratory own requisition creation and submission
  - draft requisitions stay in requester workspaces until submitted
- requisition permissions are now split by responsibility:
  - requester side uses create and submit permissions
  - main store uses review and issue permissions
- requisition workflow rules are now centralized in `InventoryRequisitionWorkflow`, reducing hard-coded main-store queue logic inside the controller
- requisition workspace access decisions are now centralized in `InventoryRequisitionAccess`, reducing inline requester-vs-main-store branching in the controller
- requisition pages and controller payloads now expose clearer workflow language with `fulfilling` and `requesting` locations in addition to the underlying source/destination storage
- requester workspaces can now cancel draft requisitions and withdraw submitted requisitions before main store review
- laboratory and pharmacy now have dedicated inventory entry points for stock, requisitions, movements, and receipts, while the main inventory workspace remains the broader branch store workspace
- City General Hospital main branch now has seeded inventory workflow data and inventory users for manual testing

What does not yet exist:

- branch-to-branch or store-to-store transfers
- reconciliation presets and specialized workflows for cycle count, expiry, and damage handling
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
- users can perform reconciliations safely with review and approval workflow
- departments can request stock and stores can issue it
- branches or stores can transfer stock between locations
- prescriptions can be dispensed against available stock
- lab and other service areas can consume stock through inventory-backed issue workflows
- low-stock, expiring, and out-of-stock visibility exists
- store teams such as main store, pharmacy, and laboratory can each operate inside their own stock workspace without losing branch isolation
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
- reconciliations
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
- `InventoryReconciliation` -> user-facing workflow currently backed by `stock_reconciliations`
- reconciliation line items -> currently backed by `stock_reconciliation_items`

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
- requisition_out
- requisition_in
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

1. Start reconciliation
2. Record actual quantity found or the correction being applied
3. Require review/approval before posting balancing movements
4. Post balancing stock movements
5. Preserve before/after values for audit

## 6.4 Internal Requisition And Issue

1. Department or sub-store raises requisition
2. Source and destination remain within the active branch
3. Store reviews availability
4. Approve fully or partially
5. Issue stock from selected source batches
6. System posts outbound movement from the source and inbound movement into the destination
7. Mark requisition as fulfilled, partially issued, rejected, or cancelled

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
- reconciliations
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
- `inventory/reconciliations/index`
- `laboratory/stock`
- `laboratory/requisitions`
- `laboratory/movements`
- `laboratory/receipts`
- `pharmacy/stock`
- `pharmacy/requisitions`
- `pharmacy/movements`
- `pharmacy/receipts`
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
- `inventory.reconciliations.manage`
- `inventory.requisitions.manage`
- `inventory.transfers.manage`
- `inventory.reports.view`
- `pharmacy.queue.view`
- `pharmacy.dispense`
- `pharmacy.override_substitution`
- `pharmacy.manage_catalog_links`

Branch isolation should follow the same active-branch approach already used elsewhere in the app.

Within a branch, inventory should also support store-aware access. Main store users may work across branch inventory locations, while pharmacy and laboratory users should primarily see their own stock positions and requisitions.

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
- [ ] Milestone 3 in progress: stock ledger, balances, and reconciliations
- [ ] Milestone 4 in progress: requisitions and inter-store transfers
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

## Milestone 3: Stock Ledger, Balances, And Reconciliations

### Objective

Make stock trustworthy by introducing a movement ledger and controlled reconciliation tools.

### Deliverables

- `InventoryBatch`
- `StockMovement`
- stock balance calculation
- stock-by-location visibility page
- reconciliations with review and approval workflow
- reconciliation presets for cycle count, expiry, and damage

### Milestone Checklist

- [x] Create `inventory_batches`
- [x] Create `stock_movements`
- [x] Create reconciliation records and related items on top of stock-ledger storage
- [x] Build stock summary queries by item, batch, and location
- [x] Add stock-by-location page showing each item's quantity across locations
- [x] Add reconciliation review and approval rules
- [ ] Add cycle-count reconciliation preset/workflow
- [ ] Add expiry and damage reconciliation preset/workflow
- [x] Add movement history page
- [x] Add tests that balances match posted movements

### Definition Of Done

- stock on hand can be explained by posted movements
- users cannot change quantity silently outside approved workflows

### Current Status

The stock ledger foundation is now in place. Posted goods receipts create `InventoryBatch` and `StockMovement` records, the stock-by-location matrix reads from movement-backed balances, a stock movement history page is available for branch users, and user-facing reconciliations now cover both count-style and correction-style stock variance with submit, review, approve, reject, and post steps. The reconciliation storage now also matches the domain language directly through `stock_reconciliations` and `stock_reconciliation_items`. City General Hospital main branch also has seeded inventory users and workflow data so the milestone 3 surfaces can be exercised manually after seeding. Milestone 3 is still not complete, because dedicated cycle-count reconciliation presets and expiry or damage reconciliation tooling are still outstanding.

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

- [x] Create `inventory_requisitions` and items
- [ ] Create `stock_transfers` and items
- [x] Add requisition statuses:
  - draft
  - submitted
  - approved
  - partially_issued
  - fulfilled
  - rejected
  - cancelled
- [ ] Add transfer statuses:
  - draft
  - in_transit
  - received
  - reconciled
  - cancelled
- [x] Add source/destination location handling for same-branch requisitions
- [x] Post requisition issue-out and issue-in movements
- [x] Add requisition pages
- [x] Split requisition experience into requester workspaces for pharmacy/laboratory and an incoming queue for main store
- [x] Add dedicated laboratory and pharmacy inventory navigation for stock, requisitions, movements, and receipts
- [x] Add tests for partial issue
- [ ] Add transfer pages and tests for transfer receipt

### Current Status

Same-branch requisitions are now implemented end to end. Pharmacy and laboratory users create and track requisitions from their own workspaces, submit them to the main store, and follow the resulting approval and issue statuses. Main inventory now behaves as a true incoming queue for requisitions rather than a generic create-and-process surface, so requester-side creation stays in the consuming unit and processing stays in main store. Main store users handle those requests through the incoming requisitions queue, where line review, rejection, and stock issue happen from selected source batches. Issuing posts real stock movements from the source location into the destination location, and partial issue is supported through remaining approved quantities on each line. Requester-side users can now also cancel draft requisitions or withdraw submitted requisitions before review, and cancelled records are kept with audit reason/details while dropping out of the active incoming queue. Inventory access is also store-aware within a branch: main store users can work across branch locations, while pharmacy and laboratory users are scoped to their own locations for stock views, receipts, movements, and destination-side requisitions. Requisition permissions are also now separated into create, submit, cancel, review, and issue abilities so requester and processor responsibilities are clearer. Inter-store transfer documents are still pending, so milestone 4 remains in progress.
The requisition code path itself has also been simplified: incoming queue rules are now centralized in `InventoryRequisitionWorkflow`, workspace access decisions now flow through `InventoryRequisitionAccess`, and the UI/controller contract now prefers `fulfilling` and `requesting` location language instead of carrying duplicate vocabularies through most of the module. The remaining dead compatibility wrappers in `InventoryLocationAccess` have also been removed, so the requisition support layer now reads much closer to the real business flow.

Planning note:

- transfers are intentionally deferred for now
- requisitions plus reconciliations are the active internal-movement workflow in the current application state

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
- [x] Add seeders for sample locations, suppliers, and items
- [ ] Add end-to-end tests for:
  - receive stock
  - reconcile stock
  - transfer stock
  - issue requisition
  - dispense prescription
- [ ] Add navigation links from modules and sidebar
- [x] Add seeded inventory users for main store, pharmacy, and laboratory manual testing
- [ ] Update `system.md` after implementation lands
- [ ] Update `hospital_database_schema.md` to match final table design

### Definition Of Done

- the inventory module is stable, discoverable, and documented
- downstream teams can build on it without reworking the stock foundation

---

## 12) Suggested Build Order

The safest implementation order from the current application state is:

1. inventory catalog and locations
2. suppliers and purchasing
3. receipt-driven stock movements and batches
4. reconciliations
5. requisitions and transfers
6. pharmacy dispensing
7. alerts, reporting, and test hardening

### Recommended Next Step From Here

Transfers are still part of the long-term inventory plan, but they are intentionally deferred right now.

The next best implementation step from the current application state is Milestone 5: pharmacy dispensing against real stock.

Why this is the right next move now:

- requisitions already cover the current requester-to-main-store internal supply flow
- procurement, receiving, stock ledger, and requisitions are now strong enough to support true dispense validation
- pharmacy is the next major operational workflow still missing from the inventory story
- dispensing is the point where inventory begins affecting patient-facing medication fulfillment directly

Recommended dispensing workflow:

1. pharmacy queue lists pending prescription items
2. pharmacist selects batch and dispense quantity from pharmacy stock
3. system validates available stock in the pharmacy location
4. system posts dispense movements
5. prescription line and header statuses update for partial or full dispense
6. batch, expiry, quantity, and counselling details are stored as dispense snapshots

After dispensing, the next major step should be:

1. transfer workflow, if still needed operationally
2. or milestone 6 reporting and alerts, if the immediate priority is operational visibility

---

## 13) Bottom Line

The right “full inventory module” for this app is broader than pharmacy alone. It should become the stock-control foundation for pharmacy, lab consumables, and general hospital supplies, while still giving pharmacy its own operational queue and dispensing workflow.

The most important architectural choice is to build inventory around locations, batches, and immutable movements first. Once that foundation exists, procurement, requisitions, transfers, and dispensing can all fit into one coherent system instead of becoming disconnected quantity-update features.
