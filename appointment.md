# Appointment Module - Proposed Implementation Plan

**Date:** March 16, 2026
**Status:** Proposed next module
**Primary References:** `hospital_database_schema.md`, `patient.md`, `patient_visit.md`

---

## 1) Goal

Introduce an appointments module that handles:

- doctor scheduling
- appointment booking
- appointment categories CRUD
- appointment modes CRUD
- appointment queue and check-in
- conversion of checked-in appointments into real patient visits

The appointment module should remain a scheduling and front-desk coordination layer.
It must not replace the existing visit, triage, consultation, or downstream order workflows.

Core principle:

- appointment = planned encounter
- visit = actual encounter
- triage / consultation / orders = clinical work after check-in

---

## 2) How This Fits The Current System

The current application already has:

- `patients`
- `patient_visits`
- `triage_records`
- `consultations`
- dedicated triage and consultation workspaces

The schema already includes:

- `doctor_scheduling`
- `appointments`
- `patient_visits.appointment_id`

That means the clean design is:

1. a patient is booked into an appointment
2. the patient arrives and is checked in
3. check-in creates a `patient_visit`
4. the visit continues through triage, consultation, orders, and billing

This avoids duplicating clinical logic inside the appointments module.

---

## 3) Module Boundaries

### 3.1 Appointments should own

- doctor availability and schedule setup
- appointment slot booking
- appointment list/calendar views
- confirmation, rescheduling, cancellation, no-show handling
- reception check-in workflow
- appointment categories CRUD
- appointment modes CRUD

### 3.2 Appointments should not own

- triage capture
- consultation notes
- prescriptions
- lab requests
- imaging requests
- facility service orders
- billing completion

Those should stay under the visit workflow after appointment check-in creates the visit.

---

## 4) Recommended Data Model

### 4.1 Keep and implement `appointments`

Use the schema direction already documented in [hospital_database_schema.md](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/hospital_database_schema.md).

Recommended fields:

- `tenant_id`
- `facility_branch_id`
- `patient_id`
- `doctor_id`
- `clinic_id`
- `appointment_category_id`
- `appointment_mode_id`
- `appointment_date`
- `start_time`
- `end_time`
- `status`
- `reason_for_visit`
- `chief_complaint`
- `is_walk_in`
- `queue_number`
- `checked_in_at`
- `completed_at`
- `cancellation_reason`
- `cancelled_by`
- `rescheduled_from_appointment_id`
- `notes`
- `created_by`
- `updated_by`

Recommended relationship additions:

- belongs to `patient`
- belongs to `doctor`
- belongs to `clinic`
- belongs to `category`
- belongs to `mode`
- has one `visit`
- belongs to `rescheduledFrom`
- has many `reschedules`

### 4.2 Add `doctor_schedules`

The schema already points to doctor scheduling.
Implement it as the source of weekly recurring availability.

Recommended fields:

- `tenant_id`
- `facility_branch_id`
- `doctor_id`
- `clinic_id`
- `day_of_week`
- `start_time`
- `end_time`
- `slot_duration_minutes`
- `max_patients`
- `valid_from`
- `valid_to`
- `is_active`
- `notes`
- `created_by`
- `updated_by`

### 4.3 Add `appointment_categories`

This should be a proper master-data CRUD table, not an enum.

Recommended fields:

- `tenant_id`
- `facility_branch_id` nullable
- `name`
- `description`
- `clinic_id` nullable
- `is_active`
- `created_by`
- `updated_by`
- soft deletes

Purpose:

- classify appointment intent or service stream
- examples: general consultation, review, antenatal, dental, dialysis

### 4.4 Add `appointment_modes`

This should also be CRUD master data, not an enum.

Recommended fields:

- `tenant_id`
- `name`
- `description`
- `is_virtual`
- `is_active`
- `created_by`
- `updated_by`
- soft deletes

Purpose:

- define how the appointment happens
- examples: physical, virtual, phone follow-up, home visit

### 4.5 Recommended extra table: `doctor_schedule_exceptions`

This is not in the current schema section, but it will help the module feel complete.

Recommended fields:

- `tenant_id`
- `facility_branch_id`
- `doctor_id`
- `clinic_id` nullable
- `exception_date`
- `start_time` nullable
- `end_time` nullable
- `type`
- `reason`
- `is_all_day`
- `created_by`
- `updated_by`

