# Patient Module Implementation Review

**Date:** March 2026

---

## Current Implementation Status

### ✅ Implemented

| Component          | Status      | Notes                                                                                                  |
| ------------------ | ----------- | ------------------------------------------------------------------------------------------------------ |
| Patient Model      | ✅ Complete | Comprehensive fields: demographics, contact, next of kin, insurance, blood group, occupation, religion |
| Patient Controller | ✅ Complete | CRUD: index, create, store, edit, update, destroy                                                      |
| Patient Actions    | ✅ Complete | CreatePatient, UpdatePatient, DeletePatient                                                            |
| Form Requests      | ✅ Complete | StorePatientRequest, UpdatePatientRequest, DeletePatientRequest                                        |
| Frontend Index     | ✅ Complete | Search, pagination, table with MRN, name, phone, gender, payer, insurance                              |
| Frontend Create    | ✅ Complete | Multi-section form (Bio, Contact, Kin, Billing)                                                        |
| Frontend Edit      | ✅ Complete | Same structure as create                                                                               |
| Patient Allergies  | ✅ Complete | Separate model, controller, routes (`patients.allergies` resource)                                     |
| Patient Insurance  | ✅ Complete | Relationship to InsuranceCompany, InsurancePackage                                                     |
| PayerType Enum     | ✅ Complete | Supports `cash` and `insurance`                                                                        |
| Routes             | ✅ Complete | `Route::resource('patients')`                                                                          |

### ❌ Not Implemented

| Component               | Priority | Notes                                                               |
| ----------------------- | -------- | ------------------------------------------------------------------- |
| Patient Search Endpoint | High     | No dedicated search API to check for duplicates before registration |
| Visit/Encounter System  | High     | No patient_visits, triage_records, consultations                    |
| Visit Check-in          | High     | No flow to create a visit and select payer per encounter            |
| Queue/Triage View       | Medium   | No queue page for triaging registered patients                      |
| Billing Flow            | Medium   | No visit_charges, visit_billings, payments                          |
| Patient Visit History   | Medium   | No view of patient's past visits                                    |

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

### Patient Model - ✅ Good

```php
// Fillable fields are comprehensive
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
    'default_payer_type',    // Cash or insurance
    'created_by', 'updated_by',
];
```

### Relationships - ✅ Good

- `country()` - BelongsTo
- `address()` - BelongsTo
- `insurances()` - HasMany (PatientInsurance)
- `primaryInsurance()` - HasOne
- `allergies()` - HasMany
- `activeAllergies()` - HasMany (scoped to active)

### Missing Models

For a complete patient management system, you would need:

1. **PatientVisit** - For encounter tracking
2. **TriageRecord** - For triage data
3. **VitalSign** - For vital signs
4. **Consultation** - For doctor consultations
5. **VisitCharge** - For billing line items
6. **VisitBilling** - For billing header
7. **Payment** - For payment records

---

## Workflow Review

### Documented Flow (from patient.md)

1. **Patient Search First** → Check for duplicates by phone/MRN/name+DOB
2. **New Patient Registration** → Create patient profile only (no billing)
3. **Optional Insurance Capture** → At registration time
4. **Visit Check-in** → Choose payer (cash vs insurance) per visit
5. **Triage and Clinical Flow** → Queue, triage, consultation
6. **Billing and Payment** → Charges, payments, claims

### Actual Implementation

The current implementation covers **Step 2-3 partially**:

- ✅ Patient registration form exists
- ✅ Insurance can be captured at registration
- ✅ Default payer type stored on patient
- ❌ No patient search before registration
- ❌ No visit creation/check-in flow
- ❌ No triage queue
- ❌ No billing flow

---

## Recommendations

### High Priority

1. **Update Patient Create/Edit pages** to match allergen design pattern
2. **Add patient search** before registration to prevent duplicates
3. **Implement Visit model** and check-in flow

### Medium Priority

4. Add queue/triage view for registered patients
5. Add patient visit history view
6. Implement billing and payment flow

### Low Priority

7. Add insurance claim tracking
8. Add patient portal (future)

---

## Code Locations

| Component          | Path                                                                                 |
| ------------------ | ------------------------------------------------------------------------------------ |
| Patient Model      | `app/Models/Patient.php`                                                             |
| Patient Controller | `app/Http/Controllers/PatientController.php`                                         |
| Patient Actions    | `app/Actions/{Create,Update,Delete}Patient.php`                                      |
| Patient Requests   | `app/Http/Requests/{Store,Update,Delete}PatientRequest.php`                          |
| Patient Index      | `resources/js/pages/patient/index.tsx`                                               |
| Patient Create     | `resources/js/pages/patient/create.tsx`                                              |
| Patient Edit       | `resources/js/pages/patient/edit.tsx`                                                |
| Patient Types      | `resources/js/types/patient.ts`                                                      |
| Patient Allergies  | `app/Models/PatientAllergy.php`, `app/Http/Controllers/PatientAllergyController.php` |
| Patient Insurance  | `app/Models/PatientInsurance.php`                                                    |
| PayerType Enum     | `app/Enums/PayerType.php`                                                            |
| Routes             | `routes/web.php` (line 73)                                                           |
