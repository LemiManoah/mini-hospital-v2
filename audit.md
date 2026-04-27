# Auditing Guide

## Purpose

Auditing is the practice of recording, reviewing, and explaining important activity in the system. In Mini-Hospital v2, auditing matters because the application handles clinical records, patient visits, billing, pharmacy stock, laboratory results, facility setup, user access, and multi-branch operations. A good audit trail helps users answer practical questions such as:

- Who created, changed, approved, cancelled, rejected, or released a record?
- When did the action happen?
- Which patient, visit, branch, tenant, inventory item, lab request, prescription, payment, or facility setting was affected?
- Was the action expected, authorized, and consistent with the workflow?
- What should support or management do next?

Auditing protects patients, staff, facility owners, support teams, and developers. It is not only a compliance feature. It is also a daily operational tool for resolving mistakes, investigating disputes, confirming accountability, and improving system reliability.

## Why Auditing Is Important

### Accountability

Hospital work involves many handoffs. Reception registers patients, nurses capture triage data, clinicians create orders, laboratory staff enter results, pharmacy staff dispense medicine, cashiers record payments, stores staff issue stock, and facility managers configure access. Auditing connects each action to an actor so responsibility is clear.

For example, when a lab result is corrected, the system should show who corrected it, when it was corrected, and why the correction was made. When inventory is issued, the system should show who requested it, who approved it, who issued it, and which stock batches were affected.

### Patient Safety

Clinical mistakes can happen when records are changed without context. Audit information helps staff understand the history behind a patient record instead of only seeing the current value.

Useful patient-safety audit events include:

- Patient demographics created or updated.
- Visit started, checked in, completed, or cancelled.
- Vital signs recorded.
- Consultation notes completed.
- Lab, imaging, prescription, and service orders created.
- Lab results entered, reviewed, approved, released, or corrected.
- Prescriptions dispensed, voided, refunded, or partially fulfilled.
- Allergies added or changed.

### Financial Integrity

Billing and payment records need special care because they affect revenue, refunds, insurance claims, and patient balances. Auditing makes it possible to compare the financial workflow against the clinical workflow.

Important financial audit points include:

- Visit charges generated or updated.
- Payments recorded.
- Pharmacy POS sales finalized, voided, or refunded.
- Insurance package prices updated.
- Currency exchange rates created or changed.
- Subscription activation or billing follow-up for tenants.

### Inventory Control

Inventory is one of the easiest places for silent losses to occur. Auditing helps management trace stock from purchase to receipt, storage, issue, usage, adjustment, and sale.

The system already has workflow concepts for purchase orders, goods receipts, requisitions, reconciliations, stock movements, pharmacy POS allocations, dispensing allocations, and lab consumable usage. These should be reviewed as part of routine stock audits.

### Support and Operational Readiness

The facility manager module includes configuration and readiness audit checks. These checks help support teams identify whether a facility is ready to operate. Current checks cover areas such as onboarding, subscription state, primary branch, active branches, departments, user access, active staff, service catalog, laboratory catalog, inventory locations, and recent operational activity.

This is useful before go-live, after onboarding, during support calls, and when a facility appears inactive or blocked.

### Developer Debugging

For developers, auditing reduces guesswork. When a bug report says "the wrong result is showing" or "stock disappeared", audit data narrows the investigation. Developers can inspect the sequence of actions, actor IDs, timestamps, tenant IDs, branch IDs, and workflow states instead of trying to reconstruct history from the final database state.

## Current Audit Surfaces In This System

The application already contains several audit-friendly patterns.

### Actor Columns

Many records include fields such as:

- `created_by`
- `updated_by`
- `approved_by`
- `rejected_by`
- `cancelled_by`
- `issued_by`
- `received_by`
- `entered_by`
- `reviewed_by`
- `released_by`
- `corrected_by`

These columns should be treated as core audit metadata. They tell users and developers who performed a business action.

### Timestamp Columns

