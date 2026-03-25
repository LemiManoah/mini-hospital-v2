# Lab Module Implementation Plan

**Date:** March 24, 2026  
**Goal:** Complete the laboratory module as an end-to-end workflow, starting from the existing consultation ordering foundation and extending through specimen collection, result entry, verification, release, and clinician visibility.

---

## 1) Current Read

The codebase already has a meaningful laboratory foundation in place:

- `LabTestCatalog` exists as the core test definition model
- `LabRequest` and `LabRequestItem` exist
- doctor consultation already supports lab ordering
- lab request charges are already synced into visit billing foundations
- request and item statuses already exist for part of the intended lifecycle

What is not yet fully implemented is the remaining downstream laboratory execution workflow:

- specimen collection workflow
- amendment/correction workflow after release
- printable released results
- broader permission and branch-isolation coverage
- full test coverage for the end-to-end lab lifecycle

What is now already implemented beyond the original draft:

- lab catalog administration surface
- lab lookup CRUD for categories, specimen types, and result types
- lab worklist and request-item workflow pages
- receive, result entry, review, approval, and release actions
- doctor-facing and visit-facing released-result visibility
- per-test consumables recording for actual execution cost

This means the right implementation strategy is to extend the current workflow, not redesign it from scratch.

---

## 2) Design Ideas To Borrow From The Production Module

The production module design has several strong ideas that are worth carrying into this project.

### Worth Borrowing Now

- separate the lab catalog surface from the lab operations worklist
- allow each lab test to define how results should be captured
- separate result entry from review, verification, and release
- give lab staff a dedicated worklist with queue counts and status filters
- make released results printable and easy for clinicians to review
- keep facility and branch scoping on lab records
- preserve audit-friendly actor and timestamp fields throughout the workflow

### Worth Borrowing, But In A Lighter Form

- use result templates, but start with a smaller set:
  - defined options
  - parameter panels
  - free text or numeric
- use standardized sample definitions, but start with a simpler shape before introducing heavy many-to-many modeling
- add reporting and dashboard metrics after the operational workflow is stable

### Worth Deferring For Later

- fertility-specific result models
- specialized culture and sensitivity models as a first implementation step
- consumables stock and requisition workflows inside the lab module before broader inventory foundations exist
- search infrastructure, cache layers, and advanced analytics before the module has real usage pressure
- printing as a status gate for clinical completion

### Important Adaptation For This Codebase

One important difference from the production system is that this project should not make "printed" the main clinical completion state. A result can be clinically complete once it has been verified and released. Printing should be modeled as an output event such as `printed_at`, not as the core workflow status.

---

## 3) What Counts As The Lab Module Being Complete

The lab module should be considered complete when all of the following are true:

- lab tests are manageable through the app as operational catalog records
- lab tests can define their result capture method and sample requirements
- clinicians can place lab requests reliably from consultation
- lab staff can receive and process requests through a dedicated worklist
- specimen collection is captured with audit-friendly timestamps and actors
- results can be entered in a structured way for simple, option-based, and parameter-based tests
- results can be reviewed, verified, and released separately from entry
- released results are visible to clinicians in the visit/consultation workspace
- released results can be printed cleanly without exposing unreleased work
- permissions and branch isolation are enforced across the workflow
- automated tests cover the critical happy paths and guard rails

---

## 4) Milestone Checklist

- [x] Milestone 1 completed: lab catalog administration is live
- [ ] Milestone 2 in progress: lab worklist and intake workflow are substantially live
- [ ] Milestone 3 completed: specimen collection workflow is live
- [ ] Milestone 4 in progress: structured result entry is substantially live
- [ ] Milestone 5 in progress: verification and release workflow is substantially live
- [ ] Milestone 6 in progress: clinician result visibility is substantially live
- [ ] Milestone 7 in progress: permissions, branch isolation, and tests are partially complete

---

## 5) Milestone Details

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

### Recommended Borrowed Ideas

- define a result capture type on the test:
  - defined_option
  - parameter_panel
  - free_entry
- keep specimen requirements on the catalog so downstream collection is guided by the test definition
- keep category support simple at first unless a dedicated category table becomes clearly necessary

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

### Recommended Borrowed Ideas

- model this as a real laboratory worklist, not just another admin list page
- support fast staff scanning with summary counts and clear queue buckets
- let lab staff see request context without needing to open the doctor workspace

### Milestone Checklist

- [x] Add lab worklist route
- [x] Add lab request detail route
- [x] Add worklist controller or module actions
- [x] Add index page with filters
- [x] Add request detail page
- [ ] Add counts/summary cards by status
- [x] Add status transition rules for intake
- [x] Add permission checks for lab worklist access
- [x] Add feature tests for worklist visibility and filtering

