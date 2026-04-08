# Lab Module Implementation Plan

**Date:** April 6, 2026  
**Goal:** Track the laboratory module against the actual codebase so the team can see what is already live, what is substantially implemented, and what still needs deliberate follow-up.

---

## 1) Current Document Status

This document is now a reality-based implementation tracker, not just a forward-looking plan.

The lab module is **not fully complete yet**, but it is already much further along than the original draft implied. The system is no longer only at the ordering layer. It already covers:

- lab catalog administration
- consultation-driven test ordering
- lab work queues
- specimen collection and receipt
- structured result entry
- review and approval/release
- clinician visibility of released results
- consumables tracking on executed tests

The biggest remaining gaps are:

- post-release amendment/correction workflow
- stronger branch-isolation test coverage
- a few richer clinician/result-detail presentation pieces

---

## 2) Current Read

The codebase already has a meaningful laboratory foundation in place:

- `LabTestCatalog` exists as the core test definition model
- `LabRequest` and `LabRequestItem` exist
- doctor consultation already supports lab ordering
- lab request charges are already synced into visit billing foundations
- request and item statuses already exist for the intended lifecycle

What is already implemented beyond the original draft:

- lab catalog administration surface
- lab lookup CRUD for categories, specimen types, and result types
- laboratory dashboard with queue metrics and recent requests
- dedicated laboratory queue pages for:
  - incoming investigations
  - result entry
  - result review
  - released result viewing
- lab worklist and request-item workflow pages
- specimen collection and receipt actions
- result entry, review, approval, and release actions
- doctor-facing and visit-facing released-result visibility
- per-test consumables recording for actual execution cost

What is not yet fully implemented:

- amendment/correction workflow after release
- richer branch-isolation coverage for lab-specific records
- broader end-to-end test coverage around the remaining gaps

This means the right implementation strategy is to extend and harden the current workflow, not redesign it from scratch.

---

## 3) Design Ideas To Borrow From The Production Module

The production module design still has several strong ideas worth carrying into this project.

### Worth Borrowing Now

- keep the lab catalog surface separate from the lab operations worklist
- allow each lab test to define how results should be captured
- separate result entry from review, verification, and release
- give lab staff dedicated queue pages with summary counts and status filters
- make released results printable and easy for clinicians to review
- keep facility and branch scoping on lab records
- preserve audit-friendly actor and timestamp fields throughout the workflow

### Worth Borrowing, But In A Lighter Form

- use result templates, but keep the starting result types simple:
  - defined options
  - parameter panels
  - free text or numeric
- use standardized specimen definitions, but avoid over-modeling too early
- add reporting and dashboard metrics after the operational workflow is stable

### Worth Deferring For Later

- fertility-specific result models
- specialized culture and sensitivity models as a first implementation step
- consumables stock and requisition workflows inside the lab module before broader inventory foundations exist
- search infrastructure, cache layers, and advanced analytics before the module has real usage pressure
- printing as a status gate for clinical completion

### Important Adaptation For This Codebase

One important difference from the production system is that this project should not make "printed" the main clinical completion state. A result can be clinically complete once it has been reviewed, approved, and released. Printing should be modeled as an output event such as `printed_at`, not as the core workflow status.

---

## 4) What Counts As The Lab Module Being Complete

The lab module should be considered complete when all of the following are true:

- lab tests are manageable through the app as operational catalog records
- lab tests can define their result capture method and specimen requirements
- clinicians can place lab requests reliably from consultation
- lab staff can receive and process requests through a dedicated worklist
- specimen collection is captured with audit-friendly timestamps and actors
- results can be entered in a structured way for simple, option-based, and parameter-based tests
- results can be reviewed, verified, and released separately from entry
- released results are visible to clinicians in the visit and consultation workspace
- released results can be printed cleanly without exposing unreleased work
- permissions and branch isolation are enforced across the workflow
- automated tests cover the critical happy paths and guard rails

---

## 5) Milestone Checklist

- [x] Milestone 1 completed: lab catalog administration is live
- [x] Milestone 2 completed: lab worklist, intake queues, and dashboard summary are live
- [x] Milestone 3 completed: specimen workflow now includes collection, receipt, rejection, and test coverage
- [ ] Milestone 4 in progress: structured result entry is substantially live
- [ ] Milestone 5 in progress: review and release are live, but amendment is still missing
- [ ] Milestone 6 in progress: clinician result visibility is substantially live
- [ ] Milestone 7 in progress: permissions are strong, but branch-isolation coverage is still incomplete

---

## 6) Milestone Details

## Milestone 1: Lab Catalog Administration

### Objective

Turn the lab test catalog into a first-class admin surface that operations can manage without seeders or database edits.

### Deliverables