Laravel timestamps such as `created_at` and `updated_at` record basic lifecycle timing. The system also uses workflow-specific timestamps such as:

- `approved_at`
- `rejected_at`
- `cancelled_at`
- `issued_at`
- `received_at`
- `entered_at`
- `reviewed_at`
- `released_at`
- `corrected_at`
- `registered_at`
- `ordered_at`
- `request_date`

These are important because business workflow time is not always the same as database update time.

### Workflow Reasons and Notes

Some workflows require explanations, not just timestamps. Examples include lab correction reasons, rejection notes, cancellation reasons, support notes, review notes, approval notes, and result notes.

For users, these reasons explain why something happened. For developers, they confirm whether the workflow forced the user to provide the context expected by the business rule.

### Facility Manager Audit

The facility manager audit screen is a readiness audit. It reviews the facility configuration and produces pass, warning, or critical results. It is especially useful for:

- Support teams onboarding a facility.
- Facility managers checking setup completeness.
- Developers validating tenant setup rules.
- Administrators identifying facilities that need follow-up.

This audit does not replace a detailed activity log. It answers "is this facility ready and healthy?" rather than "who changed this exact record?"

### Facility Manager Activity

The facility manager activity screen summarizes recent operational events across visits, consultations, laboratory, pharmacy, and service orders. It helps users see whether a facility is actively using the system and which areas have recent activity.

This is useful for operational monitoring, but it should be treated as a summary view. A deeper audit trail should still exist for record-level investigation.

### Action Classes

Business logic in this system lives heavily in `app/Actions`. This is a strong foundation for auditing because actions are central points where important changes happen. When an action creates, updates, approves, rejects, issues, posts, voids, refunds, or completes a workflow, it should also set the right audit fields.

## How Users Can Audit The System

### Facility Managers

Facility managers should use the facility manager audit page to review readiness:

1. Open the facility manager area.
2. Select the facility.
3. Open the Audit tab.
4. Review the overall health status.
5. Fix critical items before allowing full operations.
6. Fix warning items before or shortly after go-live.
7. Use support notes to record follow-up decisions.

The most important checks are primary branch, active branches, user access, active staff, subscription state, and onboarding completion. Without these, users may be unable to work properly or records may be created without the correct tenant and branch context.

### Administrators

Administrators should audit users, roles, permissions, staff assignments, and branch access. A user should only have the permissions needed for their role. Staff should be active only when they are currently working in the facility. Branch assignment should match the location where the staff member is allowed to operate.

Recommended checks:

- Confirm every active user has a linked staff record where required.
- Confirm sensitive roles are limited to trusted users.
- Confirm users are assigned to the correct tenant and branch.
- Confirm inactive staff cannot perform operational actions.
- Review new users and recently updated users.

### Clinicians

Clinicians should audit patient-facing clinical history before making decisions. They should pay attention to:

- Who registered the patient.
- Current visit status.
- Triage and vital sign timestamps.
- Consultation notes and completion status.
- Lab result approval and release status.
- Corrections made to released results.
- Allergies and other safety warnings.
- Prescriptions and dispensing status.

When a record looks wrong, clinicians should report the record, patient, visit, timestamp, and suspected issue rather than editing blindly.

### Laboratory Staff

Laboratory auditing should follow the result workflow:

1. Confirm the lab request and request item.
2. Confirm specimen collection or receipt details.
3. Confirm who entered the result and when.
4. Confirm review and approval details.
5. Confirm release status before clinicians use the result.
6. For corrections, confirm the correction reason and correction actor.

Released results should not be changed silently. Corrections should create visible audit context and should require review and release again.

### Pharmacy and Stores Staff

Pharmacy and inventory users should audit stock by comparing workflow records:

- Purchase orders.
- Goods receipts.
- Inventory locations.
- Inventory batches.
- Stock movements.
- Requisitions.
- Reconciliation records.
- Prescription dispensing allocations.
- POS sale allocations.
- Refunds and voids.

