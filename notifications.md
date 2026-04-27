# Notifications in This EMR

## What notifications are

Notifications are system-generated messages that tell the right person that something important happened or is about to happen.

In an EMR, notifications are not just a convenience feature. They help the system move work from one person or department to the next without relying only on users manually refreshing pages or asking colleagues what changed.

Examples:

- a lab technician is notified that a new lab request arrived
- a doctor is notified that a lab result is ready
- pharmacy is notified that a prescription is ready to dispense
- reception is reminded that an appointment is tomorrow
- finance is notified about an unpaid balance or failed subscription checkout

## Why notifications matter in a system like this

This project already has multiple operational modules:

- appointments
- visits and triage
- doctor consultation
- laboratory
- pharmacy
- inventory
- reports
- subscriptions and facility manager workflows

That means the system already has many handoff points.

Without notifications, the workflow becomes passive:

- users must remember to check queues manually
- departments may react late
- important actions can be missed
- the system feels slower than it really is

With notifications, the workflow becomes event-driven:

- people see new tasks earlier
- delays reduce
- the system feels more alive
- fewer steps depend on memory or verbal follow-up

## Why notifications are especially important in healthcare software

Healthcare workflows involve time-sensitive work, task ownership, and accountability.

Notifications help with:

- patient safety
- turnaround time
- staff coordination
- follow-up reliability
- operational visibility

In this app, notifications would be useful for both clinical and operational work.

## High-value notification examples for this project

## 1. Appointment notifications

Useful events:

- appointment created
- appointment rescheduled
- appointment cancelled
- upcoming appointment reminder
- patient checked in

Who should receive them:

- reception
- assigned doctor
- patient later if external channels are added

## 2. Triage and doctor workflow notifications

Useful events:

- patient triaged and ready for doctor review
- consultation completed
- follow-up required
- referral documented

Who should receive them:

- assigned doctor
- triage or front desk teams where appropriate

## 3. Laboratory notifications

Useful events:

- new lab request created
- sample collected
- result ready for review
- result approved and released

Who should receive them:

- lab staff
- requesting doctor
- visit care team

This is one of the strongest notification opportunities in the current codebase because the lab workflow already has clear status transitions.

## 4. Pharmacy notifications

Useful events:

- prescription created
- prescription ready for dispensing
- prescription partially dispensed
- prescription fulfilled
- stock item out of stock during dispense

Who should receive them:

- pharmacy team
- doctor in some cases
- inventory team for stock issues

## 5. Inventory notifications

Useful events:

- requisition submitted
- requisition approved or rejected
- goods receipt posted
- stock below reorder level
- item out of stock
- stock nearing expiry

Who should receive them:

- main store
- pharmacy
- laboratory
- branch administrators

## 6. Billing and subscription notifications

Useful events:

- payment recorded
- insurance billing pending too long
- visit balance still unpaid
- subscription activation success or failure
- subscription nearing expiry

Who should receive them:

- finance users
- support users
- tenant administrators

## Notification types you could support

Laravel can support several notification channels.

## 1. Database notifications

These are stored in the database and shown inside the app.

Best first choice for this project because:

- no external provider is needed
- easy to build incrementally
- fits the current app well
- works nicely with an in-app notification bell or inbox

## 2. Mail notifications

Useful for:

- password reset
- account verification
- appointment reminders
- subscription and billing alerts

## 3. Broadcast / real-time notifications

Useful for:

- live lab queue updates
- live pharmacy queue updates
- new requisition alerts

These become more valuable later if you add Laravel Reverb or another broadcast system.

## 4. SMS or WhatsApp later

Useful for:

- appointment reminders
- patient-facing result-ready messages
- overdue payment reminders

This is valuable later, but I would not start here.

## Best first implementation for this project

The best first notification type here is:

- database notifications

Why:

- simplest to ship
- useful across many modules
- no external dependency
- gives immediate internal operational value

## How notifications work in Laravel

Laravel has a built-in notification system using notification classes.

The main pieces are:

- a notification class in `app/Notifications`
- a notifiable model, usually `User`
- a channel such as `database`, `mail`, or `broadcast`
- code that dispatches the notification when an event happens

Basic example:

```php
use App\Notifications\LabResultReleasedNotification;

$user->notify(new LabResultReleasedNotification($labRequestItem));
```

## Recommended implementation approach in this codebase

This project uses Actions heavily for business logic.

That means the cleanest notification strategy is:

- perform the business action in an Action class
- notify interested users after the action succeeds

This fits the existing architecture much better than scattering notifications in controllers.

## Where notification triggering should live here

Best places:

- Action classes after successful state changes
- possibly dedicated event classes later

Examples:

- after `CreateLabRequest`
- after lab result approval or release action
- after `CreateAppointment`
- after `CheckInAppointment`
- after dispensing post action
- after requisition submit or issue action
- after subscription activation actions

## Recommended first notification modules

If I were adding notifications here, I would start in this order:

1. Laboratory
2. Pharmacy
3. Appointments
4. Inventory requisitions
5. Subscription and finance alerts

Why this order:

- these modules already have clear workflow state changes
- they already involve cross-team handoffs
- notifications would create immediate value

## Suggested notification architecture for this app