Use cases:

- annual leave
- meetings
- public holiday closure
- ad hoc clinic blocking

---

## 5) Enums

Use the same enum code style already used in the system:

- backed string enums
- `label()` helper
- optional `color()` helper when UI badges need it
- optional workflow helper methods like `isFinalized()`

### 5.1 Keep `AppointmentStatus`

The project already has [app/Enums/AppointmentStatus.php](/c:/Users/Manoah/Desktop/projects/personal-practice/mini-hospital-v2/app/Enums/AppointmentStatus.php).

Use it as the appointment lifecycle enum:

- `scheduled`
- `confirmed`
- `checked_in`
- `in_progress`
- `completed`
- `no_show`
- `cancelled`
- `rescheduled`

### 5.2 Add `ScheduleDay`

Instead of inline string arrays for weekdays, use a repo-style enum.

Recommended values:

- `monday`
- `tuesday`
- `wednesday`
- `thursday`
- `friday`
- `saturday`
- `sunday`

### 5.3 Add `ScheduleExceptionType`
To handle things like public holidays, leave etc

### 5.4 Add `AppointmentReminderChannel`
To handle different reminder channels

Do not turn categories or modes into enums.
Those should stay CRUD-driven master data tables.

---

## 6) Appointment Lifecycle

### 6.1 Booking statuses

- `scheduled`
- `confirmed`

### 6.2 Arrival and handoff statuses

- `checked_in`
- `in_progress`

### 6.3 Terminal statuses

- `completed`
- `cancelled`
- `no_show`
- `rescheduled`

### 6.4 Workflow rules

- new bookings start as `scheduled`
- reception can move `scheduled -> confirmed`
- reception can move `scheduled|confirmed -> checked_in`
- check-in creates a `patient_visit` linked through `appointment_id`
- if linked visit moves to `in_progress`, appointment should also move to `in_progress`
- if linked visit completes, appointment should move to `completed`
- `cancelled`, `completed`, and `no_show` are terminal
- `rescheduled` should remain as a historical record and point to the new appointment

---

## 7) Visit Integration

This is the most important part of the module.

### 7.1 Check-in action

When an appointment is checked in:

1. validate that the appointment is not terminal
2. ensure the patient has no other active visit
3. create `patient_visits` row
4. set:
   - `patient_id`
   - `facility_branch_id`
   - `clinic_id`
   - `doctor_id`
   - `appointment_id`
   - `visit_type = outpatient`
   - `status = registered`
5. store the visit payer using the current visit-start rules
6. stamp `checked_in_at`
7. set appointment status to `checked_in`

### 7.2 After check-in

The user should be able to:

- go to the visit page
- go to the triage workspace
- later go to consultation

### 7.3 Status sync with visit

Recommended behavior:

- visit `registered` -> appointment stays `checked_in`
- visit `in_progress` -> appointment becomes `in_progress`
- visit `completed` -> appointment becomes `completed`
- visit `cancelled` should not automatically cancel the appointment history; use `completed` or preserve the prior checked-in state depending on actual business need

---

## 8) Categories and Modes CRUD

These should be implemented like the other master-data modules already in the app.

### 8.1 Appointment Categories

Pages:

- `GET /appointment-categories`
- `GET /appointment-categories/create`
- `GET /appointment-categories/{appointmentCategory}/edit`

Actions:

- list
- create
- update
- delete / deactivate

Use cases:

- general outpatient
- review
- specialist clinic
- procedure follow-up

### 8.2 Appointment Modes

Pages:

- `GET /appointment-modes`
- `GET /appointment-modes/create`
- `GET /appointment-modes/{appointmentMode}/edit`

Actions:

- list
- create
- update
- delete / deactivate

Use cases:

- physical
- virtual
- phone
- home visit

### 8.3 CRUD implementation style

Follow the same system patterns used by:

- `clinics`
- `departments`
- `units`
- `facility-services`

That means:

- standard Laravel resource controllers
- request classes for validation
- action classes for create/update/delete where already consistent
- Inertia React pages under `resources/js/pages`
- shared list/create/edit patterns

---

## 9) Recommended Routes

### 9.1 Schedules