Good auditing should answer which stock moved, from where, to where, under whose authorization, and for which patient, visit, sale, or requisition.

### Cashiers and Billing Staff

Billing users should audit financial records by checking:

- Visit charges against ordered services.
- Payments against visit balances.
- Refunds and voided sales.
- Insurance package pricing.
- Currency and exchange-rate configuration.
- Payment receipt printouts.

Any manual adjustment should have a clear reason and actor.

## How Developers Should Implement Auditing

### Prefer Business Actions As Audit Points

Most audit-sensitive logic should stay in action classes under `app/Actions`. When creating or changing workflows, developers should set audit fields inside the action that performs the change.

Examples:

- Creation actions set `created_by` and often `updated_by`.
- Update actions set `updated_by`.
- Approval actions set `approved_by` and `approved_at`.
- Rejection actions set `rejected_by`, `rejected_at`, and a reason.
- Cancellation actions set `cancelled_by`, `cancelled_at`, and a reason.
- Issue or receive actions set `issued_by`, `issued_at`, `received_by`, and `received_at`.
- Correction actions set `corrected_by`, `corrected_at`, and `correction_reason`.

Keeping audit assignment in actions makes the system easier to test and keeps controllers thinner.

### Keep Tenant and Branch Context

Because this is a multi-tenant and branch-aware system, audit records should always be scoped correctly.

For tenant-owned records, store or preserve:

- `tenant_id`
- `branch_id` where the workflow is branch-specific
- actor user ID
- linked staff ID where staff identity matters clinically or operationally

This prevents one facility's audit trail from leaking into another facility and helps support teams investigate branch-specific issues.

### Use Staff IDs When The Human Role Matters

Some workflows should identify the staff member rather than only the user account. Clinical and operational actions often need staff identity because the staff record carries position, department, branch, and employment context.

Use staff-level actor fields for workflows such as:

- Lab result entry, review, approval, release, and correction.
- Specimen collection or receipt.
- Appointment cancellation when the business actor is a staff member.
- Clinical orders where the ordering clinician matters.

Use user-level actor fields for account, administration, billing, support, and configuration changes where login identity is the main concern.

### Store Reasons For Reversals and Corrections

Any destructive, corrective, or reversing action should require a reason:

- Lab result corrections.
- Inventory requisition rejection or cancellation.
- Purchase order cancellation.
- Pharmacy sale voids.
- Refunds.
- Payment reversals.
- Appointment cancellations.
- Visit cancellations or administrative changes.

Reasons should be plain text, required by validation, and displayed in audit or detail views where users need context.

### Avoid Silent Updates In Controllers

Controllers should validate input, authorize access, and call actions. They should not quietly perform workflow updates without setting audit fields. If a controller must update a model directly, it should still set the correct actor and timestamp fields.

### Use Database Transactions For Multi-Model Audits

When an action changes multiple models, wrap it in a transaction. This matters for audit integrity.

Examples:

- Posting a goods receipt should update receipt status, inventory batches, location quantities, and stock movements together.
- Issuing a requisition should update the requisition, item lines, allocations, stock movements, and inventory balances together.
- Finalizing a pharmacy POS sale should update the sale, payments, allocations, stock movements, and receipt state together.
- Recording a visit payment should update payment records and billing summaries together.

If one part fails, the audit trail should not claim an action happened while the related business records remain incomplete.

### Add Tests For Audit Behavior

Every new audit-sensitive workflow should have tests that confirm:

- The actor field is stored.
- The timestamp is stored.
- Required reason fields are validated.
- Tenant and branch isolation is respected.
- Unauthorized users cannot view or perform the action.
- Related records are changed together.
- Reversals do not erase original history.

Tests should use factories and existing feature or unit test patterns in the project.

### Display Audit Data Where It Helps Users

Audit data is only useful if people can find it. Developers should expose audit information in record detail pages, print views, activity summaries, and support screens where it supports real decisions.

Useful display patterns:

- "Created by" and "Last updated by" in administration detail screens.
- "Approved by" and "Approved at" on lab result and inventory screens.
- Correction history on lab results.
- Cancellation and rejection reasons near the affected record.
- Stock movement history for each item and location.
- Payment and refund history on billing views.
- Recent activity on facility manager pages.

### Do Not Expose Sensitive Audit Data Too Broadly

Audit trails can contain patient names, clinical notes, staff actions, financial activity, and support context. Access should follow permissions and tenant isolation.

Recommended controls:

- Facility users only see audit data for their tenant.
- Branch-scoped users only see branch-specific data when applicable.
- Support users see cross-tenant audit data only through facility manager permissions.
- Clinical audit details require clinical permissions.
- Financial audit details require billing permissions.
- User and role audit details require administration permissions.

## Recommended Future Full Audit Log

The current system uses per-record actor fields, workflow timestamps, readiness checks, and activity summaries. A future full audit log would add immutable event records that capture every important change in one place.

A practical audit log table could store:

- `id`
- `tenant_id`
- `branch_id`
- `actor_user_id`
- `actor_staff_id`
- `event_type`
- `subject_type`
- `subject_id`
- `action`
- `old_values`
- `new_values`
- `reason`
- `ip_address`
- `user_agent`
- `occurred_at`

Example event types:

- `patient.created`
- `patient.updated`
- `visit.started`
- `visit.completed`
- `appointment.cancelled`
- `lab_result.entered`
- `lab_result.approved`
- `lab_result.corrected`
- `inventory.requisition.approved`
- `inventory.stock_moved`
- `pharmacy.sale.finalized`
- `pharmacy.sale.refunded`
- `payment.recorded`
- `user.role_changed`
- `tenant.subscription.activated`

This log should be append-only. Developers should avoid editing or deleting audit events except through controlled retention policies.

## Spatie Activitylog Implementation Plan

Spatie Laravel Activitylog is a good fit for the full audit log layer because it already provides an `activity_log` table, model subjects, causers, event names, custom properties, multiple log names, and old/new model changes. It should be added as a deeper investigation trail while keeping the existing per-record audit columns such as `created_by`, `updated_by`, `approved_by`, `released_by`, and `corrected_by`.

The existing columns are still useful for fast workflow queries and simple UI display. Spatie Activitylog should answer the deeper questions: what happened, who caused it, what record was affected, what changed, why it changed, and what tenant or branch it belonged to.

### Phase 1: Install and Configure

1. Install the package with Composer.

```bash
composer require spatie/laravel-activitylog
```

2. Publish the migration and configuration.

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

3. Review the published migration before running it.

This system uses UUIDs widely. The default package migration may need adjustments for UUID subject and causer IDs before migration. The activity table should also support tenant and branch filtering efficiently.

Recommended extra columns:

- `tenant_id`
- `branch_id`
- `staff_id`
- `ip_address`
- `user_agent`

Recommended indexes:

- `tenant_id`, `created_at`
- `branch_id`, `created_at`
- `log_name`, `created_at`
- `event`, `created_at`
- `subject_type`, `subject_id`
- `causer_type`, `causer_id`

4. Run migrations only after confirming UUID compatibility and indexes.

```bash
php artisan migrate
```

### Phase 2: Create An Application Audit Helper

Create a small application-level audit action or service so developers do not call `activity()` differently across the codebase.

Suggested class:

- `app/Actions/RecordAuditActivity.php`

The action should accept:

- log name
- event name
- subject model
- description
- actor user
- actor staff ID
- tenant ID
- branch ID
- reason
- old values
- new values
- extra properties

Example shape:

```php
activity($logName)
    ->performedOn($subject)
    ->causedBy($actor)
    ->event($event)
    ->withProperties([
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'staff_id' => $staffId,
        'reason' => $reason,
        'old_values' => $oldValues,
        'new_values' => $newValues,
        'metadata' => $metadata,
    ])
    ->log($description);
```

