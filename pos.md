# Pharmacy POS Module Review and Implementation Guide

**Date:** April 19, 2026  
**Goal:** Explain how to add a pharmacy point-of-sale workflow to this application, what the current codebase already provides, what is still missing, and how to implement a clean pharmacy-only counter-sale flow without damaging the existing prescription-dispensing workflow.

---

## 1) Current Read

This codebase is already strong enough to support a pharmacy POS, but the POS itself is not yet implemented.

What already exists today:

- tenant-aware and branch-aware application structure
- staff roles and permissions
- pharmacy-specific workspace routes and sidebar entry points
- inventory items, locations, batches, receipts, requisitions, and stock movements
- pharmacy dispensing from prescription workflow
- batch-aware stock control foundations
- payment and billing foundations elsewhere in the app

What does **not** exist yet:

- a pharmacy sales cart
- a fast walk-in retail checkout screen
- OTC sale posting workflow
- pharmacy receipt / till workflow
- direct POS payment capture for walk-in customers
- pharmacy refund or void workflow

So the system already has the **inventory control backbone** for a pharmacy POS, but it still needs the **cashier-style selling layer**.

---

## 2) What the App Already Gives a POS

### 2.1 Tenant, Branch, and User Scoping Already Exist

The app is already multi-tenant and branch-aware.

Important existing foundations:

- `tenants`
- `facility_branches`
- `users`
- `staff`
- role and permission management

This matters because a POS sale should always belong to:

- one tenant
- one branch
- one user
- one physical dispensing location

That structure already fits the app naturally.

### 2.2 Pharmacy Inventory Locations Already Exist

The inventory model already supports pharmacy-specific stock locations.

Relevant parts:

- [app/Models/InventoryLocation.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InventoryLocation.php)
- [app/Support/InventoryLocationAccess.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryLocationAccess.php)
- [app/Support/InventoryWorkspace.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php)

The most important field for POS is:

- `is_dispensing_point`

That means a POS sale can be anchored to a real pharmacy counter location instead of a vague branch-level stock pool.

### 2.3 Inventory Items and Batches Already Support Stock-Controlled Sales

The POS should not maintain a separate product stock ledger.

This codebase already has:

- `inventory_items`
- `inventory_batches`
- `stock_movements`

So a sale can deplete real stock from real batches in the same way pharmacy dispensing already does.

That is a major advantage, because it avoids:

- duplicate stock balances
- manual reconciliation between pharmacy and inventory
- separate OTC stock logic

### 2.4 Pharmacy Workspaces Already Exist in Navigation

The app already has a dedicated pharmacy navigation section in:

- [resources/js/components/app-sidebar.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/app-sidebar.tsx)
- [app/Support/InventoryNavigationContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php)

That means a POS can be introduced as a natural additional pharmacy entry point such as:

- `Pharmacy POS`
- `Sales`
- `Walk-In Sales`

without redesigning the whole navigation system.

### 2.5 Pharmacy Dispensing Already Shows the Correct Separation Pattern

The current pharmacy work already establishes an important architectural principle:

- prescription dispensing is one workflow
- stock posting is another supporting concern

The POS should follow the same pattern:

- sale/cart workflow at the UI layer
- stock movement posting at the inventory layer

That makes the system more consistent and easier to audit.

---

## 3) What the POS Should Be

The pharmacy POS should be a **fast retail pharmacy checkout workflow** for sales that are **not** part of the normal visit-prescription-dispense flow.

Typical examples:

- OTC medicine sale
- walk-in purchase without visit registration
- repeat purchase where no clinical encounter is needed
- front-counter pharmacy sale of stocked items

The POS should **not** replace:

- doctor prescription authoring
- pharmacy queue dispensing
- visit billing

Instead, it should run **alongside** those workflows.

### The Correct Conceptual Split

Use the system this way:

- **Clinical prescription workflow**: doctor prescribes, pharmacy dispenses, stock reduces, prescription status changes
- **Pharmacy POS workflow**: cashier/pharmacist selects items, takes payment, stock reduces, sale receipt is produced

