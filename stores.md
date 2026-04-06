# Store Access and Workflow Notes

**Date:** April 6, 2026  
**Context:** Inventory is branch-aware, and now also store-aware inside a branch.

---

## 1) Why This Document Exists

This document explains:

- what the inventory system was doing before store-aware access was introduced
- what changed in the current implementation
- how the system should be used in day-to-day hospital operations
- what the ideal real-life workflow looks like for main store, pharmacy, and laboratory

The goal is to make the inventory behavior easy to understand for future development and testing.

---

## 2) The Problem We Had Before

Before the recent changes, the inventory module was already:

- tenant-aware
- branch-aware
- built around inventory locations
- capable of goods receipt, stock ledger movements, reconciliations, and same-branch requisitions

However, in practice it still behaved too much like one shared branch-wide inventory workspace.

### What That Meant

If a user had inventory permissions, most inventory pages behaved like this:

- they could see stock across all inventory locations in the active branch
- they could create requisitions using any source and destination location in the branch
- they could view item batches and movements from all stores in the branch
- goods receipt location options were not narrowed to the stores that person actually manages

That behavior was acceptable for a main store or central inventory user, but it did not match how many hospitals actually work.

### Why That Was a Problem

In real hospital operations, different stores often behave like separate working spaces even inside the same branch.

Common examples:

- main store
- pharmacy
- laboratory store

These stores are connected, but they are not usually managed as one flat workspace by every inventory-related user.

This means:

- a pharmacist should mainly manage pharmacy stock
- a lab technician should mainly manage lab stock
- the main store should act as the central supply point for internal replenishment
- branch isolation alone is not enough; we also need store-level operational focus

---

## 3) What the System Was Doing Before the Change

### 3.1 Branch Awareness Already Existed

The system already enforced branch isolation through:

- active branch session context
- branch-aware models and scopes
- branch-aware controllers

So users were not leaking data across branches.

### 3.2 But Inventory Access Inside a Branch Was Broad

Within one branch, inventory mostly behaved as if:

- all inventory users were central store users
- all locations in the branch were equally visible
- all inventory movements in the branch belonged to one common workspace

This made the branch inventory feel like a main store control room rather than a set of operational stores.

### 3.3 Requisitions Existed, But Without Store-Specific Access Rules

The requisition flow itself was already good:

- draft
- submitted
- approved
- partially issued
- fulfilled
- rejected
- cancelled

But the users creating or viewing requisitions were not yet scoped according to the actual store they manage.

So the workflow existed, but the operational boundaries were still too broad.

---

## 4) What Changed

The system now has a store-aware access layer inside a branch.

### 4.1 New Core Idea

Inside a branch:

- some users can work across branch inventory locations
- some users should only work within specific store types

The current model is role-based.

### 4.2 New Access Rules

The implementation now treats inventory access like this:

- `store_keeper`, `admin`, `super_admin`, and support users get broad branch inventory location access
- `pharmacist` users are scoped to pharmacy inventory locations
- `lab_technician` users are scoped to laboratory inventory locations

This logic is centralized in:

- `app/Support/InventoryLocationAccess.php`

That service now decides:

- which inventory locations a user can access
- which locations a user can receive goods into
- which locations a user can see in stock views
- which requisitions a user can view
- which requisitions a user can process

### 4.3 Goods Receipt Was Left Operationally Flexible

You asked to keep goods receipt available because stock may be received directly into different stores.

That is what the system now supports.

The rule is:

- users can still receive goods directly into stores
- but only into inventory locations they manage

So:

- a main store user can receive into main store and other branch stores
- a pharmacist can receive directly into pharmacy
- a lab technician can receive directly into lab

This preserves the operational flexibility you wanted without making the whole branch inventory flat again.

### 4.4 Requisitions Now Behave More Like Real Store Replenishment

The requisition flow is now store-aware.

For restricted store users:

- the destination should be one of their own managed locations
- the source is narrowed to the branch main store

That means:

- pharmacy requests stock into pharmacy
- lab requests stock into lab
- main store acts as the central issuing source

Main store users still retain broad branch control and can process source-side requisitions.

### 4.5 Stock Views Are Now Narrowed

The following inventory surfaces now respect store access:

- stock-by-location page
- movement report
- item detail stock batches
- item detail stock movement history
- reconciliation pages
- requisition pages
- goods receipt views and create form

This means a pharmacist no longer sees the whole branch like a store keeper does.

Instead, they see the inventory workspace relevant to pharmacy operations.

### 4.6 Seed Data Was Expanded for Testing

City General Hospital inventory seed data now includes dedicated inventory users:

- `storekeeper@citygeneral.ug`
- `pharmacy@citygeneral.ug`
- `lab@citygeneral.ug`

Password:

- `password`

This makes it easier to test the different inventory perspectives in the same branch.

---

## 5) What the System Does Now

### 5.1 Main Store User

A main store user should now experience inventory as the broad branch supply workspace.

They can:

- view stock across branch stores
- receive goods directly into managed branch locations
- see requisitions involving source or destination locations they can access
- approve and issue requisitions from the main store side
- act like the central stock control user inside the branch

This is closest to the old behavior, but now it is intentional rather than accidental.

### 5.2 Pharmacist

A pharmacist should now experience inventory mainly as a pharmacy workspace.

They can:

- view pharmacy stock
- view pharmacy-related movements
- receive goods directly into pharmacy if stock is delivered there
- create requisitions whose destination is pharmacy
- see requisitions involving pharmacy

