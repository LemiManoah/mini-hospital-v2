# Printing Plan

**Date:** April 7, 2026  
**Goal:** Explain why printing matters in this system, document the current state, and define the right implementation path for printing across the modules that need it.

---

## 1) Current Read

Printing is now **partially implemented**, in six focused places.

What exists now:

- released laboratory results can be exported as PDF
- the lab-result PDF is generated through Dompdf
- the print route is guarded so unreleased results cannot be exported
- visit payment receipts can be exported as PDF
- the payment-receipt PDF is generated through Dompdf
- the print route is guarded so refunds cannot be exported as payment receipts
- inventory requisitions can be exported as PDF
- the requisition PDF is generated through Dompdf
- goods receipts can be exported as PDF
- the goods-receipt PDF is generated through Dompdf
- visit summaries can be exported as PDF
- the visit-summary PDF is generated through Dompdf
- prescriptions can be exported as PDF
- the prescription PDF is generated through Dompdf
- the output uses a dedicated print controller and Blade view instead of trying to print a normal app page
- the current printable documents now share a common Blade print layout and shared header/footer partials

What still does **not** exist yet:

- broad printable output across every operational module

The only older nearby signal outside this new lab print flow is that [InsuranceCompanyInvoice.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/InsuranceCompanyInvoice.php) has an `is_printed` field, but that is still just a data flag and not a complete print workflow.

So the current state is:

- the app can create and process many operational records
- users can view those records on screen
- released laboratory results can now be exported as PDF
- visit payment receipts can now be exported as PDF
- inventory requisitions can now be exported as PDF
- goods receipts can now be exported as PDF
- visit summaries can now be exported as PDF
- and the current printable documents now share the same print document shell

---

## 2) Why Printing Is Very Important

Even when a hospital is becoming more digital, printing is still operationally important.

### Clinical reasons

- lab results are often physically attached to charts or handed to patients
- prescriptions may need a printable version for pharmacy handling or external purchase
- patient summaries are sometimes printed during referrals or handovers
- some clinicians still prefer a paper copy for quick ward review

### Operational reasons

- receipts may need to be handed to patients immediately
- requisitions and stock documents are often carried physically between departments
- some departments work with shared printers more reliably than shared screens
- printer output is often the easiest "official document" format for a patient or department to carry

### Compliance reasons

- some workflows need proof that a document was printed or reprinted
- print actions should not expose draft or unreleased clinical work
- released documents need a stable, presentation-safe output format

In short:

- **viewing** a record is not the same as **issuing** a document
- hospitals still need both

---

## 3) How The System Handles Walk-In Lab Patients Today

For a patient who comes to the hospital **just for a lab test**, the system can already handle that, but not through a dedicated "lab-only registration" flow.

### What happens today

1. The patient is registered normally.
2. A visit is started for that patient.
3. A lab request can be created directly against the visit.
4. The lab then processes the request through the normal lab queues.

### Why this works

The key point is that lab ordering is not limited to the doctor consultation path.

[CreateLabRequest.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Actions/CreateLabRequest.php) accepts either:

- a `Consultation`
- or a `PatientVisit`

That means the system supports two ordering modes:

- **consultation-driven lab order**
- **visit-driven lab order**

[VisitOrderController.php](c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/VisitOrderController.php) shows that a lab request can be created from the visit workspace without requiring a completed consultation note. It uses `DoctorConsultationAccess` with `requireTriage: false`, which means the order can be placed from the visit context more directly.

### What this means in practice

For a walk-in lab patient, the likely current workflow is:

- register patient
- start visit
- choose a visit type such as `outpatient` or `opd_consultation`
- place the lab request from the visit
- send it into the laboratory workflow

### What is still missing

There is still no dedicated UI flow that says something like:

- `Register Lab Walk-In`
- `Start Lab Visit`
- `Order Lab Test`

So the system **can** support walk-in lab patients today, but the user experience is still generic visit handling, not a dedicated lab-reception flow.

---

## 4) Where Printing Is Needed First

Printing should not be added everywhere at once. It should start with the documents that matter most operationally.

### Priority 1: Released laboratory results

This is the highest-value print workflow because:

- lab results are one of the most commonly printed documents in a hospital
- the lab module already has review and release logic
- released results are already clinician-visible
- the lab plan already identified printable released results as a missing piece

This is now the **first implemented print document** in the system.

Printing is allowed only for **released** results, never draft or unreleased results.

### Priority 2: Visit payment receipts

Patients often expect a physical proof of payment.

This should produce:

- patient name
- visit number
- receipt number
- date/time
- payer/payment method
- items or summary
- total paid

This is now the **second implemented print document** in the system.

### Priority 3: Requisitions and goods receipts

These are useful for store-to-department handoff and filing, especially while some departments still move with paper support.

Requisition printing and goods receipt printing are now implemented.

### Priority 4: Consultation or visit summaries

These are valuable later for:

- referrals
- discharge-style summaries
- patient handover

Visit summary printing is now implemented as the current practical summary document for the visit workspace.

### Prescription note

Prescription printing should stay on the roadmap as a **later-phase item**. It still matters operationally and should be implemented once the prescription and dispensing workflow is stable enough to support formal printed output.

---

## 5) Recommended Printing Architecture

The system should use a **dedicated print layer**, not ad-hoc browser printing from normal app pages.

### Core idea

Every printable document should have:

- a dedicated route
- a dedicated controller method
- a dedicated print-focused view

That is better than trying to print normal app pages because normal pages contain:

- sidebars
- action buttons
- filters
- tabs
- interactive controls

Those are not safe as official printable output.

### Current implementation direction

The first live print slice already follows this idea:

- route
- dedicated print controller
- dedicated Blade view
- Dompdf rendering
- release-only guard

That should remain the pattern for the rest of the system.

The app now also has a shared document shell for printable Blade views, so new print documents should extend the common layout instead of redefining base print styles, headers, and footers from scratch.

---

## 6) Recommended File And Route Structure

### A shared print area

Recommended shared pieces:

- `app/Http/Controllers/Print/`
- `resources/views/print/`
- `resources/views/print/layouts/document.blade.php`
- `resources/views/print/partials/header.blade.php`
- `resources/views/print/partials/footer.blade.php`

This shared print layout infrastructure is now implemented for the current live printable documents.

### Module-specific routes

Recommended route pattern:

- `/laboratory/request-items/{labRequestItem}/print`
- `/visits/{visit}/payments/{payment}/print`
- `/prescriptions/{prescription}/print`
- `/inventory-requisitions/{requisition}/print`
- `/goods-receipts/{goods_receipt}/print`
- `/visits/{visit}/summary/print`

### Recommended controllers

- `LabResultPrintController`
- `VisitPaymentPrintController`
- `PrescriptionPrintController`
- `InventoryRequisitionPrintController`
- `GoodsReceiptPrintController`
- `VisitSummaryPrintController`

These should live under:

- `app/Http/Controllers/Print/`

That keeps the regular workflow controllers smaller and makes the print layer easy to reason about.

---

## 7) Print Rules The System Should Enforce

Printing should not be a blind "open printer" action. It should respect workflow rules.

### Laboratory results

- only printable after release
- include approving or releasing details
- clearly show patient, visit, test, result values, notes, and issue timestamp
- never expose unreleased result output through the print route

### Receipts

- only printable for real posted payments
- should include receipt number and branch or facility identity

### Requisitions and goods receipts

- show current status clearly
- distinguish draft from submitted from posted
- optionally watermark drafts if drafts ever become printable

There is no need to add print-audit fields such as `printed_at`, `printed_by`, or `print_count` at this stage. The focus should stay on generating correct printable documents first.

---

## 8) Frontend And Output Strategy

The system now uses **PDF generation through Dompdf** for the first printing workflow.

That is a good fit for hospital documents because:

- the output is stable
- the document is easier to share or archive
- the format is more controlled than printing a live app screen

### Recommended ongoing strategy

- use dedicated Blade views for PDF output
- keep document layout minimal and formal
- use A4-friendly spacing and typography
- include branch or facility header
- include patient and document metadata blocks
- include a simple generation footer where useful

This approach is better for formal clinical output than trying to rely on browser printing of the Inertia pages.

---

## 9) Recommended Backend Pattern

Each print controller should:

1. load the document
2. enforce normal permission checks
3. enforce branch isolation
4. enforce workflow-specific print rules
5. build a dedicated print view
6. return PDF output

For example, lab result printing already follows the most important rules:

- authorize the parent lab request through branch-aware access
- confirm the result is released
- load finalized result data only
- stream a PDF back to the user

This keeps printing safe in multi-user, multi-branch use.

---

## 10) Testing Plan

Printing needs focused feature coverage.

### Feature tests

- released lab results can be exported as PDF
- unreleased lab results cannot be printed
- wrong-branch users cannot open print views
- payments can be printed only when valid
- draft requisitions or receipts follow the expected print rule

### Current implemented coverage

The first six print slices should now be covered by tests for:

- successful PDF export of a released lab result
- rejection of unreleased lab-result printing
- successful PDF export of a visit payment receipt
- rejection of refund-receipt printing
- successful PDF export of an inventory requisition
- rejection of requester users from the wrong requisition workspace
- successful PDF export of a goods receipt
- rejection of goods-receipt printing from the wrong workspace route
- successful PDF export of a visit summary
- rejection of visit-summary printing from another active branch
- successful PDF export of a prescription
- rejection of prescription printing from another active branch

---

## 11) Recommended Build Order

### Phase 1

- add lab result print route, controller, and PDF view
- add release-only print guard
- add feature tests

### Phase 1 Status

This phase is now implemented for laboratory results.

### Phase 2

- add visit payment receipt printing

### Phase 2 Status

This phase is now implemented for visit payment receipts.

### Phase 3

- add requisition and goods receipt printing
- add visit summary printing

### Phase 3 Status

This phase is now fully implemented:

- requisition printing is implemented
- goods receipt printing is implemented
- visit summary printing is implemented

### Phase 4

- add better branch or facility branding support
- add prescription printing when that module is ready for formal output
- add PDF export coverage to more modules when those modules are ready

---

## 12) Best Immediate Next Step

Now that released laboratory result, payment receipt, requisition, goods receipt, visit summary, and prescription PDF output exist, the next best print implementation is:

### Shared print styling and branding refinements

After that:

1. shared print styling refinements
2. branding polish

---

## 13) Bottom Line

Printing is operationally important in this system, and the first real print documents now exist:

- released laboratory result PDF output
- visit payment receipt PDF output
- inventory requisition PDF output
- goods receipt PDF output
- visit summary PDF output
- prescription PDF output

Today the app supports:

- on-screen operational workflows
- on-screen clinical review
- on-screen inventory and billing workflows
- PDF export for released lab results
- PDF export for visit payment receipts
- PDF export for inventory requisitions
- PDF export for goods receipts
- PDF export for visit summaries
- PDF export for prescriptions

But it still does **not** yet fully support:

- clean printable documents across all modules
- broad print permissions and workflow guards

The best ongoing implementation path is:

1. keep the dedicated print-controller + PDF-view pattern
2. keep new printable documents on the shared layout infrastructure
3. then refine shared print styling and branding
