# Patient Module Implementation Review

**Date:** March 13, 2026

---

## Current Implementation Status

### ✅ Implemented

| Component                | Status        | Notes                                                                                 |
| ------------------------ | ------------- | ------------------------------------------------------------------------------------- |
| Patient Model            | ✅ Complete   | Demographics only - contact, next of kin, blood group, occupation, religion           |
| Patient Controller       | ✅ Complete   | CRUD: index, create, store, edit, update, destroy                                     |
| Patient Actions          | ✅ Complete   | CreatePatient, UpdatePatient, DeletePatient                                           |
| Form Requests            | ✅ Complete   | StorePatientRequest, UpdatePatientRequest, DeletePatientRequest                       |
| Frontend Index           | ✅ Complete   | Search, pagination, table with MRN, name, phone, gender                               |
| Frontend Create          | ✅ Complete   | Multi-section form (Bio, Contact, Kin) - billing moved to visit                       |
| Frontend Edit            | ✅ Complete   | Same structure as create                                                              |
| Patient Allergies        | ✅ Complete   | Separate model, controller, routes (`patients.allergies` resource)                    |
| Patient Insurance        | ✅ Deprecated | Moved to visit-level (visit_payers) - patient insurances retained for historical data |
| PayerType Enum           | ✅ Complete   | Supports `cash` and `insurance`                                                       |
| Routes                   | ✅ Complete   | `Route::resource('patients')`                                                         |
| PatientVisit Model       | ✅ Complete   | Encounter tracking with status, clinic, doctor                                        |
| Visit Payer              | ✅ Complete   | Per-visit billing snapshot (visit_payers table)                                       |
| Register + Visit Start   | ✅ Complete   | Single transaction registration + visit creation                                      |
| Visit Status Transitions | ✅ Complete   | TransitionPatientVisitStatus action                                                   |
| One Active Visit Rule    | ✅ Complete   | Prevents multiple active visits per patient                                           |
| Patient Visit History    | ✅ Complete   | Visit history shown on patient profile                                                |

### ❌ Not Implemented

| Component               | Priority | Notes                                                               |
| ----------------------- | -------- | ------------------------------------------------------------------- |
| Patient Search Endpoint | High     | No dedicated search API to check for duplicates before registration |
| Triage Records          | High     | No triage_records table yet                                         |
| Vital Signs             | High     | No vital_signs table yet                                            |
| Consultation Records    | High     | No consultations table yet                                          |
| Queue/Triage View       | Medium   | No queue page for triaging registered patients                      |
| Visit Status Logs       | Medium   | No immutable audit timeline for visit transitions                   |
| Billing Flow            | Medium   | No visit_charges, visit_billings, payments                          |
| Insurance Claims        | Low      | No claim workflow for insurance companies                           |

---

## Design Pattern Review

### Current Design (Patient Pages)

The patient create/edit pages use:

- `Card` component with `CardHeader` and `CardTitle` for sections
- Form fields in grid layouts
- Basic header with title and back button

### Expected Design (per allergen pattern)

The allergen module uses:

- Header with icon (e.g., `formatIdentifierLabel` helper)
- Form inside bordered container
- Proper label styling (`text-sm font-semibold`)
- Cancel button in footer

**Gap:** Patient create/edit pages do NOT follow the allergen design pattern. Should be updated to match.

---

## Data Model Review

### Patient Model - ✅ Demographics Only

```php
// Fillable fields - demographics only, no billing
$fillable = [
    'tenant_id',
    'patient_number',        // MRN
    'first_name', 'last_name', 'middle_name',
    'date_of_birth', 'age', 'age_units',
    'gender',
    'email', 'phone_number', 'alternative_phone',
    'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_relationship',
    'address_id',
    'marital_status', 'occupation', 'religion',
    'country_id',
    'blood_group',
    'created_by', 'updated_by',
];
```

### Relationships - ✅ Good