- `GET /appointments/schedules`
- `GET /appointments/schedules/create`
- `POST /appointments/schedules`
- `GET /appointments/schedules/{schedule}/edit`
- `PUT /appointments/schedules/{schedule}`
- `DELETE /appointments/schedules/{schedule}`

### 9.2 Appointments

- `GET /appointments`
- `GET /appointments/create`
- `POST /appointments`
- `GET /appointments/{appointment}`
- `PUT /appointments/{appointment}`
- `POST /appointments/{appointment}/confirm`
- `POST /appointments/{appointment}/check-in`
- `POST /appointments/{appointment}/reschedule`
- `POST /appointments/{appointment}/mark-no-show`
- `POST /appointments/{appointment}/cancel`

### 9.3 Categories

- `Route::resource('appointment-categories', AppointmentCategoryController::class)->except(['show'])`

### 9.4 Modes

- `Route::resource('appointment-modes', AppointmentModeController::class)->except(['show'])`

---

## 10) Recommended Controllers

### 10.1 Scheduling

- `DoctorScheduleController`

Responsibilities:

- list schedules
- create schedule
- update schedule
- disable schedule

### 10.2 Booking

- `AppointmentController`

Responsibilities:

- index
- create
- store
- show
- update booking details

### 10.3 Status operations

- `AppointmentConfirmationController`
- `AppointmentCheckInController`
- `AppointmentRescheduleController`
- `AppointmentCancellationController`
- `AppointmentNoShowController`

This matches the explicit workflow style already being used in the app.

### 10.4 Master data

- `AppointmentCategoryController`
- `AppointmentModeController`

---

## 11) Recommended Actions

To stay aligned with the project style, use action classes for stateful operations.

### 11.1 Scheduling

- `CreateDoctorSchedule`
- `UpdateDoctorSchedule`
- `DeleteDoctorSchedule`

### 11.2 Booking and status changes

- `CreateAppointment`
- `UpdateAppointment`
- `ConfirmAppointment`
- `CheckInAppointment`
- `RescheduleAppointment`
- `CancelAppointment`
- `MarkAppointmentNoShow`

### 11.3 Master data

- `CreateAppointmentCategory`
- `UpdateAppointmentCategory`
- `DeleteAppointmentCategory`
- `CreateAppointmentMode`
- `UpdateAppointmentMode`
- `DeleteAppointmentMode`

### 11.4 Visit sync

- `SyncAppointmentStatusFromVisit`

This action can later be triggered from visit transitions so the appointment and visit remain aligned after check-in.

---

## 12) Recommended Requests

- `StoreDoctorScheduleRequest`
- `UpdateDoctorScheduleRequest`
- `StoreAppointmentRequest`
- `UpdateAppointmentRequest`
- `ConfirmAppointmentRequest`
- `CheckInAppointmentRequest`
- `RescheduleAppointmentRequest`
- `CancelAppointmentRequest`
- `MarkAppointmentNoShowRequest`
- `StoreAppointmentCategoryRequest`
- `UpdateAppointmentCategoryRequest`
- `DeleteAppointmentCategoryRequest`
- `StoreAppointmentModeRequest`
- `UpdateAppointmentModeRequest`
- `DeleteAppointmentModeRequest`

Validation themes:

- no booking in the past unless walk-in
- doctor must belong to the branch context
- clinic and doctor must be compatible
- no overlapping active slots for the same doctor
- rescheduled target must be a valid future slot
- check-in should fail if patient already has another active visit

---

## 13) Recommended Frontend Pages

### 13.1 Schedules

- `resources/js/pages/appointments/schedules/index.tsx`
- `resources/js/pages/appointments/schedules/create.tsx`
- `resources/js/pages/appointments/schedules/edit.tsx`

### 13.2 Appointments

- `resources/js/pages/appointments/index.tsx`
- `resources/js/pages/appointments/create.tsx`
- `resources/js/pages/appointments/show.tsx`

### 13.3 Categories

- `resources/js/pages/appointment-category/index.tsx`
- `resources/js/pages/appointment-category/create.tsx`
- `resources/js/pages/appointment-category/edit.tsx`

### 13.4 Modes

- `resources/js/pages/appointment-mode/index.tsx`
- `resources/js/pages/appointment-mode/create.tsx`
- `resources/js/pages/appointment-mode/edit.tsx`