That separation prevents the system from mixing patient treatment fulfillment with retail checkout.

---

## 4) Recommended Real-Life POS Workflow

### Step 1: Cashier Opens Pharmacy POS

The user enters the pharmacy POS page and chooses:

- dispensing location
- active cart
- walk-in customer details if needed

The location should always be a real pharmacy dispensing point.

### Step 2: User Adds Items to Cart

The POS should search `inventory_items` limited to:

- active items
- pharmacy-sellable items
- branch-accessible stock

The UI should show:

- item name
- generic name
- available quantity in selected location
- selling price
- dosage form or pack description

### Step 3: System Validates Stock

Before finalizing the sale, the app should validate:

- the item is active
- the selected location is accessible to the user
- enough stock exists in the selected location
- batch allocations are valid if batch tracking is required

### Step 4: User Takes Payment

At checkout, the POS should capture:

- gross amount
- discount
- paid amount
- balance amount
- change amount
- payment method
- transaction reference where needed

This should be stored on POS-specific payment records rather than forced through `visit_billings`.

### Step 5: Sale Is Finalized

When the sale is confirmed:

- a POS sale document is created
- selected batch allocations are attached to line items
- `stock_movements` are posted using a POS sale source document
- receipt data is preserved

### Step 6: Receipt and Audit Trail

The system should preserve:

- what was sold
- quantity sold
- price used
- discount used
- batch used
- who sold it
- where it was sold
- when it was sold
- how it was paid

---

## 5) Recommended Scope for Version 1

Version 1 of the pharmacy POS should be deliberately narrow.

### Recommended V1 Features

- open active cart
- add/remove cart items
- select dispensing location
- show stock availability
- batch allocation
- finalize sale
- capture one or more payments
- print or view receipt
- basic completed-sales history

### Features That Can Wait

- returns and refunds
- parked carts
- customer accounts
- loyalty logic
- tax engine
- barcode scanning
- supplier promotions
- split-tender complexity beyond basic payments

That smaller scope is much safer and gets a usable counter-sale workflow live faster.

---

## 6) Recommended Data Model

The POS should use clearly pharmacy-specific tables.

That avoids future collision with:

- general clinic cashier flows
- visit billing
- inpatient pharmacy charging
- future retail modules outside pharmacy

### Recommended Tables

- `pharmacy_pos_sales`
- `pharmacy_pos_sale_items`
- `pharmacy_pos_sale_item_allocations`
- `pharmacy_pos_payments`
- `pharmacy_pos_carts`
- `pharmacy_pos_cart_items`
- `pharmacy_pos_cart_item_allocations`

### 6.1 `pharmacy_pos_sales`

This should be the completed checkout header.

Recommended fields:

- `id`
- `tenant_id`
- `facility_branch_id`
- `inventory_location_id`
- `sale_number`
- `sale_type`
- `gross_amount`
- `discount_amount`
- `paid_amount`
- `balance_amount`
- `change_amount`
- `customer_name`
- `customer_phone`
- `status`
- `sold_at`
- `notes`
- `created_by`
- `updated_by`
- timestamps
- soft deletes

Recommended statuses:

- `draft`
- `completed`
- `cancelled`
- `refunded`

### 6.2 `pharmacy_pos_sale_items`

This stores the sold lines.

Recommended fields:

- `id`
- `pharmacy_pos_sale_id`
- `inventory_item_id`
- `quantity`
- `unit_price`
- `discount_amount`
- `line_total`
- `notes`
- timestamps

Important detail:

- `unit_price` should be a snapshot
- not just a live lookup from item master data

### 6.3 `pharmacy_pos_sale_item_allocations`

This links each sold line to the batches actually used.

Recommended fields:

- `id`
- `pharmacy_pos_sale_item_id`
- `inventory_batch_id`
- `quantity`
- `unit_cost_snapshot`
- `batch_number_snapshot`
- `expiry_date_snapshot`
- timestamps