- `LabTestCatalogController`
- request validation for create, update, and delete actions
- routes for lab test catalog CRUD
- Inertia pages for list, create, and edit flows
- result capture metadata on the test definition
- catalog fields such as:
  - test code
  - test name
  - lab service category
  - specimen type
  - container type
  - volume required
  - turnaround time
  - base price
  - fasting requirement
  - reference ranges
  - result capture type
  - active flag

### Milestone Checklist

- [x] Add `LabTestCatalogController`
- [x] Add store request validation
- [x] Add update request validation
- [x] Add delete request validation or safe deactivation logic
- [x] Add CRUD routes
- [x] Add index page
- [x] Add create page
- [x] Add edit page
- [x] Add search/filter support
- [x] Add result capture type to the lab test definition
- [x] Add permission enforcement
- [x] Add feature tests for catalog CRUD

### Current Status

This milestone is complete. The catalog and supporting lookup records are already manageable through the application.

### Definition Of Done

- lab tests can be created and maintained in the app
- consultation ordering reads from the same managed records
- pricing and specimen metadata are not hidden in seed data only

## Milestone 2: Lab Worklist And Intake

### Objective

Give lab staff a dedicated operational surface to receive, triage, and track incoming lab work.

### Deliverables

- lab requests index page for lab staff
- request detail page
- operational queue counts by status
- filters for:
  - status
  - priority
  - date
  - branch
  - requesting clinician
  - patient
- request actions for intake progression

### Milestone Checklist

- [x] Add lab worklist route
- [x] Add lab request detail route
- [x] Add worklist controller or module actions
- [x] Add index page with filters
- [x] Add request detail page
- [x] Add counts/summary cards by status
- [x] Add status transition rules for intake
- [x] Add permission checks for lab worklist access
- [x] Add feature tests for worklist visibility and filtering

### Current Status

This milestone is complete. The system already has:

- a laboratory dashboard with queue and release metrics
- dedicated queue pages for intake, result entry, review, and released-result viewing
- request-item workflow pages that lab staff can work from directly

### Definition Of Done

- lab staff can see pending and active work from one place
- request movement from ordered to active processing is operationally clear

## Milestone 3: Specimen Collection Workflow

### Objective

Capture specimen collection as a real workflow event instead of treating all tests as instantly in progress.

### Deliverables

- `lab_specimens` table and model
- accession or specimen number generation
- optional barcode-ready accession format
- specimen collection action
- specimen receipt or acceptance action
- rejection flow with reason capture
- request and request item status updates tied to specimen state

### Suggested First Fields

- request id
- request item id or grouped specimen linkage
- specimen type snapshot
- accession number
- collected by
- collected at
- received by
- received at
- status
- rejection reason
- notes

### Milestone Checklist

- [x] Create `lab_specimens` migration
- [x] Add `LabSpecimen` model
- [x] Define specimen statuses
- [x] Add specimen collection action
- [x] Add specimen receive or accept action
- [x] Add specimen rejection action
- [x] Add specimen collection UI
- [x] Add specimen timeline or status display on request detail
- [x] Update status transitions from real specimen actions
- [x] Add tests for collection, receipt, and rejection

### Current Status

This milestone is complete. `lab_specimens` is real, accession numbers are generated, collection and receipt drive workflow state, and rejected specimens now carry explicit reason and actor capture with test coverage.

### Definition Of Done

- specimen collection is auditable
- request status reflects actual lab progress instead of assumptions

## Milestone 4: Structured Result Entry

### Objective

Add a result model that supports real laboratory reporting instead of only a flat "completed" state.

### Recommended Shape

The current implementation uses:

- `lab_result_entries` per request item
- `lab_result_values` per analyte or reported parameter

This keeps the module flexible for:

- single-value tests
- multi-parameter panels
- text-only narrative results
- future abnormal flags and reference range handling

### Deliverables

- result tables and models
- result definition metadata tied to test configuration
- result entry action
- result entry page or section on request detail
- result value types such as:
  - numeric
  - text
  - boolean
  - option
- result comments and interpretation notes

### Milestone Checklist

- [x] Add result capture type support to result entry flow
- [x] Add result definition structures for simple options
- [x] Add result definition structures for parameter panels
- [x] Create `lab_result_entries` migration
- [x] Create `lab_result_values` migration
- [x] Add result models
- [x] Add result entry action or service
- [x] Add result entry UI
- [x] Add support for numeric result values
- [x] Add support for text result values
- [ ] Add abnormal flag handling
- [ ] Add reference range snapshot handling
- [x] Add tests for result creation and editing before verification

### Current Status

The structured result workflow is live using `lab_result_entries` and `lab_result_values`. The remaining work is clinical enrichment such as abnormal flags and stronger range snapshot behavior.

### Definition Of Done

- lab staff can enter structured results for a request item
- the result model can handle both simple and panel-based tests

## Milestone 5: Verification And Release

### Objective

Separate result entry from final release so the lab workflow is clinically safe and auditable.

### Deliverables

- result verification action
- result release action
- clear review workflow status handling
- reviewer identity and timestamps
- amendment flow for corrected results
- locked editing behavior after verification unless explicitly amended