This wrapper keeps audit payloads consistent and makes it easier to redact sensitive fields later.

### Phase 3: Define Log Names

Use separate log names so users and developers can filter activity by domain.

Recommended logs:

- `access` for login, logout, password, 2FA, user, role, and permission activity.
- `administration` for facility setup, staff, departments, branches, services, and catalogs.
- `clinical` for patient visits, triage, consultations, lab requests, imaging, prescriptions, and clinical services.
- `laboratory` for specimen, result entry, review, approval, release, and correction events.
- `pharmacy` for prescription dispensing, POS sales, refunds, voids, and stock allocation.
- `billing` for visit charges, payments, invoices, refunds, and pricing changes.
- `inventory` for purchase orders, goods receipts, requisitions, issues, reconciliations, stock movements, and consumables.
- `support` for tenant onboarding, support notes, subscription follow-up, impersonation, and facility readiness activity.

### Phase 4: Start With Manual Business Events

Manual business-event logging should come before automatic model logging. Business events are easier for users to understand and safer for developers to reason about.

High-priority events:

- `tenant.created`
- `tenant.subscription.activated`
- `user.created`
- `user.role_changed`
- `patient.created`
- `patient.updated`
- `visit.started`
- `visit.completed`
- `appointment.confirmed`
- `appointment.cancelled`
- `consultation.completed`
- `lab_request.created`
- `lab_result.entered`
- `lab_result.reviewed`
- `lab_result.approved`
- `lab_result.released`
- `lab_result.corrected`
- `prescription.created`
- `prescription.dispensed`
- `payment.recorded`
- `pharmacy.sale.finalized`
- `pharmacy.sale.voided`
- `pharmacy.sale.refunded`
- `inventory.goods_receipt.posted`
- `inventory.requisition.approved`
- `inventory.requisition.issued`
- `inventory.reconciliation.posted`

These should be logged inside the existing action classes in `app/Actions`, after the business operation succeeds and inside the same transaction when the audit event must commit with the workflow.

### Phase 5: Add Automatic Model Logging Carefully

After manual events are in place, automatic model logging can be enabled for selected models.

Good candidates:

- master data such as units, allergens, appointment modes, appointment categories, departments, facility services, lab catalogs, insurance packages, currencies, and referral facilities.
- configuration records where old/new values are useful.
- low-volume administrative records.

Avoid broad automatic logging at first for high-volume tables such as stock movements, lab result values, POS allocations, and visit charge recalculations. Those can generate noisy logs and increase database size quickly.

When enabling model logging:

- Log only important attributes.
- Use dirty-only changes.
- Do not log empty changes.
- Exclude secrets, tokens, passwords, remember tokens, and large clinical text unless explicitly needed.
- Prefer descriptions that users can understand.