This is what keeps the POS audit trail consistent with inventory.

### 6.4 `pharmacy_pos_payments`

Payments should be explicit and separate.

Recommended fields:

- `id`
- `pharmacy_pos_sale_id`
- `receipt_number`
- `amount`
- `payment_method`
- `reference_number`
- `payment_date`
- `is_refund`
- `notes`
- `created_by`
- `updated_by`
- timestamps

### 6.5 `pharmacy_pos_carts`

This supports the active checkout session.

Recommended fields:

- `id`
- `tenant_id`
- `facility_branch_id`
- `inventory_location_id`
- `user_id`
- `cart_number`
- `hold_reference`
- `customer_name`
- `customer_phone`
- `status`
- `notes`
- `held_at`
- `converted_at`
- timestamps

Recommended statuses:

- `active`
- `held`
- `converted`
- `abandoned`

### 6.6 `pharmacy_pos_cart_items`

This stores draft cart lines.

Recommended fields:

- `id`
- `pharmacy_pos_cart_id`
- `inventory_item_id`
- `quantity`
- `unit_price`
- `discount_amount`
- `notes`
- timestamps

### 6.7 `pharmacy_pos_cart_item_allocations`

This stores planned or reserved batch allocations for the cart.

Recommended fields:

- `id`
- `pharmacy_pos_cart_item_id`
- `inventory_batch_id`
- `quantity`
- timestamps

---

## 7) Recommended Backend Entry Points

### Controllers

- `PharmacyPosController`
- `PharmacyPosCartController`
- `PharmacyPosSaleController`
- `PharmacyPosPaymentController`
- `PharmacyPosReceiptController`

### Actions

- `OpenPharmacyPosCart`
- `AddItemToPharmacyPosCart`
- `UpdatePharmacyPosCartItem`
- `AllocatePharmacyPosCartItemBatches`
- `FinalizePharmacyPosSale`
- `RecordPharmacyPosPayment`
- `VoidPharmacyPosSale`
- `RefundPharmacyPosSale`

### Requests

- `StorePharmacyPosCartRequest`
- `StorePharmacyPosCartItemRequest`
- `UpdatePharmacyPosCartItemRequest`
- `FinalizePharmacyPosSaleRequest`
- `StorePharmacyPosPaymentRequest`

### Support Services

- `PharmacyPosPricingService`
- `PharmacyPosStockAllocator`
- `PharmacyPosReceiptNumberGenerator`
- `PharmacyPosSaleNumberGenerator`
- `PharmacyPosAccessService`

---

## 8) Recommended Pages

### Core POS Pages

- `pharmacy/pos`
- `pharmacy/pos/history`
- `pharmacy/pos/sales/{sale}`
- `pharmacy/pos/carts/{cart}`

### What Each Page Should Do

`pharmacy/pos`

- open or create active cart
- search items
- add cart lines quickly
- show current totals
- capture payment
- finalize sale

`pharmacy/pos/history`

- show completed sales
- filter by date, cashier, location, and sale number

`pharmacy/pos/sales/{sale}`

- show sale detail
- show items, payments, and batch allocations
- support print and later refund/void actions if allowed

`pharmacy/pos/carts/{cart}`

- restore a held cart
- continue editing draft items

---

## 9) How Stock Posting Should Work

This is the most important design rule.

### Do Not Update Item Quantities Directly

The POS should never directly reduce a quantity field on an inventory item.

Always post stock through:

- `stock_movements`

### Recommended Movement Pattern

For each finalized sale item allocation:

- create a `stock_movements` row
- `movement_type = dispense` or a dedicated POS sale movement if later needed
- set source document type to the POS sale class
- set source document id to the sale id

This keeps:

- stock reports correct
- batch balances correct
- expiry reporting correct
- audit history consistent

### Batch Tracking Rule

If batch tracking is enabled for pharmacy:

- the POS UI should require allocation before finalizing the sale

If batch tracking is disabled:

- backend should auto-allocate using FEFO ordering
- earliest valid expiry first