### Milestone Checklist

- [x] Add verification status fields
- [x] Add verify-result action
- [x] Add release-result action
- [x] Add reviewer attribution fields
- [x] Add edit-lock behavior after verification
- [ ] Add amendment or correction workflow
- [x] Add UI controls for verify and release
- [x] Add tests for verification, release, and edit-lock rules

### Current Status

Review and release are operational now. Approval also makes results visible to clinicians. The remaining gap is a true amendment or correction flow for post-release changes rather than simple edit prevention.

### Definition Of Done

- a result can be entered by one user and verified by another where required
- released results are protected from silent overwrite

## Milestone 6: Clinician Result Visibility

### Objective

Return verified lab output back into the visit and consultation workflow where it becomes clinically useful.

### Deliverables

- lab results tab or section in visit workspace
- released-result visibility in doctor consultation workspace
- printable released lab report
- result summary cards on visit detail where appropriate
- clear distinction between pending, in-progress, and released requests
- optional attachment support for result artifacts if later needed

### Milestone Checklist

- [x] Add released lab result data to visit detail payload
- [x] Add released lab result data to consultation payload
- [x] Add clinician-facing lab result UI
- [x] Add printable released result view
- [x] Add pending vs released state labels
- [ ] Add dedicated result detail view or drawer
- [ ] Add tests confirming unreleased results are hidden from clinicians where intended

### Current Status

Clinicians can already see released results in the visit and consultation workspaces, and released lab results can now be exported as PDF. The remaining work is a richer result-detail presentation layer and explicit tests proving unreleased work stays hidden.

### Definition Of Done

- clinicians can see the results they need without going to a separate admin-only surface
- unreleased work is clearly distinct from finalized output

## Milestone 7: Permissions, Branch Isolation, And Testing

### Objective

Finish the module with strong workflow safety, not just functional screens.

### Deliverables

- permission matrix for:
  - ordering
  - receiving lab work
  - specimen collection
  - result entry
  - verification
  - release
  - amendment
  - cancellation or rejection
- branch isolation across lab worklist and data access
- feature and unit coverage for critical lab workflows

### Milestone Checklist

- [x] Add permission coverage for lab catalog administration
- [x] Add permission coverage for lab worklist access
- [x] Add permission coverage for result entry
- [x] Add permission coverage for verification and release
- [ ] Add branch isolation tests for lab requests
- [ ] Add branch isolation tests for specimen and result records
- [x] Add end-to-end feature test for request to release flow
- [x] Add documentation updates in implementation notes
- [x] Mark completed milestones in this file

### Current Status

Permission hooks and focused workflow tests exist, but the branch-isolation matrix still needs to be expanded before this milestone can be treated as done.

### Definition Of Done

- the module is safe to operate in a multi-user, multi-branch environment
- the most important clinical and security boundaries are automated

---

## 7) Recommended Build Order From Here

1. add amendment or correction flow for released results
2. add dedicated clinician result-detail presentation
3. expand branch-isolation tests for requests, specimens, and result records
4. add clinical enrichment such as abnormal flags and stronger reference range snapshots

---

## 8) Suggested Immediate Next Step

The next implementation step for the lab module should be:

### Build The Amendment And Result Hardening Layer

Why this is next:

- the core lab workflow is already operational
- the biggest remaining gaps are now at the safety and hardening layer, not the intake layer
- released results already have printable PDF output, so the next missing clinical safeguard is amendment/correction and stronger visibility hardening

If only one slice should be taken first, start with:

1. amendment or correction workflow
2. branch-isolation tests
3. richer clinician result-detail presentation

---

## 9) Advanced Ideas To Revisit Later

These are good ideas from the production module, but they should be treated as later extensions instead of first-pass blockers:

- specialized culture and sensitivity workflow:
  - organism selection
  - antibiotic sensitivity matrices
- fertility-specific result packs
- lab attachments and uploaded external reports
- consumables stock, requisitions, reconciliation, and restocking
- laboratory summary statistics and staff performance dashboards beyond the current operational dashboard
- barcode scanning after accession generation is stable
- analyzer or machine result import

---

## 10) Boundaries For This Plan

This plan intentionally does not include:

- analyzer or LIS machine integration
- reagent inventory and QC workflows
- microbiology-specific culture workflows
- advanced pathology-style narrative reporting
- insurer claims logic beyond the current visit charge foundation

Those can be handled in later iterations after the core lab workflow is stable.

---

## 11) Bottom Line

The lab module is no longer just "started" at the ordering layer. It already spans most of the operational lifecycle:

- catalog administration
- consultation ordering
- work queues and dashboard
- specimen collection and receipt
- result entry
- review and release
- clinician visibility
- consumables tracking

The remaining work is mainly finishing and hardening the workflow:

- amendment or correction after release
- richer clinician-facing result presentation
- broader branch-isolation coverage