### Current Status

This milestone is substantially implemented. The remaining gap is a richer dashboard-style summary layer and broader filtering/count views, not the core intake workflow itself.

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
- specimen receipt/acceptance action
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

- [ ] Create `lab_specimens` migration
- [ ] Add `LabSpecimen` model
- [ ] Define specimen statuses
- [ ] Add specimen collection action
- [ ] Add specimen receive/accept action
- [ ] Add specimen rejection action
- [ ] Add specimen collection UI
- [ ] Add specimen timeline/status display on request detail
- [ ] Update `LabRequestStatus` transitions from real specimen actions
- [ ] Add tests for collection, receipt, and rejection

### Definition Of Done

- specimen collection is auditable
- request status reflects actual lab progress instead of assumptions

## Milestone 4: Structured Result Entry

### Objective

Add a result model that supports real laboratory reporting instead of only a flat "completed" state.

### Recommended Shape

- `lab_result_sets` per request item
- `lab_result_values` per analyte or reported parameter

This keeps the module flexible for:

- single-value tests
- multi-parameter panels
- text-only narrative results
- future abnormal flags and reference range handling

### Borrowed Result-Type Strategy

The production system's strongest idea is that not every test should be captured the same way. For this codebase, the best first implementation is:

- simple option results:
  - positive/negative
  - reactive/non-reactive
  - normal/abnormal
- parameter-panel results:
  - CBC-style multi-parameter tests
  - chemistry panels
- free-entry results:
  - narrative or custom measurements

Instead of immediately creating a separate model tree for fertility and culture workflows, build the result model so those can be added later without a redesign.

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
- [x] Create `lab_result_sets` migration
- [x] Create `lab_result_values` migration
- [x] Add result models
- [x] Add result entry action/service
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

### Recommended Status Strategy

The current request lifecycle already includes:

- requested
- sample_collected
- in_progress
- completed
- cancelled
- rejected

To support a stronger clinical workflow, the plan should introduce review and release semantics before a request is considered fully complete. That can be done either by:

- extending request or result statuses with review/release states, or
- keeping those states on result sets while request status remains a higher-level operational summary

Either way, review and release should be explicit.

### Milestone Checklist

- [x] Add verification status fields
- [x] Add verify-result action
- [x] Add release-result action
- [x] Add reviewer attribution fields
- [x] Add edit-lock behavior after verification
- [ ] Add amendment/correction workflow
- [x] Add UI controls for verify/release/amend
- [x] Add tests for verification, release, and amendment rules

### Current Status

Review and release are operational now. The remaining gap is a true amendment/correction flow for post-release changes rather than simple edit prevention.

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
- [ ] Add printable released result view
- [x] Add pending vs released state labels
- [ ] Add result detail view or drawer
- [ ] Add tests confirming unreleased results are hidden from clinicians where intended

### Current Status

Clinicians can already see released results in the visit and consultation workspaces. The remaining work is a dedicated printable output and a richer detail presentation layer.

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
  - cancellation/rejection
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

## 6) Recommended Build Order

1. build lab catalog administration
2. add the lab worklist and request intake surface
3. implement specimen collection and receipt
4. add structured result storage and result entry UI
5. add verification, release, and amendment rules
6. expose released results back to clinicians
7. complete permissions, branch isolation, and test coverage

---

## 7) Suggested Immediate Next Step

The first implementation step for the lab module should be:

### Build The Laboratory Dashboard

Why this is next:

- the core lab workflow now exists, but the module still needs a true operational landing page
- lab staff need queue pressure and release pressure visible before opening the raw worklist
- the dashboard completes the missing summary-card layer originally planned for Milestone 2

---

## 8) Advanced Ideas To Revisit Later

These are good ideas from the production module, but they should be treated as later extensions instead of first-pass blockers:

- specialized culture and sensitivity workflow:
  - organism selection
  - antibiotic sensitivity matrices
- fertility-specific result packs
- lab attachments and uploaded external reports
- consumables stock, requisitions, reconciliation, and restocking
- laboratory summary statistics and staff performance dashboards
- barcode scanning after accession generation is stable
- analyzer or machine result import

---

## 9) Boundaries For This Plan

This plan intentionally does not include:

- analyzer or LIS machine integration
- reagent inventory and QC workflows
- microbiology-specific culture workflows
- advanced pathology-style narrative reporting
- insurer claims logic beyond the current visit charge foundation

Those can be handled in later iterations after the core lab workflow is stable.

---

## 10) Bottom Line

The lab module is already started, but only at the ordering layer. The best implementation plan is to keep the existing consultation-driven request flow and build the rest of the module in order:

- catalog administration
- lab worklist
- specimen collection
- result entry
- verification and release
- clinician visibility
- permissions and tests