Example model option:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName('administration')
        ->logOnly(['name', 'status', 'updated_by'])
        ->logOnlyDirty()
        ->dontLogEmptyChanges();
}
```

### Phase 6: Add Tenant and Branch Scopes For Audit Views

Audit views should never show cross-tenant data unless the user has support or facility manager permissions.

Recommended views:

- Facility manager tenant activity timeline.
- Patient audit timeline.
- Visit audit timeline.
- Lab result audit timeline.
- Inventory item stock audit timeline.
- Billing and payment audit timeline.
- User and role audit timeline.

All queries should filter by `tenant_id` and, where applicable, `branch_id`. Support users can use tenant-level filters through the facility manager module.

### Phase 7: Build User-Facing Audit Screens

Start with screens that solve real operational problems.

First screens to build:

- Patient timeline: registration, visits, clinical orders, lab results, prescriptions, payments.
- Visit timeline: check-in, triage, consultation, orders, charges, payments, completion.
- Lab result timeline: entry, review, approval, release, correction.
- Inventory item timeline: receipt, movement, requisition, issue, reconciliation, dispensing, sale.
- Facility manager audit timeline: setup, subscription, support, impersonation, readiness changes.

Each entry should show:

- action label
- actor
- timestamp
- affected record
- reason or notes where available
- old/new changes when useful

### Phase 8: Retention, Cleanup, and Storage Policy

Activity logs can grow quickly. The system should define retention before production rollout.

Suggested policy:

- Keep clinical, billing, access, and inventory logs longer.
- Keep low-value administrative noise for a shorter period.
- Never clean logs blindly in production.
- Export or archive old logs before deletion when compliance requires retention.
- Schedule cleanup only after management approves retention rules.

Spatie provides an `activitylog:clean` command, but this should be used carefully because audit logs may be legally or operationally important.

### Phase 9: Testing Requirements

Every important audited workflow should have tests proving:

- the activity record is created;
- the correct log name is used;
- the correct event name is used;
- the subject is attached;
- the causer is attached;
- tenant and branch metadata are present;
- reason fields are present for corrections, cancellations, rejections, refunds, and voids;
- sensitive values are not logged;
- unauthorized users cannot view the audit entry.

Testing should focus first on manual business events in action classes.

### Phase 10: Rollout Order

Recommended rollout:

1. Install and configure package in development.
2. Add UUID-safe migration changes and tenant/branch metadata.
3. Create the shared audit recording action.
4. Log support and facility manager events.
5. Log access and user-management events.
6. Log billing and payment events.
7. Log lab result workflow events.
8. Log pharmacy and inventory workflow events.
9. Add user-facing timelines.
10. Add selected automatic model logging for master data.
11. Add cleanup and archival policy.

This staged approach avoids flooding the database with low-value events before the highest-risk workflows are covered.

## Suggested Audit Review Schedule

### Daily

- Review failed or blocked facility readiness checks.
- Review recent facility activity for unusual inactivity.
- Review lab result corrections.
- Review pharmacy voids, refunds, and stock discrepancies.
- Review payments recorded and visit billing exceptions.

### Weekly

- Review user access and inactive staff.
- Review inventory requisitions, issues, and reconciliations.
- Review support notes and unresolved facility follow-ups.
- Review past-due or cancelled subscriptions.
- Review branch and department setup completeness.

### Monthly

- Review role and permission assignments.
- Reconcile stock movements against physical counts.
- Reconcile billing reports against payments.
- Review audit gaps where records have missing actor fields.
- Review old support cases and repeated facility warnings.

## Red Flags To Investigate

Users and support teams should investigate when they see:

- Records with no actor where an actor should exist.
- Clinical results corrected without a reason.
- Payments, refunds, or voids without explanation.
- Stock movements without a linked requisition, receipt, sale, or dispensing record.
- Users acting outside their expected branch.
- Multiple failed readiness checks after a facility is live.
- Active users with no staff record where staff linkage is required.
- Staff marked inactive while their user account remains operational.
- Sudden drops in recent operational activity for an active facility.
- Repeated updates to sensitive settings by the same user.

## Developer Checklist

Before shipping a workflow that changes important data, confirm:

- Authorization is enforced.
- Tenant and branch scope are applied.
- The action class sets the correct actor fields.
- The action stores workflow timestamps.
- Corrections, cancellations, rejections, refunds, and voids require reasons.
- Multi-model changes use a database transaction.
- Tests cover actor fields, timestamps, reasons, and permissions.
- The UI exposes audit context where users need it.
- Sensitive audit data is hidden from unauthorized users.
- Existing audit history is preserved rather than overwritten.

## Summary

Auditing gives Mini-Hospital v2 memory. It helps facility users trust what they see, helps managers supervise operations, helps support teams diagnose facility readiness, and helps developers debug the real sequence of events behind a problem.

The system already has a strong audit foundation through actor columns, workflow timestamps, action classes, facility health checks, and recent activity views. The next step is to make these audit signals consistently visible, consistently tested, and eventually backed by a full immutable event log for high-value clinical, financial, inventory, and access-control actions.
