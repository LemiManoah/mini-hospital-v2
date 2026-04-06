# Requisitions Module Review

## Purpose of This Note

This document reviews the current requisitions module as it exists in the application today, explains how it currently works, identifies where the design is getting more complex than necessary, and proposes improvements while the module is still easy to reshape.

The goal is not to say the module is broken. The core workflow is already working:

- Pharmacy can raise requisitions
- Laboratory can raise requisitions
- Main store can receive them as incoming work
- Main store can approve, reject, and issue stock
- Stock movements are posted into the ledger

That is a strong foundation.

The main issue now is that the workflow logic, naming, routing, permissions, and UI responsibilities are carrying more complexity than they should for a module that should feel simple and operational.

## Implementation Update

The first two simplification phases from this review have now been applied in the codebase:

- Phase 1 completed:
  - main inventory requisitions are now queue-only
  - generic inventory-side requisition create/store routes have been removed
  - requisition creation now happens from requester workspaces only
  - draft requisitions are no longer viewable from the main inventory queue
- Phase 2 completed:
  - requisition permissions are now split into:
    - `inventory_requisitions.create`
    - `inventory_requisitions.submit`
    - `inventory_requisitions.review`
    - `inventory_requisitions.issue`
  - requester workspaces use create/submit
  - main store queue uses review/issue
- Phase 3 completed:
  - the serialized requisition contract now exposes `fulfilling_location` and `requesting_location`
  - the requisition UI has started using requester/fulfiller language instead of leaning only on `source` and `destination`
- Phase 4 completed:
  - requester workspaces can now cancel draft requisitions
  - requester workspaces can withdraw submitted requisitions before review
  - `cancelled` is now a real audited workflow state with reason and actor fields
  - cancelled requisitions no longer appear in the main-store incoming queue
- Phase 5 completed:
  - the requisition show page is now split into smaller workflow-focused components
  - requester actions, queue review, queue issuing, summary, and line display are no longer all embedded in one page body
- Phase 6 completed:
  - incoming queue rules now live in `InventoryRequisitionWorkflow`
  - requester location types and hidden queue statuses are no longer hard-coded only inside the controller
  - the serialized requisition contract now prefers `fulfilling_location` and `requesting_location` without carrying duplicate legacy keys
- Phase 7 completed:
  - workspace and access decisions now flow through `InventoryRequisitionAccess`
  - the controller no longer carries as much inline requester-vs-main-store authorization logic
  - index location resolution, queue visibility, and workspace matching now have a narrower home
- Phase 8 completed:
  - dead compatibility wrappers were removed from `InventoryLocationAccess`
  - inventory-location requisition relations now expose `fulfillingRequisitions` and `requestingRequisitions` as the primary names
  - requisition actions now reload `fulfillingLocation` and `requestingLocation` directly

The findings below are still useful because they explain why those changes were needed and what complexity remains after them.

## Current Requisition Flow

### Business Flow

The current flow behaves like this:

1. A pharmacy or laboratory user creates a requisition.
2. The requisition is saved as a `draft`.
3. The requester submits it to main store.
4. Main store sees it in the `Incoming Requisitions` queue.
5. Main store approves quantities or rejects the request.
6. Main store issues stock from selected source batches.
7. The system creates:
   - `requisition_out` movement from source location
   - `requisition_in` movement into destination location
8. The requisition becomes:
   - `approved`
   - `partially_issued`
   - `fulfilled`
   - or `rejected`

### Technical Shape

The current implementation is centered around:

- [InventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InventoryRequisition.php)
- [InventoryRequisitionItem.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InventoryRequisitionItem.php)
- [InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php)
- [CreateInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/CreateInventoryRequisition.php)
- [SubmitInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/SubmitInventoryRequisition.php)
- [ApproveInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/ApproveInventoryRequisition.php)
- [RejectInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/RejectInventoryRequisition.php)
- [IssueInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/IssueInventoryRequisition.php)
- [InventoryLocationAccess.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryLocationAccess.php)
- [InventoryWorkspace.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php)
- [InventoryNavigationContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php)

The UI is also shared heavily across three workspaces:

- inventory
- laboratory
- pharmacy

using the same controller methods and the same base page components with different route context.

## Developer Guide

This section describes the requisitions module as it exists now after the refactor phases above.

### Core Mental Model

Think about requisitions in two roles:

- requester workspaces:
  - laboratory
  - pharmacy
- fulfiller workspace:
  - main inventory / main store

That means:

- lab and pharmacy create, submit, track, and optionally cancel or withdraw their own requisitions
- main store reviews, approves, rejects, and issues them
- stock only moves when main store posts the issue step

### Current Terminology

Inside the code, requisitions should now be read with these business terms first:

- `fulfillingLocation`
- `requestingLocation`
- `fulfillingRequisitions`
- `requestingRequisitions`

The remaining `source` and `destination` naming is now mostly intentional schema naming, not the preferred domain vocabulary.

### Intentional Naming Leftovers

These are still present on purpose:

- database columns:
  - `source_inventory_location_id`
  - `destination_inventory_location_id`
- request payload fields and form keys that still submit those column names
- feature-test local variables such as `$sourceLocation` and `$destinationLocation`

These were kept because changing schema and request contracts would be a larger migration than the refactor needed. The business-facing and code-navigation-facing names should continue to prefer `fulfilling` and `requesting`.

### Main Files And Responsibilities

- [InventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InventoryRequisition.php)
  - requisition model
  - status transition helpers
  - primary requisition relationships
- [InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php)
  - main orchestration layer for list, create, show, submit, cancel, approve, reject, and issue
- [InventoryRequisitionWorkflow.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryRequisitionWorkflow.php)
  - queue-definition rules
  - requester location types
  - hidden incoming statuses
- [InventoryRequisitionAccess.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryRequisitionAccess.php)
  - requester vs main-store access decisions
  - workspace matching
  - location resolution for requester and queue screens
- [InventoryLocationAccess.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryLocationAccess.php)
  - lower-level inventory location access rules reused by requisitions and other inventory modules
- [InventoryWorkspace.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php)
  - determines whether the route is inventory, laboratory, or pharmacy
- [InventoryNavigationContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php)
  - chooses page labels, breadcrumbs, section titles, and hrefs
- [StoreInventoryRequisitionRequest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Requests/StoreInventoryRequisitionRequest.php)
  - requester-side validation and workspace-aware location validation
- [CreateInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/CreateInventoryRequisition.php)
  - creates draft requisitions
- [SubmitInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/SubmitInventoryRequisition.php)
  - moves draft to submitted
- [CancelInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/CancelInventoryRequisition.php)
  - cancels draft or withdraws submitted requisitions before review
- [ApproveInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/ApproveInventoryRequisition.php)
  - records approved quantities
- [RejectInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/RejectInventoryRequisition.php)
  - records rejection
- [IssueInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/IssueInventoryRequisition.php)
  - validates available stock and posts `requisition_out` / `requisition_in` movements
- [resources/js/pages/inventory/requisitions](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/inventory/requisitions)
  - shared requisition page implementations reused by inventory, lab, and pharmacy routes

### Request Path

When tracing a requisition request, use this order:

1. Route
   - check whether the request came through `inventory`, `laboratory`, or `pharmacy`
2. Workspace
   - [InventoryWorkspace.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php) decides the behavioral workspace
3. Navigation
   - [InventoryNavigationContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php) decides labels and links
4. Permission middleware
   - controller middleware checks `view`, `create`, `submit`, `cancel`, `review`, or `issue`
5. Requisition access layer
   - [InventoryRequisitionAccess.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryRequisitionAccess.php) decides whether the current workspace can view or process the requisition
6. Validation or action
   - request objects validate locations and payloads
   - action classes enforce status transitions and stock rules

### Workspace Behavior

#### Inventory Workspace

- acts as main-store queue
- lists only incoming requester-to-main-store requisitions
- can show submitted, approved, partially issued, fulfilled, rejected
- cannot create requester-side requisitions
- owns approve, reject, and issue

#### Laboratory Workspace

- can create requisitions only for laboratory locations
- can only request from a fulfilling main-store location
- can submit and track requisitions
- can cancel draft or withdraw submitted requisitions before review

#### Pharmacy Workspace

- same flow as laboratory, but scoped to pharmacy locations

### Status Flow

The effective requisition lifecycle is:

- `draft`
- `submitted`
- `approved`
- `partially_issued`
- `fulfilled`
- `rejected`
- `cancelled`

Who can do what:

- requester can:
  - create draft
  - submit draft
  - cancel draft
  - withdraw submitted before review
- main store can:
  - approve submitted
  - reject submitted
  - issue approved or partially issued

### Stock Posting Behavior

Approval does not move stock.

Issue is the ledger event that moves stock:

- `requisition_out` from the fulfilling location
- `requisition_in` into the requesting location

