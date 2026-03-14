# Consultation and Facility Services Review

**Date:** March 14, 2026  
**Purpose:** Review ideas from the other hospital system before updating `patient.md` or changing the current consultation plan.

---

## Why This Review Exists

We have a consultation plan in our current system, but you shared a useful reference flow from another hospital product that has a broader facility services module.

This document does not recommend copying that system as-is.

Instead, it explains:

- how that system's services flow works
- what parts are worth borrowing
- what parts do not fit our current schema cleanly
- how to adapt the useful ideas into our consultation and facility services flow

---

## How the Other System's Services Module Works

### 1. Service Catalog

The other system starts with a master `FacilityService` model. That catalog is broad and includes many kinds of services under one umbrella:

- consultation services
- radiology services
- procedures
- dental services
- maternity services
- family planning services
- ambulance services

In practice, this means the hospital first defines what services it offers, then clinicians order from that shared catalog during patient care.

### 2. Pricing Model

That system uses two pricing paths for the same service:

- **cash/base price** via `FacilityServicePrice`
- **insurance-specific price** via `InsurancePackageService`

So the same service can have:

- a normal branch price
- a package-specific insured rate

This is a familiar and useful idea, but the implementation is more service-specific than our current schema.

### 3. Service Ordering

When a clinician wants a patient to receive a service, the system creates an `OtherServiceOrder`.

That order behaves like a work item for the operational team:

- it captures the selected service
- it links the service to the patient visit
- it tracks fulfillment status
- it feeds billing and claims later

Typical lifecycle:

1. clinician orders the service
2. service department receives the request
3. status moves from pending to completed or another operational state
4. billing uses the order details for invoicing or insurance claims

### 4. Service-Specific Behavior

The useful detail in that system is that some services trigger deeper workflows.

Example:

- if the ordered service is an ambulance service, an `AmbulanceServiceTrip` record is created automatically

That means the shared service order acts as a front door, while specialized modules can branch off from it when needed.

### 5. Lab Is Separated

One very important design decision in the other system is that laboratory is **not** treated like a generic facility service order.

Lab has its own dedicated pipeline:

- service catalog for lab tests
- lab order records
- specimen/result workflow

That separation is a strong pattern and fits our system well too.

---

## What Is Good About That Design

There are several strong ideas we can reuse.

### A. A hospital-wide service catalog

This is useful because it gives the facility a single place to define non-drug, non-lab, non-radiology offerings.

Examples that fit this idea:

- consultation fees
- nursing procedures
- dressings
- physiotherapy sessions
- dental procedures
- minor theater procedures
- ambulance trips

### B. Orders as operational work items

The `OtherServiceOrder` idea is valuable because it separates:

- defining a service
- ordering a service
- completing a service
- billing a service

That matches real hospital operations well.

### C. Department fulfillment workflow

A service order can be routed to a department and worked on later. This is especially useful for:

- radiology
- dental
- physiotherapy
- procedure room work
- ambulance dispatch

### D. Optional service-specific extensions

The ambulance example is also good design. A generic order can trigger a more detailed workflow only when needed.

This suggests a clean future pattern:

- generic service order for common workflow
- specialized child record for exceptional workflows

---

## What We Should Not Copy Directly

Some parts of that design do not fit our current architecture and would create duplication if copied without adapting.

### 1. Do not mix lab into generic facility services

Our schema already has a proper dedicated lab flow:

- `lab_requests`
- `lab_request_items`
- `lab_specimens`
- `lab_results`

We should keep lab separate.

### 2. Do not collapse imaging into a generic order if we already need radiology-specific fields

Our schema already expects radiology-specific request data:

- `modality`
- `body_part`
- `laterality`
- `clinical_history`
- `contrast requirements`
- pregnancy status

That is much richer than a generic service order.

So imaging should remain a dedicated module, even if it looks like a service operationally.

### 3. Do not reintroduce old-style insurance package service tables

Our current schema already chose a more flexible direction:

- `insurance_package_prices`
- `billable_type`
- `billable_id`

That is better than maintaining separate insurance tables for each billable category.

So we should borrow the concept of insurance-specific pricing, but keep our unified pricing structure.

### 4. Do not let consultation become only a billable service

In the other system, consultation can appear as just another service type in the service catalog.

For us, consultation is more than a billable item:

- it is the main clinical note
- it captures diagnosis and disposition
- it is the source of lab/imaging/prescription orders

So consultation should remain a first-class clinical record, not just a service definition.

---

## How This Maps to Our Current Schema

Our current schema already points toward a cleaner split:

### Consultation should stay clinical

Use:

- `consultations` as the doctor encounter record

This should own:

- SOAP-style documentation
- diagnosis
- plan
- outcome/disposition
- referrals
- follow-up instructions

### Dedicated clinical support modules should stay dedicated

Use:

- `lab_requests` for lab
- `imaging_requests` for radiology
- `prescriptions` for pharmacy

These should continue to be created from a consultation where applicable.

### Facility services should cover the "everything else" category

This is the main thing we can add from the other system.

We do **not** yet have a strong operational structure for non-lab, non-imaging, non-drug, non-admission services such as:

- dressings
- physiotherapy
- dental procedures
- minor procedures
- ambulance transport
- nursing procedures
- consumable/non-lab outpatient services

This is the gap where a facility services flow would help.

---

## Recommended Direction for Our System

### Keep a clear distinction between 3 layers

#### 1. Clinical encounter layer

