# Notifications in This EMR

## Purpose

This document describes the current notification state of Mini-Hospital v2, what has already been achieved, what is only partial, and what still needs to be built for a production-grade hospital notification system.

It is written as a status document for developers, product owners, and support teams. The goal is to avoid guessing whether notifications are "done" when the codebase really contains only a first internal slice.

## Short Answer

Notifications are **partially implemented**.

The system already has:

- Laravel database notifications
- a notifications table
- a dedicated notifications inbox page
- unread notification count in shared Inertia props
- mark-as-read, mark-all-read, and delete actions
- three working internal workflow notifications:
  - `lab_result_released`
  - `prescription_created`
  - `inventory_requisition_submitted`
- auth-related mail notifications through Laravel and Fortify:
  - password reset
  - email verification

The system does **not** yet have a broad, production-ready notification program across appointments, billing, support, subscriptions, patient reminders, broadcasts, digests, escalation rules, or delivery preferences.

## What Has Been Achieved

### 1. Core notification infrastructure is in place

The application already uses Laravel's notification system on the `User` model through the `Notifiable` trait.

Current building blocks found in the repo:

- `app/Models/User.php`
- `database/migrations/2026_04_28_200346_create_notifications_table.php`
- `app/Http/Controllers/NotificationController.php`
- `resources/js/pages/notifications/index.tsx`
- `app/Http/Middleware/HandleInertiaRequests.php`

This means the app has moved beyond planning. There is a real in-app notification layer.

### 2. In-app database notifications exist

The system supports database-backed notifications that can be viewed inside the application.

Current user-facing capabilities:

- list notifications
- paginate notifications
- show notification title, message, type, action URL, and timestamps
- mark one notification as read
- mark all notifications as read
- delete a notification
- expose unread count globally through shared Inertia props

This is a solid internal starting point for staff-facing alerts.

### 3. Three domain notifications are already implemented

#### Laboratory

`app/Notifications/LabResultReleasedNotification.php`

This is sent after lab approval/release in:

- `app/Actions/ApproveLabResultEntry.php`

Current recipient pattern:

- the requesting doctor user, resolved from `requested_by` staff ID and tenant

What it currently communicates:

- notification type
- title
- readable message
- visit action URL
- resource ID and type
- occurrence timestamp

#### Pharmacy

`app/Notifications/PrescriptionCreatedNotification.php`

This is sent after prescription creation in:

- `app/Actions/CreatePrescription.php`

Current recipient pattern:

- users in the tenant with pharmacy dispensing permissions

This is a good internal handoff from consultation to pharmacy.

#### Inventory

`app/Notifications/InventoryRequisitionSubmittedNotification.php`

This is sent after requisition submission in:

- `app/Actions/SubmitInventoryRequisition.php`

Current recipient pattern:

- users in the tenant with inventory requisition review or issue permissions

This is a good internal handoff from requesting location to store/review users.

### 4. Recipient targeting helper exists

The system already has:

- `app/Actions/NotifyUsersWithPermission.php`

This is important because it avoids hardcoding notification recipients everywhere.

Current behavior:

- scopes recipients to tenant
- filters users by direct or role-based permissions
- sends the provided notification to all matching users

This is useful, but it is still tenant-wide rather than truly branch-aware.

### 5. Authentication mail notifications are working

The system also includes auth notification flows through Laravel and Fortify:

- password reset notification flow
- email verification notification flow

Relevant code:

- `app/Actions/CreateUserEmailResetNotification.php`
- `app/Actions/CreateUserEmailVerificationNotification.php`
- `app/Http/Controllers/UserEmailResetNotificationController.php`
- `app/Http/Controllers/UserEmailVerificationNotificationController.php`

These are not operational hospital notifications, but they are part of the platform's notification surface and they are already tested.

### 6. Notification UI and controller tests already exist

There is already a focused feature test file:

- `tests/Feature/NotificationControllerTest.php`

It covers:

- auth requirement
- notifications index rendering
- user isolation
- mark one as read
- mark all as read
- delete notification
- unread notification count in shared props

That is a good base for expanding the module safely.

## What Is Partial

These areas exist, but they are not production-complete yet.

### 1. Notification coverage across modules is still narrow

Only three operational workflows currently generate internal database notifications:

- lab result released
- prescription created
- inventory requisition submitted

That leaves many major hospital workflows without notifications.

### 2. Recipient targeting is only partly mature

`NotifyUsersWithPermission` is a useful first helper, but it is still incomplete for a real hospital environment.

