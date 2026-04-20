# Support Impersonation Review and Implementation Guide

**Date:** April 20, 2026  
**Goal:** Explain how to implement a safe support impersonation workflow in this codebase after the Facility Manager cleanup, with a clear phased rollout and without requiring the target user to be online or requiring a reason prompt in version one.

---

## 1) Short Answer

Yes, this app can support impersonation cleanly.

The best design for this codebase is:

**session-based support impersonation layered on top of the existing auth, tenant, and branch context**

That means:

- the support user stays the real actor
- the app temporarily behaves as if the support user is the selected tenant user
- the switch is reversible
- the session clearly knows impersonation is active

Important assumptions for this implementation:

- the target user does **not** need to be online
- the support user does **not** need to enter a reason in version one

---

## 2) What Impersonation Should Mean Here

In this codebase, impersonation should mean:

- a support user temporarily acts as a selected tenant user
- the system resolves permissions, tenant, branch access, and navigation using the impersonated user
- the support operator can stop impersonation and return to their own support identity

This is different from:

- switching tenant context only
- logging in manually with another user account
- editing the support user permanently to look like the tenant user

The point is to let support see:

- what the tenant user sees
- what routes they can access
- what branch data they can reach
- what modules/menus they see

without actually becoming that user permanently.

---

## 3) What the Codebase Already Has

This repo already has the most important underlying foundations.

### 3.1 Auth and Session Flow Already Exist

Relevant file:

- [app/Http/Controllers/SessionController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/SessionController.php)

The app already:

- authenticates through Laravel session auth
- sets branch context after login
- handles support users differently from tenant users

That is a good base for impersonation because impersonation should also be session-based.

### 3.2 User Model Already Supports Support Users and Tenant Users

Relevant file:

- [app/Models/User.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Models/User.php)

The model already gives us:

- `tenant_id`
- `staff_id`
- `is_support`
- permission roles

That means impersonation can distinguish:

- who the real support actor is
- who the current visible app user is

### 3.3 Branch Context Is Already Session-Based

Relevant file:

- [app/Support/BranchContext.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Support/BranchContext.php)

This is very important.

Because branch selection is already stored in session, impersonation can:

- clear branch context when it starts
- let the app resolve branch access based on the impersonated user
- restore normal branch behavior when impersonation ends

### 3.4 Shared Inertia User Data Already Exists

Relevant file:

- [app/Http/Middleware/HandleInertiaRequests.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Middleware/HandleInertiaRequests.php)

This is where impersonation state should be shared to the frontend.

It is already the right place to expose:

- impersonation active or not
- real support user summary
- impersonated user summary

so the frontend can show a persistent banner.

### 3.5 Facility Manager Already Exists As the Support Home

Relevant files:

- [app/Http/Controllers/FacilityManagerController.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Controllers/FacilityManagerController.php)
- [resources/js/pages/facility-manager/users.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/users.tsx)

This is the correct place to start impersonation from.

That means impersonation does **not** need a separate support app or a separate switcher surface.

---

## 4) What the Workflow Should Be

### Step 1: Support Opens a Facility

The support user opens a tenant in Facility Manager and goes to the users page.

### Step 2: Support Clicks Impersonate

From a user row, support clicks:

- `Impersonate`

No online-presence check is required.

Why:

- this is session impersonation
- not a remote live takeover
- not a screen-sharing workflow

### Step 3: Session Starts Impersonation

The backend stores impersonation state in session and begins resolving the current app user as the impersonated user.

### Step 4: Banner Appears Everywhere

The app should clearly show:

- support is impersonating user X
- which tenant/user is active
- a `Stop Impersonation` action

### Step 5: Support Troubleshoots Normally

Support can then reproduce:

- hidden menu issues
- permission issues
- wrong branch visibility
- workflow problems
- onboarding or module packaging problems

### Step 6: Support Stops Impersonation

The app ends impersonation and returns to the real support identity.

---

## 5) What Should Not Be Required

Per your instruction, version one should **not** require:

- the impersonated user to be online
- a reason prompt

That is reasonable for an internal support MVP.

However, even without a reason prompt, the system should still log:

- who started impersonation
- who was impersonated
- when it started
- when it ended