This is the consultation itself.

Primary record:

- `consultations`

#### 2. Specialized order modules

These already deserve their own workflows.

Primary records:

- `lab_requests`
- `imaging_requests`
- `prescriptions`
- future `procedure_requests` if we want procedure-specific structure

#### 3. General facility services layer

This is where we can borrow from the other system.

Primary records to introduce later:

- `facility_services` as the service catalog
- `facility_service_prices` only if we truly need a separate cash-price table
- `facility_service_orders` as the operational request/fulfillment record

However, because we already have `charge_masters`, we should be careful not to create duplicate pricing masters.

---

## Best Fit for Our Existing Architecture

### Option A: Reuse `charge_masters` as the pricing source and add service orders

This is the option that fits our system best.

Model idea:

- `charge_masters` stays the financial catalog
- `facility_services` becomes the operational catalog only if needed
- `facility_service_orders` references both the visit and the billable item

Benefits:

- avoids duplicate price sources
- keeps insurance pricing compatible with `insurance_package_prices`
- lets billing continue to flow through `visit_charges`

Suggested relationship:

- service order references a visit
- service order references a consultation when created by a clinician
- service order references the selected billable item
- charge is created from the order, with price frozen at order time

### Option B: Make `facility_services` the master catalog and map it to charges

This can work, but it is heavier.

You would need:

- a service catalog
- pricing linkage
- insurance pricing linkage
- a mapping from service to financial charge

This is viable, but it is more duplication than we need right now.

### Recommendation

Prefer **Option A**:

- keep `charge_masters` as the billable source of truth
- add an operational service-order layer for non-lab, non-imaging, non-pharmacy services

---

## Proposed Facility Services Flow for Our System

Here is the adapted flow I would recommend.

### Phase 1: Consultation-led ordering

During consultation, the clinician can:

- save clinical notes
- add diagnosis and plan
- place orders

Orders can be one of four types:

- lab request
- imaging request
- prescription
- facility service order

### Phase 2: Facility service ordering

For services that are not lab, imaging, or drugs:

1. clinician selects a service
2. system creates a `facility_service_order`
3. order is assigned to the relevant department/unit
4. order status is tracked
5. completion triggers or confirms billing

### Phase 3: Billing integration

When the order is placed:

- resolve cash or insurance price
- create/freeze the related `visit_charge`

That way:

- clinical teams can keep working
- finance gets an accurate billable line item
- later edits to master prices do not affect historical charges

### Phase 4: Fulfillment and completion

Operational teams update the service order:

- pending
- in_progress
- completed
- cancelled

Optional future statuses:

- scheduled
- awaiting_authorization
- rejected

### Phase 5: Specialized branching when needed

Some facility service types can later trigger deeper workflows:

- ambulance service -> ambulance trip record
- theater procedure -> procedure event record
- physiotherapy -> treatment session record

That gives us the flexibility of the other system without forcing every service into a complex custom module on day one.

---

## What We Can Borrow Immediately

These ideas are strong candidates for adoption now.

### Borrow now

- a general non-lab/non-pharmacy/non-radiology service order concept
- service order statuses for fulfillment tracking
- consultation as the main place where services are ordered
- support for service-specific follow-on workflows later
- insurance-aware service pricing resolution at order time

### Borrow later

- specialized branches like ambulance trip generation
- department-specific operational boards
- more detailed service completion notes

### Do not borrow

- a separate insurance package table just for services
- a generic service pipeline for lab
- reducing consultation to only a service charge item

---

## Suggested Tables / Concepts for Future Design

If we choose to implement facility services after consultation, these are the cleanest additions.

### Core catalog

- `facility_services`
  - operational name
  - service category
  - department/clinic ownership
  - active status
  - optional default charge mapping

### Orders

- `facility_service_orders`
  - `visit_id`
  - `consultation_id` nullable
  - `facility_service_id`
  - `ordered_by`
  - `status`
  - `priority`
  - `clinical_notes`
  - `performed_by`
  - `completed_at`
  - `cancellation_reason`

### Billing linkage

- either direct `charge_master_id`
- or `billable_type='service'` plus `billable_id`

This should still create a `visit_charge` record with frozen price.

---

## Recommended Build Order

1. Finish consultation core first
2. Add prescriptions, lab requests, and imaging requests from consultation
3. Design the general facility services catalog and order flow
4. Integrate facility service orders with `visit_charges`
5. Add specialized service extensions only where operationally necessary

This sequence matters because consultation is the clinical source of intent. The service module should support consultation, not compete with it.

---

## Bottom Line

The other system has a useful **service ordering** concept, especially for non-lab and non-pharmacy work.

The strongest ideas to adopt are:

- a catalog of facility services
- an order record that tracks fulfillment
- service-specific branching for exceptional workflows
- billing integration at order time

But in our system:

- `consultations` should remain the clinical backbone
- lab, imaging, and pharmacy should remain dedicated modules
- insurance pricing should continue to use the unified `insurance_package_prices` design
- `charge_masters` should remain central to billing unless we intentionally redesign the financial catalog

---

## Proposed Next Step

Use this review to update `patient.md` in a second pass by:

1. keeping consultation as the doctor-facing encounter record
2. adding a future "facility services" bucket after consultation orders
3. clarifying that facility services are for non-lab, non-imaging, non-pharmacy operational orders
4. planning billing integration through `visit_charges` and insurance resolution through `insurance_package_prices`
