# Consultation Module Review

## Current Assessment

The consultation module is functional and close to a coherent end-to-end doctor workflow, but it is not fully mature yet.

What is working well now:

- Patients reach the doctors queue only after triage exists.
- The consultation workspace can be opened from the doctors queue, triage page, and visit page.
- Starting a consultation creates the consultation record and moves the visit from `registered` to `in_progress`.
- The consultation screen now shows the full latest vital-sign snapshot instead of only a minimal subset.
- Follow-up fields now appear only when the selected outcome is `follow_up_required`.
- Referral fields now appear only when the selected outcome is `referred`.
- Referred-to department and referred-to facility are now dropdown-driven instead of free-text-first UX.
- Referral facilities now have CRUD support and can be maintained from administration master data.
- Consultation entry buttons are already guarded by consultation permissions in the main visit and triage surfaces, and the route itself is also permission-protected.

## How Patients Reach The Doctors Queue

The doctors queue is driven by `DoctorConsultationController@index`.

Today, a patient becomes visible there when all of the following are true:

- the visit belongs to the active branch
- the visit is not `completed`
- the visit is not `cancelled`
- the visit has a triage record

For non-privileged users, the visit must also be one of these:

- assigned to the logged-in doctor
- not assigned to any doctor yet
- already linked to a consultation owned by that doctor

This means the practical flow is:

1. registration creates the visit
2. triage records the clinical intake
3. the visit appears in the doctors queue
4. the doctor opens the consultation workspace
5. clicking `Start Consultation` creates the consultation draft

## Does “Start Consultation” Make Sense To Move The Visit To In Progress?

Yes, it makes sense with the way this module is currently modeled.

Why:

- triage means the patient is clinically ready for doctor review, but the doctor may not have started work yet
- creating the consultation is the first doctor-owned clinical action
- the code already uses `CreateConsultation` to transition the visit from `registered` to `in_progress`

So the current interpretation is:

- `registered`: patient is in the visit workflow but the doctor has not started consultation work
- `in_progress`: the doctor has started the consultation workspace

That is a reasonable design. It becomes especially coherent because the consultation model does not currently have a separate status enum such as `draft`, `in_consultation`, `completed`, `cancelled`. Instead, the visit status and the consultation timestamps are doing that job.

## What Was Improved In This Pass

- Full latest-vitals display was expanded in the consultation workspace and visit clinical snapshot.
- Outcome-driven conditional UI was added for follow-up and referral sections.
- Referral destination selection now uses:
  - department dropdown
  - facility dropdown
- Referral facility CRUD is now part of the product structure:
  - model
  - migration
  - factory
  - actions
  - requests
  - controller
  - Inertia pages
  - administration entry point
  - permissions
- Consultation access points on visit/triage surfaces remain permission-guarded.

## What Is Complete

- doctors consultation queue
- consultation workspace
- draft save and finalize flow
- consultation-linked ordering surface for:
  - labs
  - prescriptions
  - imaging
  - facility services
- vitals visibility in consultation context
- referral destination maintenance via master data

## What Is Partial

- referral persistence still stores names/strings on the consultation record instead of foreign keys
- consultation progression is inferred from timestamps and visit state rather than a dedicated consultation status machine
- referral workflow stops at documentation; there is no downstream referral acceptance/tracking workflow yet
- follow-up workflow is documented in the consultation, but there is no dedicated follow-up scheduling workflow attached to it yet
- queue semantics are workable, but they still rely on shared understanding of visit status rather than explicit queue stage terminology

## What Is Not Yet Achieved

- a full closed-loop referral workflow
  - no receiving-facility acknowledgement
  - no referral completion status
  - no referral feedback loop
- a dedicated consultation lifecycle model
  - for example: `not_started`, `draft`, `in_consultation`, `finalized`, `reopened`
- structured referral destinations on the consultation table
  - today the consultation stores destination text, not `department_id` / `referral_facility_id`
- consultation reopen/amend flow after finalization
- reporting views specific to consultation throughput, pending drafts, follow-up outcomes, and referral trends

## Design Risks And Review Notes

1. The queue logic is reasonable, but it depends heavily on triage existence plus visit status plus assignment rules. That is workable, but it should be documented as a product rule, not just a controller behavior.
2. Referral destination values are now selected from dropdowns, which is good for UX consistency, but the consultation still persists them as strings. That is acceptable for now, but it is not the strongest long-term data model.
3. The consultation module is acting as both the doctor note workspace and the orchestration point for downstream orders. That is powerful, but it means permission boundaries and completion rules need to stay very clear.
4. The workflow currently assumes that “consultation started” is the right trigger for visit `in_progress`. That is sensible, but it should be confirmed as a product decision so it does not get changed casually later.

## Recommended Next Improvements

1. Decide whether referrals should remain string-based or move to foreign-key-backed destination references.
2. Introduce an explicit consultation status model if the team wants clearer draft/active/finalized semantics.
3. Add consultation-focused reporting and dashboards.
4. Add dedicated follow-up scheduling if follow-up outcomes are meant to drive future appointments or return visits.
5. Add tests around:
   - conditional consultation outcome UI
   - referral facility administration permissions
   - queue visibility rules for assigned vs unassigned visits

## Questions That Need Product Answers

### Queue And Status

- Should every triaged patient automatically appear in the doctors queue, or only those assigned to a clinic/doctor?
- Should `Start Consultation` always move the visit to `in_progress`, or only after the first actual note is saved?
- Should a completed consultation automatically move the visit toward another visit status, or is that decided elsewhere?
- Can a finalized consultation be reopened?
- Should a visit be allowed to have more than one consultation episode?

### Referral Workflow

- When a consultation outcome is `referred`, must the user pick exactly one destination, or can both department and facility be recorded together?
- Is a department referral meant to be internal only, while a facility referral is external only?
- Should referral facilities be branch-specific, tenant-wide, or both?
- Do referrals need categories such as `higher center`, `specialist`, `diagnostics`, `admission`, or `emergency transfer`?
- Should referral destinations store contact details only, or also service capability and location metadata?

### Follow-Up Workflow

- Should `follow_up_required` create an appointment suggestion automatically?
- Should follow-up days be mandatory only on finalization, or even while saving draft?
- Do follow-up instructions need to be visible in the visit summary and patient-facing discharge outputs?

### Clinical Documentation

- Should all triage vitals remain read-only inside consultation, or should doctors be able to add a repeat vital set from the consultation page?
- Do doctors need structured diagnosis lists beyond the current primary diagnosis and ICD-10 code?
- Should completion require more fields than it currently does?

### Permissions And Ownership

- Should users with `consultations.view` be able to open every consultation workspace, or only the ones they own or are assigned to?
- Should there be a separate permission for finalizing consultations?
- Should pharmacists, nurses, or support users be allowed to view consultation summaries without accessing the editing workspace?

## Bottom Line

The consultation module is operational and now more coherent than before, especially around vitals, referrals, and outcome-specific UI. The biggest remaining work is not basic CRUD anymore. It is product clarity: defining the final rules for queue ownership, referral modeling, follow-up handling, and consultation lifecycle semantics.