So the workflow can stay lightweight without becoming invisible.

---

## 6) Recommended Design

The best design for this codebase is:

### 6.1 Session Overlay, Not User Mutation

Do **not** permanently rewrite the support user into the tenant user.

Instead, store impersonation state in session, for example:

- `impersonation.real_user_id`
- `impersonation.target_user_id`
- `impersonation.started_at`

Then apply that state at request time.

Why this is better:

- no permanent mutation of the support account
- simpler to stop safely
- easier to audit
- less risk of leaving the support user “stuck” as the tenant user

### 6.2 Middleware Should Apply the Impersonated Identity

Recommended middleware:

- `ApplyImpersonationContext`

Responsibilities:

1. check session for active impersonation
2. load the target user
3. replace the request/auth user context for the request lifecycle
4. preserve the real support user separately for auditing and UI

### 6.3 Keep Real Actor Available

Even when impersonation is active, the app should still know:

- real actor = support user
- visible app user = impersonated user

That can be held in a small support class such as:

- `App\Support\ImpersonationContext`

Possible helpers:

- `isActive()`
- `realUser()`
- `impersonatedUser()`

### 6.4 Branch Context Must Reset on Start and Stop

This matters because the app is branch-aware.

When impersonation starts:

- clear current branch session

When impersonation stops:

- clear branch again

Then let normal branch resolution happen for whichever identity is active.

---

## 7) Recommended Permissions

Add a clear permission:

- `tenants.impersonate`

Why explicit permission matters:

- impersonation is more sensitive than ordinary `tenants.view`
- not every support-style role should automatically impersonate

Version one can stop there.

Later, if needed, add:

- `tenants.impersonate_admin`
- `tenants.impersonate_finance`
- `tenants.impersonate_clinical`

But that is not necessary at the beginning.

---

## 8) Recommended Restrictions

Even without a reason prompt, impersonation should still be constrained.

### Suggested Version-One Rules

- only support users can impersonate
- support user must have `tenants.impersonate`
- target user must belong to the selected tenant
- target user must exist
- target user should not be another support user
- target user should not be a super admin

Optional but recommended:

- block impersonating users marked inactive

This keeps the first version safe without making it hard to use.

---

## 9) Recommended Routes

Put impersonation under Facility Manager.

Recommended routes:

- `POST /facility-manager/facilities/{tenant}/users/{user}/impersonate`
- `POST /facility-manager/impersonation/stop`

These names fit the current support architecture better than reviving a separate switcher section.

Suggested route names:

- `facility-manager.facilities.users.impersonate`
- `facility-manager.impersonation.stop`

---

## 10) Recommended Backend Entry Points

### Controller

Recommended controller:

- `FacilityImpersonationController`

Actions:

- `start`
- `stop`

### Actions / Services

Recommended services:

- `StartUserImpersonation`
- `StopUserImpersonation`

`StartUserImpersonation` should:

- verify the actor
- verify permission
- verify target belongs to tenant
- write impersonation session values
- clear branch context
- log start event

`StopUserImpersonation` should:

- remove impersonation session values
- clear branch context
- restore real support user context
- log end event

### Middleware

Recommended middleware:

- `ApplyImpersonationContext`

This should be registered globally in the web stack after auth, so the app experience is consistent.

---

## 11) Recommended Logging / Audit

Even without a reason field, impersonation should still be auditable.

### Recommended Table

- `user_impersonation_logs`

Suggested fields:

- `id`
- `actor_user_id`
- `target_user_id`
- `tenant_id`
- `started_at`
- `ended_at`
- `ip_address`
- `user_agent`
- timestamps

That is enough for version one.

If a reason is ever introduced later, it can be added without redesign.

### Why This Matters

The app should always be able to answer:

- who impersonated whom
- when it started
- when it stopped

That is the minimum operational safety line.

---

## 12) Recommended Frontend Behavior

### 12.1 Facility Manager Users Page

The users table should gain an:

- `Impersonate`

action per row where allowed.

Best place:

- [resources/js/pages/facility-manager/users.tsx](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/resources/js/pages/facility-manager/users.tsx)

### 12.2 Global Banner

When impersonation is active, every page should show a clear banner.