- `country()` - BelongsTo
- `address()` - BelongsTo
- `allergies()` - HasMany
- `activeAllergies()` - HasMany (scoped to active)
- `visits()` - HasMany (PatientVisit)
- `activeVisit()` - HasOne (scoped to active visit)
- `insurances()` - HasMany (PatientInsurance - deprecated for new registrations, kept for historical data)

### Implemented Models

1. **PatientVisit** ✅ - Encounter tracking with status, clinic, doctor
2. **VisitPayer** ✅ - Per-visit billing snapshot

### Pending Models

3. **TriageRecord** - For triage data
4. **VitalSign** - For vital signs
5. **Consultation** - For doctor consultations
6. **VisitCharge** - For billing line items
7. **VisitBilling** - For billing header
8. **Payment** - For payment records
9. **VisitStatusLog** - For immutable audit timeline

---

## Workflow Review

### Documented Flow (from patient_visit.md)

1. **Patient Search First** → Check for duplicates by phone/MRN/name+DOB
2. **New Patient Registration + Visit Start** → Create patient + visit + payer in single transaction
3. **Visit Check-in** → Choose payer (cash vs insurance) per visit (visit_payers table)
4. **Triage and Clinical Flow** → Queue, triage, consultation (NOT YET IMPLEMENTED)
5. **Billing and Payment** → Charges, payments, claims (NOT YET IMPLEMENTED)

### Actual Implementation

The current implementation covers **Step 2-3**:

- ✅ Patient registration form exists
- ✅ Single transaction: patient + visit + payer in one screen
- ✅ Per-visit payer selection (cash vs insurance)
- ✅ Insurance selection happens per visit, not per patient
- ✅ Visit status transitions (registered → in_progress → completed)
- ✅ One active visit rule enforced
- ❌ No patient search before registration
- ❌ No triage queue
- ❌ No billing flow (charges, billings, payments)
- ❌ No consultation records

### Visit Statuses (Simplified)

- `registered` - Patient checked in, waiting for clinical action
- `in_progress` - Clinical activity started (triage, consultation)
- `awaiting_payment` - Defined but not wired into workflow yet
- `completed` - Visit closed
- `cancelled` - Visit cancelled

---

## Recommendations

### High Priority

1. **Add patient search** before registration to prevent duplicates
2. **Implement Triage Records** - triage form, status promotion to `in_progress`
3. **Implement Consultation Records** - clinician notes, status completion

### Medium Priority

4. Add queue/triage view for registered patients
5. Add visit status logs for audit timeline
6. Implement billing and payment flow (visit_charges, visit_billings, payments)

### Low Priority

7. Add insurance claim tracking
8. Add patient portal (future)

---

## Code Locations

| Component               | Path                                                                                 |
| ----------------------- | ------------------------------------------------------------------------------------ |
| Patient Model           | `app/Models/Patient.php`                                                             |
| Patient Controller      | `app/Http/Controllers/PatientController.php`                                         |
| Patient Actions         | `app/Actions/{Create,Update,Delete}Patient.php`                                      |
| Patient Requests        | `app/Http/Requests/{Store,Update,Delete}PatientRequest.php`                          |
| Patient Index           | `resources/js/pages/patient/index.tsx`                                               |
| Patient Create          | `resources/js/pages/patient/create.tsx`                                              |
| Patient Profile         | `resources/js/pages/patient/show.tsx`                                                |
| Patient Edit            | `resources/js/pages/patient/edit.tsx`                                                |
| Patient Types           | `resources/js/types/patient.ts`                                                      |
| Patient Allergies       | `app/Models/PatientAllergy.php`, `app/Http/Controllers/PatientAllergyController.php` |
| PatientVisit Model      | `app/Models/PatientVisit.php`                                                        |
| PatientVisit Controller | `app/Http/Controllers/PatientVisitController.php`                                    |
| Visit Payer Model       | `app/Models/VisitPayer.php`                                                          |
| PayerType Enum          | `app/Enums/PayerType.php`                                                            |
| VisitType Enum          | `app/Enums/VisitType.php`                                                            |
| VisitStatus Enum        | `app/Enums/VisitStatus.php`                                                          |
| Routes                  | `routes/web.php`                                                                     |
