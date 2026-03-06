# Implementation Plan & Build Order

Building a Multi-Tenant Hospital Management System requires a structured approach to prevent foreign key constraint failures and to ensure that foundation layers are solid before complex business logic is applied. 

Below is the recommended order of implementation and the reasoning behind it, followed by a list of potentially missing entities from the current schema.

---

## Phase 1: Foundation & Base Helper Modules

These represent the absolute lowest level of the dependency tree. They are mostly independent lookup/reference tables that other core entities will depend on.

- **Tables to build**: `countries`, `addresses`, `currencies` (see Missing Features), `subscription_packages`, `allergens`
- **Why build first?**
  You cannot create a tenant without a subscription, country, or address. Similarly, patient and staff records will heavily rely on base addresses and country codes. Having these in place means you will never face a missing foreign key when seeding your primary tables.

## Phase 1.5: Roles & Permissions (RBAC)

Because security and access control are critical to a multi-tenant application, roles and permissions should be defined immediately after the foundation. 

- **Tables to build**: `roles`, `permissions`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` (Provided by `spatie/laravel-permission` package).
- **Why build first?**
  Staff and Users will need to be assigned roles immediately upon creation in Phase 2. Defining the permissions early allows seamless seeding of an "Admin" or "Superadmin" user in the subsequent steps.

## Phase 2: Multi-Tenant Architecture & Security

This is the core pillar of the multi-branch, multi-tenant architecture. All subsequent data will belong to these entities.

- **Tables to build**: `tenants`, `facility_branches`, `departments`, `staff` (Users), `staff_branches`, `staff_addresses`
- **Why build first?**
  Every patient, visit, appointment, and transaction requires a `tenant_id` and often a `branch_id`. You also need authenticated `staff` members to act as the `created_by` or `updated_by` reference (audit trails) for all other records. Establishing global tenant scopes and authentication here is critical before moving on.

## Phase 3: Hospital Infrastructure & Service Catalogs

Before patients arrive, the hospital must define what it is and what it offers.

- **Tables to build**: `clinics`, `wards`, `beds`, `charge_masters`, `lab_test_catalogs`, `medication_catalogs`
- **Why build first?**
  These represent the physical limits and financial services of the hospital. You cannot admit a patient without a `bed`, you cannot order a test without a `lab_test_catalog` entry, and you cannot bill without `charge_masters`. These catalogs are relatively static master data.

## Phase 4: Patient Registration & Demographics

The central entity of the healthcare system.

- **Tables to build**: `patients`, `patient_addresses`, `patient_allergies`, `past_medical_histories`, `patient_insurances` (see Missing Features)
- **Why build first?**
  Patients are the focus of the system. You cannot schedule an appointment, create a visit, or order medications without a registered patient. Dependencies from Phase 1 (addresses, allergens) and Phase 2 (tenant, creator) are already resolved, making this safe to implement.

## Phase 5: Scheduling & Outpatient (OPD) Workflow

The daily operational flow of a hospital.

- **Tables to build**: `schedules`, `appointments`, `patient_visits`, `triage_records`, `vital_signs`, `consultations`
- **Why build first?**
  This maps to the real-world workflow:
  1. Doctor creates a `schedule`.
  2. Patient books an `appointment`.
  3. Patient arrives and a `patient_visit` encounter is opened.
  4. Nurse does `triage_records` and `vital_signs`.
  5. Doctor performs `consultations`.

## Phase 6: Clinical Support Services (Lab, Radiology, Pharmacy)

These are investigations and treatments resulting from a consultation.

- **Tables to build**: 
  - *Lab*: `lab_requests`, `lab_request_items`, `lab_specimens`, `lab_results`
  - *Radiology*: `imaging_requests`, `imaging_studies`, `radiology_reports`
  - *Pharmacy*: `prescriptions`, `prescription_items`, `dispensing_records`
- **Why build first?**
  These are ordered explicitly during or after a `consultation`. They depend on the visit and consultation context to exist. You also need the Catalogs from Phase 3 to know *what* can be requested.

## Phase 7: Inpatient Operations (IPD)

The most complex continuous care loop.

- **Tables to build**: `ipd_admissions`, `nursing_care`, `medication_administrations`
- **Why build first?**
  IPD relies on `wards` and `beds` being set up (Phase 3), as well as an initial `patient_visit` or emergency encounter (Phase 5) that led to the admission. It sits near the top of the dependency chain.

## Phase 8: Billing, Finance & Auditing

The culmination of all hospital activities.

- **Tables to build**: `visit_charges`, `visit_billings`, `payments`, `audit_logs`
- **Why build first?**
  Billing aggregates data from all other modules: consultations, lab tests, pharmacy dispensing, and IPD bed charges. It must be the last operational step implemented because it references almost every service module. (Note: `audit_logs` should be conceptually configured early via model observers, but its reporting UI/logic is usually done last).

---

## 🔍 What Could Be Missing From the Current Schema?

While the schema is comprehensive, the following components are either missing or implicitly referenced without a defined table:

1. **Currencies Table**
   - **Issue**: `facility_branches` references a `currency_id` constrained to `currencies`, but the `currencies` table is not explicitly defined in the schema (only `countries` is, which contains currency strings, but not an independent table).

2. **Insurance Management**
   - **Issue**: `visit_billings` references `insurance_id` constrained to `patient_insurances`. However, `patient_insurances` and master `insurance_companies` (HMOs/TPA) tables are completely missing. You need tables to manage insurance providers, plans, and patient policy subscriptions.

3. **Roles & Permissions (RBAC)**
   - **Issue**: The `staff` table has a simple `role` string column. **[RESOLVED in Phase 1.5]**: Implemented via `spatie/laravel-permission`.

4. **Inventory & Procurement (Supply Chain)**
   - **Issue**: You have `medication_catalogs` but no tables for tracking stock levels, purchase orders, suppliers, or stock adjustments. A pharmacy cannot dispense without knowing physical inventory counts.

5. **Procedures & Surgeries (OR)**
   - **Issue**: The Relationships Summary explicitly mentions `procedure_requests`, but it is not defined in the schema. You will need tables for Operating Theater scheduling, intra-operative notes, and anesthesia records.

6. **Patient Portal / User Accounts**
   - **Issue**: If patients are meant to log in to book appointments or view lab results, they need authentication credentials. Typically, this is solved by either a generic `users` table linked polymorphically, or adding login capability directly to the `patients` table.

7. **System Notifications / Communications**
   - **Issue**: A table for tracking outgoing SMS and Emails (e.g., appointment reminders, lab result readiness) is highly recommended for auditing patient communication.