The banner should say:

- you are impersonating `Name / Email`
- real support user `Name`
- `Stop Impersonation`

This should be visible on all app pages until impersonation ends.

### 12.3 Shared Inertia Payload

Add impersonation data in:

- [app/Http/Middleware/HandleInertiaRequests.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Http/Middleware/HandleInertiaRequests.php)

Suggested shared structure:

```php
'impersonation' => [
    'active' => true,
    'real_user' => [
        'id' => '...',
        'name' => 'Support User',
        'email' => 'support@example.com',
    ],
    'target_user' => [
        'id' => '...',
        'name' => 'Tenant User',
        'email' => 'user@example.com',
    ],
    'started_at' => '...',
],
```

---

## 13) Important Design Decisions

### Do Not Depend on the Target User Being Online

This should not be part of the design.

Why:

- impersonation is a support session state
- not a live handoff
- not remote-control software

So the correct validation is:

- does the target user exist and belong to the tenant?

not:

- are they online right now?

### Do Not Require a Reason in Version One

This is acceptable if:

- the feature is support-only
- audit logs still exist
- impersonation banner is always visible

Later, if policy demands it, a reason can be added as a phase-two enhancement.

### Do Not Reuse the Old Tenant Switch Pattern as Full Impersonation

The old support tenant-switch flow changes context at a tenant level.

Impersonation should be cleaner and more explicit:

- specific tenant user
- session overlay
- reversible
- audited

### Keep Stop-Impersonation Extremely Easy

This should never be hidden.

The safest UX is:

- global banner
- one-click stop

---

## 14) Recommended Implementation Phases

### Phase 1: Core Impersonation Engine

Deliverables:

- `tenants.impersonate` permission
- start impersonation route
- stop impersonation route
- session impersonation state
- branch clear/reset behavior
- backend validation rules

Why first:

- this creates the actual capability
- keeps scope small

### Phase 2: Middleware and Shared App Context

Deliverables:

- `ApplyImpersonationContext` middleware
- `ImpersonationContext` support helper
- shared Inertia impersonation payload

Why next:

- this makes impersonation affect the whole app consistently

### Phase 3: Facility Manager UI

Deliverables:

- `Impersonate` action on Facility Manager users page
- stop impersonation action
- initial success/error feedback

Why then:

- gives support a usable entry point after the backend is stable

### Phase 4: Global Banner

Deliverables:

- visible banner on all pages while impersonating
- real user and target user display
- stop button in the banner

Why then:

- makes the feature safe and obvious in daily use

### Phase 5: Audit Logging

Deliverables:

- `user_impersonation_logs`
- start/end event persistence
- basic reviewability later

Why then:

- audit should exist before broad use
- but can be added after the first technical slice if needed

### Phase 6: Hardening and Policy Refinement

Deliverables:

- restrictions for privileged targets
- inactive-user rules
- optional future reason capture
- optional notifications if policy requires them

Why last:

- best added after the team sees how impersonation is actually used

---

## 15) Recommended First Implementation Order in This Repo

If I were implementing this next in the current codebase, I would do it in this exact order:

1. add `tenants.impersonate` to the permission seeder
2. add impersonation session helper/service
3. add start/stop controller and routes under Facility Manager
4. add middleware to apply impersonated identity
5. add shared Inertia impersonation payload
6. add global impersonation banner
7. add impersonate action on Facility Manager users page
8. add audit log table and persistence

That gives you a clean and support-friendly MVP.

---

## 16) Definition of Done

Support impersonation should be considered complete when:

- support users can impersonate a tenant user from Facility Manager
- target user does not need to be online
- the app clearly shows impersonation is active
- support can stop impersonation easily
- branch context behaves correctly during impersonation
- impersonation start and stop are auditable
- unauthorized users cannot impersonate

---

## 17) Bottom Line

The cleanest impersonation design for this application is:

**session-based support impersonation with middleware-applied user context, a visible global banner, and audit logging**

And for your requested policy:

- no online requirement is needed
- no reason prompt is needed in version one

So the right rollout is:

1. backend impersonation engine
2. middleware and shared context
3. Facility Manager entry point
4. global banner
5. audit logging
6. later hardening if needed