So if stock looks wrong, debug the issue action and movement history before debugging the approval step.

### How To Debug Common Problems

If a user says “I cannot see this requisition”:

1. confirm the route workspace
2. confirm the user has the route permission
3. confirm the requisition matches the workspace
4. confirm the user can access the relevant requesting or fulfilling location

If a user says “I can see it but cannot process it”:

1. check `inventory_requisitions.review` or `inventory_requisitions.issue`
2. check whether they manage the fulfilling location
3. check the requisition status

If a user says “it disappeared from the queue”:

1. check whether it is still `draft` or now `cancelled`
2. check whether it still matches the incoming queue rule in [InventoryRequisitionWorkflow.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryRequisitionWorkflow.php)

If a user says “issue failed”:

1. check approved vs remaining quantities
2. check available batch balances in the fulfilling location
3. check that the selected batches belong to the correct location

### Tests That Matter Most

The most important current tests are:

- [InventoryRequisitionControllerTest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/tests/Feature/Controllers/InventoryRequisitionControllerTest.php)
  - full workflow behavior and route access
- [InventoryRequisitionWorkflowTest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/tests/Unit/Support/InventoryRequisitionWorkflowTest.php)
  - queue-definition rules
- [InventoryRequisitionAccessTest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/tests/Unit/Support/InventoryRequisitionAccessTest.php)
  - workspace and access behavior

### Current Minor Naming Leftovers

These are the remaining small leftovers I found:

- database-backed field names still use `source_` and `destination_`
- form field names still submit those database names
- some feature-test local variables still use `$sourceLocation` and `$destinationLocation`

These are minor and acceptable for now. The important part is that:

- model relationships
- controller payloads
- location relationships
- access helpers
- workflow helpers

now all speak mostly in `fulfilling` / `requesting` terms.

## What the Module Is Doing Well

Before looking at issues, it is worth stating what is already good:

- The stock movement side is conceptually sound.
- Requisitions fit the branch-local workflow well.
- Pharmacy and lab can now operate as requester workspaces.
- Main store can work from an incoming queue.
- The source-batch allocation logic in issuing is a good control.
- Role/location-based access is already preventing a lot of incorrect actions.
- The module is already much closer to real hospital workflow than a generic “internal stock request” page.

So the right move is refinement, not replacement.

## Detailed Findings

### Developer Complexity: Why This Module Is Harder to Trace Than It Looks

One important issue is not just business complexity. It is code-flow complexity.

At first glance, requisitions look like one module:

- one controller
- one model
- a few pages

But in practice the flow is spread across:

- routes
- workspace detection
- navigation context
- sidebar guards
- location access rules
- controller branching
- page-level branching
- permission middleware
- action-level validation and aborts

That means a developer trying to understand “why can this user see or not see this requisition?” or “why does this page behave differently here?” has to jump through several files before the answer becomes clear.

#### Main Complexity Sources

##### 1. Route Prefix Decides Workspace Behavior

The module changes behavior based on whether the current route starts with:

- `inventory.`
- `laboratory.`
- `pharmacy.`

This happens in:

- [InventoryWorkspace.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryWorkspace.php)
- [InventoryNavigationContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryNavigationContext.php)

That means the same controller method can behave differently depending on route name, not method name.

For example:

- `InventoryRequisitionController@index`
- `InventoryRequisitionController@create`
- `InventoryRequisitionController@show`

all change behavior based on inferred workspace.

This is powerful, but it is also easy to miss when reading the controller in isolation.

##### 2. The Same Controller Serves Three Different Mental Models

[InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php) is doing all of these:

- generic inventory queue behavior
- lab requester behavior
- pharmacy requester behavior

That means methods like `index()` and `create()` contain branching such as:

- if inventory workspace: treat as incoming queue
- otherwise: treat as requester workspace

So the controller is not only a CRUD controller. It is also acting like a workflow router.

This is one of the biggest reasons the code is harder to reason about than the feature first appears.

##### 3. Sidebar Visibility Is Only Part of Access Control

The sidebar decides what links a user sees, but that is not the real permission boundary.

The real behavior is split across:

- sidebar conditions in [app-sidebar.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/components/app-sidebar.tsx)
- controller middleware in [InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php)
- location-based rules in [InventoryLocationAccess.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryLocationAccess.php)
- workspace matching in `abortUnlessMatchesWorkspace()`

So if a developer asks:

- “why is this menu hidden?”
- “why does this route still 403 if the menu is visible?”
- “why does this route 404 from one workspace but 403 from another?”