That is the same pattern already used by pharmacy dispensing and should stay consistent.

---

## 10) Pricing and Payments

### 10.1 Pricing

The POS needs a selling price source.

If the current codebase does not yet store pharmacy selling prices cleanly, one of these approaches should be chosen:

1. add POS-specific price fields on `inventory_items`
2. add a pharmacy price table per branch or per location
3. add price lists later if pricing complexity is expected

For version 1, the simplest clean option is:

- a pharmacy selling price snapshot per item line at checkout

### 10.2 Discounts

Support:

- line discount
- optional sale-level discount

Do not overcomplicate discount logic in version 1.

### 10.3 Payments

Payments should be stored on POS records directly.

Do not force OTC sales into:

- visit billing
- inpatient billing
- patient billing ledgers

That would mix two different business flows.

### 10.4 Unpaid or Partially Paid Sales

You have two safe options:

1. only allow fully paid POS completion in version 1
2. allow partial payment and preserve balance on the sale

My recommendation for version 1:

- allow cashiers to complete only fully paid or clearly partially paid sales if the business truly needs it
- otherwise keep it simple and require full payment

---

## 11) Permissions and Roles

The POS should be permissioned separately from general pharmacy dispensing where possible.

Recommended permissions:

- `pharmacy_pos.view`
- `pharmacy_pos.create`
- `pharmacy_pos.complete`
- `pharmacy_pos.void`
- `pharmacy_pos.refund`
- `pharmacy_pos.view_history`

That allows a facility to give:

- cashier-only access
- pharmacist-only access
- supervisor approval for voids or refunds

This matters especially in mixed environments where:

- some staff dispense prescriptions
- some staff mainly run retail counter sales

---

## 12) Recommended Implementation Phases

### ✅ Phase 1: POS Shell and Cart — COMPLETED

Deliverables:

- [x] sidebar link (`Pharmacy POS` entry in app-sidebar.tsx)
- [x] POS page (`pharmacy/pos/index.tsx` — open cart form + item search + cart view)
- [x] draft cart creation (`OpenPharmacyPosCartAction`, `PharmacyPosController`)
- [x] add/remove items (`AddItemToPharmacyPosCartAction`, `RemovePharmacyPosCartItemAction`, `PharmacyPosCartController`)
- [x] totals (computed inline from cart items on the page)
- [x] permissions seeded (`pharmacy_pos` permission group on pharmacist role)
- [x] migrations (`pharmacy_pos_carts`, `pharmacy_pos_cart_items`, `pharmacy_pos_cart_item_allocations`)
- [x] tests (5/5 passing in `PharmacyPosPhase1Test.php`)

Why first:

- gives the pharmacy a usable retail workspace fast
- low risk to stock if finalization is not live yet

### ✅ Phase 2: Sale Finalization and Payments — COMPLETED

Deliverables:

- [x] finalize sale action (`FinalizePharmacyPosSaleAction`)
- [x] payment capture (`RecordPharmacyPosPaymentAction`, `PharmacyPosPaymentController`)
- [x] sale number generation (`PharmacyPosSaleNumberGenerator`)
- [x] checkout page (`pharmacy/pos/checkout.tsx`)
- [x] completed sale detail page (`pharmacy/pos/sales/show.tsx`)
- [x] migrations (`pharmacy_pos_sales`, `pharmacy_pos_sale_items`, `pharmacy_pos_sale_item_allocations`, `pharmacy_pos_payments`)
- [x] models (`PharmacyPosSale`, `PharmacyPosSaleItem`, `PharmacyPosSaleItemAllocation`, `PharmacyPosPayment`)
- [x] tests (6/6 passing in `PharmacyPosPhase2Test.php`)

Why next:

- gives the pharmacy a usable checkout flow
- creates clear document boundaries before stock posting complexity grows

### Phase 3: Batch-Aware Stock Posting

Deliverables:

- batch allocation
- stock validation
- sale movement posting
- completed stock reduction