They should not behave like the branch-wide inventory controller unless they also hold a broader inventory role.

### 5.3 Lab Technician

A lab technician should now experience inventory mainly as a laboratory store workspace.

They can:

- view lab stock
- view lab-related movements
- receive goods directly into lab if stock is delivered there
- create requisitions whose destination is lab
- see requisitions involving lab

Like pharmacy users, they are now operating inside a store-specific stock space.

---

## 6) The Ideal Real-Life Flow

This is the most realistic operational model for the current system design.

## 6.1 Main Store

Main store should function as the central branch supply source.

Typical responsibilities:

- receive bulk deliveries from suppliers
- hold buffer stock
- fulfill pharmacy and lab requisitions
- monitor branch-wide stock position
- handle reconciliations and corrections when needed

Typical flow:

1. Supplier delivers stock.
2. Main store receives goods directly into main store, or occasionally into another store if delivery goes there physically.
3. Goods receipt is posted.
4. Stock becomes visible in the ledger and stock-by-location view.
5. Pharmacy and lab request replenishment from main store through requisitions.
6. Main store approves and issues from available source batches.

## 6.2 Pharmacy

Pharmacy should function as a store that both holds stock and dispenses it.

Typical responsibilities:

- manage on-hand pharmacy stock
- request replenishment from main store
- receive direct deliveries into pharmacy when applicable
- later, dispense prescribed items from pharmacy stock

Typical flow:

1. Pharmacist notices low stock in pharmacy.
2. Pharmacist creates a requisition from main store to pharmacy.
3. Main store reviews and approves.
4. Main store issues from selected batches.
5. Pharmacy stock increases through requisition-in movements.
6. Pharmacy operates from its own store-level stock position.

## 6.3 Laboratory

Lab should function as a store for reagents and consumables.

Typical responsibilities:

- manage lab stock
- request replenishment from main store
- receive direct deliveries into lab when needed
- later, consume stock through lab workflows

Typical flow:

1. Lab detects low stock or upcoming workload needs.
2. Lab creates a requisition from main store to lab store.
3. Main store approves and issues.
4. Lab receives stock through requisition-in movements.
5. Lab operates from its own visible stock balance.

---

## 7) What This Means Operationally

The branch now behaves like a small internal supply chain rather than one shared store screen.

### Better Mental Model

The new practical mental model is:

- branch = organizational boundary
- store = operational inventory workspace
- main store = central supplier inside the branch
- pharmacy and lab = controlled destination stores

This is much closer to real-life use in hospitals where:

- stock enters at one or more receiving points
- central stores redistribute internally
- service areas should not be editing or viewing every other store unnecessarily

---

## 8) What Is Still Missing for the Ideal End State

The system is much closer now, but the ideal real-life implementation would go even further.

### Recommended Next Improvements

#### 8.1 Explicit Store Assignment

Right now store access is role-based by location type.

That is a good first implementation, but the more precise real-world version would be:

- assign users or staff to specific inventory locations
- allow one pharmacist to manage Pharmacy A but not Pharmacy B
- allow one lab lead to manage only specific lab stores

This would be better than relying only on role names.

#### 8.2 Main Store Source Enforcement

Right now restricted users are effectively guided toward main store as the source through access filtering.

The ideal version should make this business rule more explicit and configurable:

- some requisitions must come only from main store
- others might allow sub-store to sub-store requests later if desired

#### 8.3 Transfer Workflow

Requisition is now in place, but transfers are still pending.

That matters because real operations often also need:

- controlled store-to-store transfer documents
- in-transit state
- receiving confirmation
- discrepancy handling at destination

#### 8.4 Pharmacy Dispensing

The pharmacy store is now visible and manageable, but the final pharmacy operational loop is not complete until:

- prescriptions are dispensed against pharmacy stock
- batch-aware stock depletion occurs from dispensing
- pharmacy queue is fully integrated

#### 8.5 Lab Consumption

Lab currently has a better store boundary now, but ideal usage also needs:

- reagent and consumable usage to consume stock from lab inventory
- traceability from clinical/lab activity to stock depletion

---

## 9) Summary of the Current Direction

Before the change:

- inventory inside a branch behaved too much like one shared main store workspace

After the change:

- inventory is still branch-aware
- it is now also store-aware inside the branch
- main store remains the broad branch inventory controller
- pharmacy and lab users now operate within their own store contexts
- goods receipt remains flexible enough for direct receiving into multiple stores
- requisitions now better reflect internal hospital replenishment flow

The current design is a strong practical middle ground:

- simple enough to use now
- realistic enough for hospital operations
- extensible later into explicit store assignments, transfers, dispensing, and store-specific consumption workflows

---

## 10) Practical Testing Notes

To test the current behavior from seeded data:

1. Run a fresh seed.
2. Sign in as each of the seeded inventory users.
3. Compare what each user sees in:
   - stock by location
   - inventory item detail
   - goods receipts
   - requisitions
   - reconciliations
4. Confirm:
   - main store user sees broad branch inventory
   - pharmacy user sees pharmacy-oriented stock and requisition workspace
   - lab user sees lab-oriented stock and requisition workspace

Recommended test credentials:

- `storekeeper@citygeneral.ug`
- `pharmacy@citygeneral.ug`
- `lab@citygeneral.ug`

Password:

- `password`