the answer is often distributed across multiple layers.

This is a legitimate source of confusion.

##### 4. Navigation Context and Access Context Are Different Systems

The module has both:

- `InventoryWorkspace`
- `InventoryNavigationContext`

These are related, but not the same thing.

`InventoryWorkspace` decides behavioral context:

- which component to render
- which route names to use
- which location types belong to the workspace

`InventoryNavigationContext` decides UI text context:

- page titles
- breadcrumbs
- hrefs
- section labels

So a developer has to know:

- `InventoryWorkspace` changes logic
- `InventoryNavigationContext` changes wording and links

This split is reasonable, but it is another layer to hold in mind when tracing the module.

##### 5. The Module Uses Both Permission Rules and Location Rules

Access is not determined only by role permission.

A user may have:

- `inventory_requisitions.view`
- `inventory_requisitions.update`

but still be blocked because:

- the requisition source location is not one they manage
- the destination location is not in their workspace
- they are opening the requisition through the wrong route context

That means the module effectively has two access systems:

1. permission-based access
2. location/workspace-based access

That is correct from a security perspective, but it raises the mental load for maintenance and debugging.

##### 6. Shared Pages Are Reused, but Behavior Is Not Truly Shared

The same page files are reused under inventory, laboratory, and pharmacy.

That sounds simple, but the page behavior changes substantially based on:

- `navigation.key`
- `isRequesterWorkspace`
- `canManageQueue`
- `canSubmitToMainStore`

Especially in:

- [resources/js/pages/inventory/requisitions/create.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/inventory/requisitions/create.tsx)
- [resources/js/pages/inventory/requisitions/show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/inventory/requisitions/show.tsx)

So the code is “shared” structurally, but not conceptually.

That makes it easy for a developer to mistakenly change one branch of the experience while assuming they are editing a generic page.

##### 7. Access Failures Can Happen at Multiple Different Stages

A requisition can be blocked:

- in the sidebar because the link is not shown
- by route middleware because permission is missing
- in request validation because locations are invalid
- in controller guards because workspace access is denied
- in action-level business logic because status transition is not allowed

This means debugging a failure often requires asking:

- did the user reach the page?
- did the route authorize?
- did the workspace match?
- did the location access pass?
- did the status transition pass?

This layered design is strong defensively, but it is not easy to trace quickly.

#### Suggested Developer-Facing Improvements

To reduce developer confusion, these improvements would help a lot:

1. Add a short architecture note near the controller or in this document describing the full request path:
   - route -> workspace -> navigation context -> sidebar visibility -> controller guard -> action

2. Consolidate workflow rules into one place, for example:
   - `InventoryRequisitionWorkflow`
   - or a policy/service dedicated to requester vs processor behavior

3. Split requester and processor pages more clearly so frontend branching reduces.

4. Replace some implicit route-name-based behavior with explicit workspace input where reasonable.

5. Add more tests that assert not just outcomes, but route/workspace expectations, so future developers can infer intent from tests.

6. Keep this document up to date whenever requisition routing or workspace behavior changes.

### 1. The Main Store Queue Was Not Fully Queue-Only

Originally, the intended workflow was:

- Pharmacy/Lab create requests
- Main store processes them

At the time of review, the generic inventory create/store routes still existed:

- [web.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/routes/web.php#L183)

And the controller still supported generic inventory-side creation:

- [InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php#L141)

That meant a main-store-style user could still create requisitions directly from the inventory module, even though the business workflow had already shifted to requester-side creation.

#### Why this matters

This leaves the module with two competing mental models:

- requisitions are requests raised by consuming units
- requisitions are generic inventory documents that anyone in inventory can create

That ambiguity will keep leaking into permissions, tests, UI copy, and support questions.

#### Outcome

This has now been corrected.

- main inventory requisitions are queue-only
- generic inventory create/store routes are gone
- requester workspaces now own requisition creation

#### Suggested follow-on improvement

Keep tests and docs aligned so this does not regress later.

This was the single clearest simplification, and it is now in place.

### 2. The Data Model Uses Fulfillment Language More Than Requester Language

Today, a requisition created by lab or pharmacy stores:

- `source_inventory_location_id` = main store
- `destination_inventory_location_id` = lab or pharmacy

That is technically valid for stock movement.

But mentally, the requester thinks:

- “we are requesting stock”
- not “main store is the source and we are the destination”

This mismatch is why the UI keeps having to translate the same document back into human language like:

- `Issuing Store`
- `Requesting Unit`

in:

- [create.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/inventory/requisitions/create.tsx#L142)
- [show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/inventory/requisitions/show.tsx#L299)

#### Why this matters

The code and the user mental model are now different enough that every page has to compensate.

That creates:

- extra conditionals
- more UI wording branches
- slower onboarding for future developers

#### Suggested improvement

Keep the current database fields if needed, but introduce clearer domain naming in code and serialization:

- `requestingLocation`
- `fulfillingLocation`

or

- `requestedByLocation`
- `issuedFromLocation`

Then the UI can use those names directly, and the controller can expose both if needed.

This is a low-risk refactor with high readability value.

### 3. One Permission Was Carrying Too Many Responsibilities

At the time of review:

- `inventory_requisitions.view` controls reading
- `inventory_requisitions.create` controls creating
- `inventory_requisitions.update` controlled:
  - submit
  - approve
  - reject
  - issue

See:

- [InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php#L50)

#### Why this matters

These actions belong to different roles.

Requester actions:

- create
- edit draft
- submit
- possibly withdraw or cancel

Main store actions:

- review
- approve
- reject
- issue

Combining them under one update permission worked only because additional location and workspace guards happened later.

That means the permission system is currently broad, and the real workflow separation is being enforced indirectly.

#### Outcome

This has now been corrected.

The module now uses:

- `inventory_requisitions.view`
- `inventory_requisitions.create`
- `inventory_requisitions.submit`
- `inventory_requisitions.review`
- `inventory_requisitions.issue`

with requester and processor responsibilities split accordingly.

#### Suggested follow-on improvement

Optional later:

- `inventory_requisitions.cancel`

### 4. The Incoming Queue Logic Is Hard-Coded for Only Lab and Pharmacy

Implementation note:

- this rule has now been extracted into [InventoryRequisitionWorkflow.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/InventoryRequisitionWorkflow.php)
- the helper now centralizes requester location types, fulfilling location types, hidden queue statuses, and the definition of an incoming requisition

Originally, the main inventory queue filtered requisitions so they only counted as incoming queue items when their destination was:

- pharmacy
- laboratory

See:

- [InventoryRequisitionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/InventoryRequisitionController.php#L88)

That works for the current scope, but it means adding another requester location type later requires changing controller logic directly.

#### Why this matters

This kind of rule will spread if more location-based workflows are added.

It also means the queue definition is not really a business rule object yet. It is just a controller condition.

#### Suggested improvement

Move this into a clearer rule:

- a configuration list of requisition-requester location types
- or a small support class like `InventoryRequisitionWorkflow`

That would let the queue ask:

- “is this requisition a requester-to-main-store request?”

instead of:

- “is the destination pharmacy or laboratory?”

That will scale better as more store-like modules are introduced.

### 5. Statuses Are Ahead of Workflow Support

The enum includes:

- `draft`
- `submitted`
- `approved`
- `partially_issued`
- `fulfilled`
- `rejected`
- `cancelled`

See:

- [InventoryRequisitionStatus.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Enums/InventoryRequisitionStatus.php)

But the model transitions only really support:

- submit
- approve
- reject
- issue

See:

- [InventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InventoryRequisition.php#L64)

This has now been implemented through requester-side cancel and withdraw actions.

#### Why this matters

It creates false workflow expectations.

Users and developers see `cancelled` and assume:

- drafts can be abandoned cleanly
- submitted requests can be withdrawn
- main store can stop a request after approval if needed

But the workflow does not expose that.

#### Suggested improvement

If cancellation is needed, implement it properly.

Recommended minimal rules:

- `draft` -> `cancelled` by requester
- `submitted` -> `cancelled` by requester only before review

If not needed yet, remove `cancelled` until the flow exists.

### 6. The Shared Show Page Is Becoming Too Heavy

Originally, the requisition show page handled:

- requester-side tracking
- main-store queue guidance
- submit action
- approve form
- reject form
- issue form
- per-line allocation UI
- issue history rendering

in one page:

- [show.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/inventory/requisitions/show.tsx)

#### Why this matters

This page is doing too many jobs.

It is not just a detail page anymore. It is:

- a requester tracking page
- a main store review page
- a main store issue workstation

That means every small workflow change touches a large file with lots of branching behavior.

#### Suggested improvement

Split the UI concerns, even if they still share backend data:

- requester detail page
- main-store processing page

Possible approaches:

1. Keep one route but split into subcomponents
2. Keep one data contract but render different page shells by workspace
3. Give main store its own dedicated processing page

My recommendation:

- keep the same route structure for now
- split the page into dedicated subcomponents immediately
- if complexity continues, then split the processing page into its own component entirely

### 7. Approval and Issue Are Correct, but Operationally Dense

The issue action itself is solid:

- it locks the requisition
- validates remaining approved quantities
- validates source batch balances
- posts paired movements

See:

- [IssueInventoryRequisition.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/IssueInventoryRequisition.php)

This is good.

But the operational UI is dense because the main-store user has to do all of this in one page:

- review lines
- set approved quantities
- choose issue quantities
- choose source batches
- optionally leave notes

#### Suggested improvement

Keep the backend logic, but simplify the interaction model:

- Step 1: review and approve quantities
- Step 2: issue approved quantities

This already exists conceptually in statuses, but the page could enforce the separation more clearly.

For example:

- if `submitted`, only show review UI
- if `approved` or `partially_issued`, only show issue UI

That would reduce visual and mental load.

### 8. The Current Tests Cover Workflow, but Not Enough Design Boundaries

The existing test coverage is good for core behavior:

- create
- scope restrictions
- queue filtering
- approve
- reject
- issue

See:

- [InventoryRequisitionControllerTest.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/tests/Feature/Controllers/InventoryRequisitionControllerTest.php)

But there are still missing tests around intended design boundaries.

#### Missing tests worth adding

- inventory workspace cannot create requisitions once queue-only design is enforced
- requester can cancel draft requisition
- requester can or cannot withdraw submitted requisition, depending on final rule
- main store cannot approve a requisition with zero approved quantities after a future UI refactor
- requester workspaces cannot reach approve/reject/issue routes even by crafted POST requests
- future requester location types can enter queue without editing controller logic, once queue rules are extracted

## Suggested Simplification Plan

If we want to reduce complexity without destabilizing stock movement logic, this is the best order:

### Phase 1: Workflow Clarification

1. Make inventory requisitions queue-only
2. Remove or disable generic inventory creation
3. Keep create/submit only in requester workspaces

This gives the module one clear business shape.

### Phase 2: Permission Separation

1. Add distinct permissions:
   - create
   - submit
   - review
   - issue
   - optionally cancel
2. Update seeders and role assignments accordingly

This gives the module cleaner ownership boundaries.

### Phase 3: Language Cleanup

1. Expose clearer serialized names for requester vs fulfiller locations
2. Reduce the need for UI translation logic
3. Update copy so the module reads naturally everywhere

This gives the module a more understandable domain model.

### Phase 4: Status Completion

1. Either implement cancel/withdraw properly
2. Or remove `cancelled` until it is supported

This keeps statuses honest.

### Phase 5: UI Decomposition

1. Split the current show page into smaller requester/reviewer/issuer pieces
2. Make requester pages feel like request tracking
3. Make main-store pages feel like a processing workstation

This keeps the frontend maintainable as the workflow grows.

### Phase 6: Queue Rule Extraction

1. Extract requester location type rules out of the controller
2. Make the queue definition configurable or service-based

This prepares the module for additional requester locations later.

## Recommended Final Design Direction

The ideal design for this application now looks like this:

### Requester Workspaces

Laboratory and Pharmacy should:

- create requisitions
- save drafts
- submit requisitions
- view current status
- view approval and issue progress
- optionally cancel drafts or withdraw submitted requests before review

They should not:

- approve
- reject
- issue

### Main Store Workspace

Main Inventory should:

- show incoming requisitions as a queue
- review submitted requests
- approve quantities
- reject requests
- issue from available batches
- track partially issued and fulfilled requests

It should not:

- normally create requester-side requisitions

### Ledger Behavior

Keep the current stock posting behavior:

- `requisition_out` from main store
- `requisition_in` into requester location

That part already fits the operational model well.

## My Recommendation

If time allows changing things now, the best next refactor is:

1. Make main inventory queue-only for requisitions
2. Split requisition permissions by responsibility
3. Implement cancel/withdraw or remove `cancelled`
4. Rename the location semantics in code and serialization
5. Break up the shared show page into smaller workflow-specific pieces

That would keep the strong parts of the current design while removing most of the unnecessary mental load.

## Short Conclusion

The requisitions module is already useful and operationally close to real hospital workflow.

The main remaining problem is not capability. It is complexity concentration.

Right now the system is making one module do all of these at once:

- requester experience
- queue experience
- approval experience
- issuing experience
- stock movement posting

The best next step is not to add more requisition features first. It is to simplify the workflow boundaries so the existing feature set becomes easier to understand, use, and maintain.