## Step 1: use Laravel database notifications

Laravel needs a notifications table.

Run:

```bash
php artisan notifications:table --no-interaction
php artisan migrate
```

This adds a `notifications` table where in-app notifications can be stored.

## Step 2: make sure `User` is notifiable

Laravel usually uses the `Notifiable` trait on the `User` model.

Example:

```php
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    use Notifiable;
}
```

## Step 3: create notification classes

Create a notification:

```bash
php artisan make:notification LabResultReleasedNotification --no-interaction
```

Example structure:

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LabRequestItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LabResultReleasedNotification extends Notification
{
    use Queueable;

    public function __construct(private LabRequestItem $labRequestItem) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lab_result_released',
            'title' => 'Lab result released',
            'message' => 'A laboratory result is now available for review.',
            'lab_request_item_id' => $this->labRequestItem->id,
            'visit_id' => $this->labRequestItem->request?->visit_id,
        ];
    }
}
```

## Step 4: send notifications from Actions

Example idea:

```php
$doctorUser?->notify(new LabResultReleasedNotification($labRequestItem));
```

In this codebase, this should happen after the lab result release or approval action successfully commits.

## Step 5: add a notifications UI

Once database notifications exist, add:

- unread count in the sidebar or top bar
- notifications dropdown or dedicated page
- mark-as-read action

A good first page would be:

- `resources/js/pages/notifications/index.tsx`

And a simple controller:

- `app/Http/Controllers/NotificationController.php`

## Step 6: model a consistent payload shape

Keep notification payloads predictable.

Recommended fields:

- `type`
- `title`
- `message`
- `action_url`
- `resource_id`
- `resource_type`
- `occurred_at`

That makes frontend rendering easier.

## Example notification use cases for this exact app

## Laboratory result released

Trigger:

- when a lab result becomes visible or released

Recipients:

- the doctor who requested it
- maybe branch clinical staff with the right permission later

## New prescription created

Trigger:

- when the doctor creates a prescription

Recipients:

- pharmacy users in the active branch

## Inventory requisition submitted

Trigger:

- when pharmacy or lab submits a requisition

Recipients:

- main store users in that branch

## Appointment reminder

Trigger:

- scheduled job checks tomorrow's appointments

Recipients:

- reception users
- patient later through email or SMS

## Subscription near expiry

Trigger:

- scheduled job checks expiring subscriptions

Recipients:

- tenant admins
- support or facility manager users if needed

## How to choose recipients in this app

This is important.

Do not notify every user in the system.

Choose recipients based on:

- active branch
- role
- permission
- ownership
- assigned doctor or actor

Examples:

- lab notifications should target users in the same branch with lab permissions
- pharmacy notifications should target users in the same branch with pharmacy permissions
- doctor notifications should target the assigned doctor if one exists
- inventory alerts should target the right store roles

## A practical recipient pattern

For branch-scoped internal notifications, a small helper Action or service would be useful.

Example ideas:

- `NotifyBranchUsersWithPermission`
- `GetBranchUsersByPermission`

That way your Actions can stay clean and say:

```php
$this->notifyBranchUsersWithPermission->handle(
    $branchId,
    'lab_requests.view',
    new LabResultReleasedNotification($labRequestItem),
);
```

## Should notifications be queued?

Yes, eventually.

Why:

- keeps requests fast
- cleaner if later you add email or broadcast channels
- better for bursts of activity

Laravel supports queued notifications naturally.

For a first step, database notifications can be synchronous if needed, but the better production direction is queued notifications.

## Good first queue-ready design

- create notification classes now
- keep payloads small
- avoid loading huge relations inside `toArray()`
- later switch to queued notifications without redesigning the feature

## Common mistakes to avoid

## 1. Notifying too many users

This creates noise and users start ignoring notifications.

## 2. Sending vague messages

Bad:

- "Something changed"

Better:

- "Lab result released for visit VIS-10023"

## 3. Missing action links

Notifications should usually help the user navigate to the relevant screen.

## 4. Firing notifications before the transaction succeeds

If the database transaction fails, you do not want users notified about something that did not persist.

In this project, that means notification dispatch should happen after successful writes.

## 5. Mixing every notification format together

Define a consistent payload style early.

## 6. Forgetting read-state UX

If users cannot mark notifications as read, the feature quickly becomes messy.

## Suggested first implementation slice

If you want to build notifications safely in this project, I would start with this slice:

1. database notifications setup
2. unread notification count for logged-in user
3. notifications page or dropdown
4. lab result released notification
5. inventory requisition submitted notification
6. prescription created notification

This would prove the pattern across three strong workflow handoffs.

## Suggested future enhancements

Later, you can add:

- queued notifications
- real-time broadcasting
- email reminders
- scheduled appointment reminders
- stock and expiry alert digests
- tenant admin operational summaries

## Bottom line

Notifications are important in this EMR because the app already has multiple cross-team workflows where work moves from one person or module to another.

The best implementation path here is:

1. start with Laravel database notifications
2. trigger them from Action classes after successful workflow changes
3. target users by branch and permission
4. add an in-app inbox or notification bell

The highest-value first notifications for this codebase are laboratory, pharmacy, appointments, and inventory requisitions.