This is where the POS becomes inventory-controlled.

### Phase 4: History and Operational Controls

Deliverables:

- sale history page
- filtering
- print receipt
- hold cart

### Phase 5: Reversals and Refunds

Deliverables:

- void draft sale
- refund posted sale
- reversal stock movements
- supervisor controls

---

## 13) Important Design Decisions

### Keep POS Separate From Prescription Dispensing

This is the most important design choice.

Do not treat a POS sale like a prescription fulfillment.

Use POS for:

- OTC
- walk-in
- retail

Use dispensing for:

- prescribed medication fulfillment

### Always Sell From a Real Pharmacy Location

Every POS sale should be tied to:

- one `inventory_location_id`

Prefer locations where:

- `type = pharmacy`
- `is_dispensing_point = true`

### Preserve Batch Snapshots

When a sale is finalized, keep the batch snapshot values.

Do not rely on later joins only, because:

- batches may be edited
- reporting needs point-in-time truth

### Keep Payments Inside POS

Do not create unnecessary coupling between POS and visit billing for walk-in sales.

### Start With Fast and Clear UI

The POS screen should feel different from the prescription queue.

It should prioritize:

- speed
- clarity
- minimal clicks
- quick search
- visible totals

not document review density.

---

## 14) Definition of Done for a Pharmacy POS

The pharmacy POS can be considered complete when:

- staff can open a pharmacy cart
- staff can search and add stock items quickly
- the sale is tied to a real pharmacy location
- batch allocation works where required
- sale finalization posts stock movements correctly
- payment is captured and auditable
- receipt history is visible
- completed sales can be reviewed later
- refunds or reversals are controlled and auditable

---

## 15) Can This System Support a Pharmacy-Only Business?

Yes, if fully implemented, this system can support a pharmacy-only business.

But there is an important distinction:

- **technically yes**
- **product-wise it should be packaged differently**

For a pharmacy-only tenant, the system should behave like:

- pharmacy queue for prescriptions
- pharmacy POS for walk-ins
- inventory and stock control
- receipts, suppliers, and purchasing
- payments and cashier workflows

What should usually be hidden or disabled:

- triage
- doctor consultations
- laboratory
- inpatient workflows
- unrelated hospital dashboards

To make that clean, the app should add:

- tenant-level enabled module configuration
- sidebar filtering by enabled module
- route and controller guards for disabled modules
- role templates for pharmacy-only businesses
- onboarding templates for pharmacy tenants

Without that packaging layer, the app can still work, but it will feel like a hospital system with many irrelevant menus visible.

---

## 16) Can This System Support a Dental-Only Clinic?

Yes, the same principle applies to a dental-only clinic.

If a dental module is fully implemented, this platform can absolutely power a dental-only clinic.

The system already has reusable cross-cutting foundations such as:

- tenants
- branches
- users and staff
- roles and permissions
- appointments
- patients
- visit-style workflows
- billing and payments
- inventory

A dental module would mainly need to add the dental-specific workflow layer on top of those foundations.

Examples of dental-specific features:

- dental charting
- tooth-based treatment plans
- procedure catalog
- dental consultation notes
- radiology or image attachments
- dental consumables and materials
- procedure billing

Then the same product-packaging pattern should be applied:

- enable only dental-relevant modules for that tenant
- hide pharmacy, lab, and other hospital modules if unused
- tailor roles, menus, dashboards, and onboarding to dental operations

So the system does not need to be a full hospital in every deployment. It can become a narrower clinic product if modules are properly isolated and enabled selectively.

---

## 17) Bottom Line

The pharmacy POS should be built as:

**retail pharmacy checkout on top of the existing inventory ledger**

not as a shortcut inside prescription dispensing and not as a clone of visit billing.

And more broadly:

- yes, the platform can serve a pharmacy-only business
- yes, it can also serve a dental-only clinic

but to do that well, the codebase should introduce a stronger **module enablement and product-packaging layer** so each tenant sees only the workflows that actually belong to their kind of facility.