### 13.5 UI expectations

Follow the same visual and architectural patterns already used in the app:

- `AppLayout`
- `Head`
- list pages with search and pagination
- create/edit pages with cards and clear actions
- detail page for appointment operations
- route strings or generated route helpers following existing project usage

---

## 14) Appointment Page Design

### 14.1 Appointment list

Should support:

- search by patient, phone, appointment date, doctor
- filters by:
  - date
  - doctor
  - clinic
  - status
  - category
  - mode
- quick actions:
  - confirm
  - check in
  - reschedule
  - cancel
  - mark no-show

Recommended views:

- day queue
- upcoming list
- optional later calendar

### 14.2 Appointment create page

Sections:

- patient selection
- appointment details
- schedule slot selection
- category and mode
- notes / complaint

Recommended behavior:

- start with existing patient booking
- add quick patient registration later if needed

### 14.3 Appointment details page

Show:

- patient snapshot
- appointment timing
- doctor and clinic
- category and mode
- current status
- linked visit if checked in

Actions:

- confirm
- check in
- reschedule
- cancel
- mark no-show
- open linked visit if one exists

---

## 15) Scheduling Rules

### 15.1 Slot generation

Slots should be generated from:

- schedule day
- start time
- end time
- slot duration
- active validity dates
- exception dates

### 15.2 Capacity rules

Use:

- `max_patients`
- current active appointments in that slot

### 15.3 Overlap rules

Do not allow:

- overlapping active schedules for the same doctor and clinic window
- booking beyond configured capacity

---

## 16) Suggested Build Order

### Phase 1: Master data and scheduling

1. implement `appointment_categories`
2. implement `appointment_modes`
3. implement `doctor_schedules`
4. add `ScheduleDay` enum

### Phase 2: Booking core

5. implement `appointments` migration/model
6. build appointment list/create/show pages
7. add booking, confirm, cancel, no-show, reschedule actions

### Phase 3: Check-in and visit handoff

8. implement `CheckInAppointment`
9. create `patient_visit` from appointment
10. link `appointment_id` on visit
11. add appointment-to-visit status sync

### Phase 4: Queue refinement

12. add doctor-facing “my appointments”
13. add day queue by clinic/doctor
14. add schedule exceptions

### Phase 5: Notifications and reports

15. add booking and cancellation notifications
16. add scheduled appointment report

---

## 17) Business Rules Summary

- one appointment can produce at most one visit
- one checked-in appointment must link to one visit
- one patient cannot be checked in to a new appointment if they already have an active visit
- appointment categories and modes are managed through CRUD, not enums
- doctor schedules define slot supply
- appointments consume schedule capacity
- appointments do not directly create orders or consultation records
- all clinical work starts from the visit created at check-in

---

## 18) Recommended Permissions

Suggested permissions:

- `appointments.view`
- `appointments.create`
- `appointments.update`
- `appointments.confirm`
- `appointments.check-in`
- `appointments.cancel`
- `appointments.no-show`
- `appointments.reschedule`
- `appointments.view-my`
- `appointments.view-reports`
- `doctor-schedules.view`
- `doctor-schedules.create`
- `doctor-schedules.update`
- `doctor-schedules.delete`
- `appointment-categories.view`
- `appointment-categories.create`
- `appointment-categories.update`
- `appointment-categories.delete`
- `appointment-modes.view`
- `appointment-modes.create`
- `appointment-modes.update`
- `appointment-modes.delete`

---

## 19) Definition of Done

The appointment module should be considered complete when:

- staff can configure doctor schedules
- staff can manage appointment categories
- staff can manage appointment modes
- reception can book, confirm, reschedule, cancel, and mark no-show
- reception can check in an appointment
- check-in creates a linked visit
- triage and consultation continue through the existing visit workflow
- appointment and visit statuses remain reasonably synchronized after check-in

---

## 20) Recommended First Implementation Slice

If building this incrementally, the best first slice is:

1. `appointment_categories` CRUD
2. `appointment_modes` CRUD
3. `doctor_schedules` CRUD
4. `appointments` booking
5. `appointments/{appointment}/check-in`
6. create linked visit and open visit page

That gives the system a usable front-desk appointment workflow without waiting for reporting or notifications.