Current gaps:

- no explicit branch filtering
- no shift or assignment awareness
- no user preference handling
- no throttling or deduplication
- no escalation path when no recipient matches

For a multi-branch system, branch-aware targeting will matter a lot.

### 3. Notification payload shape is good but still basic

Current payload fields are fairly consistent:

- `type`
- `title`
- `message`
- `action_url`
- `resource_id`
- `resource_type`
- `occurred_at`

That is good enough for the current inbox UI, but still limited.

Missing production-friendly fields may include:

- tenant ID
- branch ID
- severity
- workflow status
- actor name or role
- patient-safe summary text
- deduplication key
- expiration date

### 4. The inbox UI exists, but there is no notification center experience yet

Current UI supports a dedicated notifications page.

Still missing:

- top-bar dropdown or bell tray
- grouped unread vs read sections
- filtering by type
- filtering by branch
- filtering by date or severity
- bulk delete
- bulk archive
- richer deep links
- empty-state guidance by role

### 5. Notifications are internal only

The notification system today is mostly in-app and staff-facing.

Still missing:

- email reminders for operational workflows
- SMS or WhatsApp for patients
- real-time broadcast events
- push notifications
- scheduled digest notifications

### 6. Queue posture is only partially mature

The notification classes use `Queueable`, which is a good sign, but the repo does not yet show a broader notification delivery strategy built around queued fan-out, retries, and failure monitoring for operational messages.

That means production-readiness is still partial here.

## What Is Not Done

The following high-value areas are still not implemented, or at least not found in the codebase as working notification flows.

### Clinical and operational workflows not yet covered

- appointment created
- appointment rescheduled
- appointment cancelled
- appointment reminder
- patient checked in
- triage completed and ready for clinician
- consultation completed
- referral created
- imaging order ready
- imaging result ready
- prescription dispensed
- prescription partially dispensed
- stock-out during dispensing
- goods receipt posted
- requisition approved
- requisition rejected
- low stock alert
- expiry warning
- payment recorded
- outstanding balance reminder
- insurance claim exception
- subscription nearing expiry
- subscription failed payment
- support follow-up reminder

### Delivery channels not yet done

- real-time broadcasting
- mail-based operational notifications
- patient-facing reminders
- scheduled summary digests
- delivery preferences per user
- mute or snooze controls

### Operational controls not yet done

- severity levels
- escalation rules
- de-duplication
- notification retention/archive policy
- audit linkage between notification and business event
- analytics on sent/read/actioned notifications

## Current Status by Area

### Completed

- Laravel notifications foundation
- notifications table
- `User` notifiable setup
- notifications index page
- unread count in shared props
- mark-as-read flow
- mark-all-read flow
- delete notification flow
- lab result released notification
- prescription created notification
- inventory requisition submitted notification
- auth notification flows for password reset and email verification
- feature coverage for notification inbox behavior

### Partial

- permission-based recipient targeting
- notification payload standardization
- queue readiness
- staff-facing inbox UX
- cross-module notification coverage

### Not Done

- branch-aware routing
- billing notifications
- appointment reminders
- support and subscription notifications
- patient-facing messaging
- broadcast or real-time delivery
- notification preferences
- severity and escalation policy
- delivery observability and reporting

## Recommended Next Notification Steps

If we want a production-grade hospital notification module, the next steps should be:

1. Make recipient targeting branch-aware.
2. Add notifications for billing, support, and subscription follow-up.
3. Add appointment reminders and triage-to-clinician handoff alerts.
4. Add requisition approval, low-stock, and near-expiry inventory alerts.
5. Add a top-bar notification tray and filtering by type.
6. Queue operational notifications consistently.
7. Decide which notifications stay internal and which later become email or SMS.
8. Add audit linkage so major notifications correspond cleanly to business events.

## Suggested Production-Ready Notification Scope

To be genuinely production useful, this system should eventually support:

- internal database notifications for all critical handoffs
- role- and branch-aware recipient selection
- real-time refresh or broadcast for urgent operational queues
- email for account and administrative reminders
- optional patient reminders through external channels
- severity levels and escalation rules
- notification preferences and retention rules
- strong tests around recipient isolation and message generation

## Bottom Line

Notifications are **started and genuinely useful**, but they are **not complete**.

The current implementation proves the architecture:

- Laravel database notifications work
- the inbox UI works
- unread counts work
- real workflows can trigger notifications

What exists today is a good first operational slice, not yet the full production-grade notification module that a hospital system will need.
