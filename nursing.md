# Nursing / Care Section Blueprint

## Purpose

The care section on the visit page should become the working area for nursing activity that happens after triage and before, during, and after clinician review.

It should answer:

- what nursing care has been planned for this visit
- what nursing care has already been done
- what is still due now
- who performed it and when
- whether any care task should raise a billable service

## What the system already has

- triage capture
- vital signs history
- nurse notes at triage
- visit-level clinical context
- facility service ordering for some nursing-related billable services

These are useful foundations, but they are not yet a real nursing workspace.

## What the care section should include

### 1. Nursing summary

- current nurse in charge
- latest vital signs
- risk flags
- pending care tasks
- completed care tasks today
- escalation notes

### 2. Care plans

- nursing diagnosis or care focus
- goals
- planned interventions
- review frequency
- start date and expected stop date
- active, paused, completed, cancelled status

### 3. Nursing interventions

- medication administration log
- IV fluids and infusion monitoring
- wound care and dressing log
- injections and nebulization
- catheter care
- intake and output
- turning schedule
- pain reassessment
- observation rounds

Each intervention should capture:

- visit
- care plan if linked
- intervention type
- performed by
- performed at
- notes
- outcome
- whether it is billable
- linked billable service or charge source when relevant

### 4. Observation charting

- repeat vitals
- NEWS/PEWS trend
- pain trend
- fluid balance
- nursing alerts
- deterioration escalation trail

### 5. Medication administration record

- prescribed item
- scheduled time
- administered time
- dose given
- route
- administered by
- held / omitted reason
- adverse reaction notes

### 6. Handover and shift notes

- shift summary
- unresolved tasks
- escalation items
- next actions
- author and timestamp

## Billing relationship

The care section should not be the cashier screen, but it should be able to trigger billable nursing services when appropriate.

Examples:

- dressing
- nebulization
- IV line insertion
- injections
- wound care procedures

Recommended rule:

- nursing documentation records the care event
- when the intervention is marked billable, the system creates or syncs a linked visit charge
- finance collects payment from the Finance & Accounting module, not from the visit page

## Suggested domain model

### `nursing_care_plans`

- `patient_visit_id`
- `primary_focus`
- `goals`
- `status`
- `started_at`
- `ended_at`
- `created_by`
- `updated_by`

### `nursing_interventions`

- `patient_visit_id`
- `nursing_care_plan_id`
- `intervention_type`
- `description`
- `performed_by`
- `performed_at`
- `outcome`
- `notes`
- `is_billable`
- `facility_service_id`

### `nursing_observations`

- `patient_visit_id`
- `recorded_by`
- `recorded_at`
- `pain_score`
- `news_score`
- `pews_score`
- `intake_notes`
- `output_notes`
- `escalation_notes`

### `medication_administration_records`

- `patient_visit_id`
- `prescription_item_id`
- `scheduled_for`
- `administered_at`
- `administered_by`
- `dose_given`
- `route`
- `status`
- `omission_reason`
- `notes`

### `nursing_handover_notes`

- `patient_visit_id`
- `shift_name`
- `summary`
- `pending_tasks`
- `escalations`
- `authored_by`
- `authored_at`

## Visit page layout recommendation

Add a dedicated `Care` tab on the visit page with:

1. Summary strip
2. Active care plans
3. Interventions timeline
4. Observation chart
5. Medication administration
6. Handover notes

## User stories

As a nurse, I need to record bedside care quickly without leaving the visit context.

As a nurse, I need repeated observations and interventions to stay visible as a timeline so handover is safer.

As a clinician, I need to see what nursing care has already been done before changing the treatment plan.

As finance staff, I need billable nursing procedures to become charges without asking nurses to re-enter them elsewhere.

## Delivery phases

### Phase 1

- care tab shell
- nursing summary
- intervention log
- billable intervention to visit-charge sync

### Phase 2

- care plans
- observation charting
- handover notes

### Phase 3

- medication administration record
- alerts and escalations
- fuller inpatient nursing workflow
