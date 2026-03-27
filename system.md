# System Documentation: Mini-Hospital v2

## Overview

Mini-Hospital v2 is a multi-tenant hospital management system focused on outpatient (OPD) operations. Built with Laravel 12, Inertia.js v2, and React 19, it provides a complete clinical workflow from patient registration through billing.

**Architecture:**

- Multi-tenant SaaS with branch isolation
- Outpatient (OPD) focused clinical workflow
- Action-based business logic pattern
- Event-driven charge generation (planned)

---

## Module Status Legend

| Icon | Status                     |
| ---- | -------------------------- |
| ✅   | Implemented and functional |
| 🟡   | Partially implemented      |
| ❌   | Not implemented            |

---

## 1. Authentication & User Management ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/User.php`

### Models

| Model    | Table     | Description                       |
| -------- | --------- | --------------------------------- |
| `User`   | `users`   | System users with authentication  |
| `Tenant` | `tenants` | Organization/workspace            |
| `Staff`  | `staff`   | Employees linked to user accounts |

### Actions

| Action                                    | Purpose                   |
| ----------------------------------------- | ------------------------- |
| `CreateUser`                              | Create new user account   |
| `UpdateUser`                              | Update user profile       |
| `DeleteUser`                              | Soft delete user          |
| `CreateUserPassword`                      | Set initial password      |
| `UpdateUserPassword`                      | Change password           |
| `CreateUserEmailVerificationNotification` | Send verification email   |
| `CreateUserEmailResetNotification`        | Send password reset email |

### Features

- Login/logout with session management
- Password reset flow
- Email verification
- Two-factor authentication
- User profile management
- User CRUD for administrators

### Controllers

- `SessionController` - Authentication sessions
- `UserController` - User CRUD
- `UserProfileController` - Profile management
- `UserPasswordController` - Password changes
- `UserEmailVerificationController` - Email verification
- `UserTwoFactorAuthenticationController` - 2FA management

---

## 2. Roles & Permissions ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model        | Table         | Description                      |
| ------------ | ------------- | -------------------------------- |
| `Role`       | `roles`       | User roles (uses Spatie)         |
| `Permission` | `permissions` | System permissions (uses Spatie) |

### Actions

| Action       | Purpose             |
| ------------ | ------------------- |
| `CreateRole` | Create new role     |
| `UpdateRole` | Update role details |
| `DeleteRole` | Remove role         |

### Features

- Role CRUD operations
- Permission assignment to roles
- Role-based access control (RBAC)
- Spatie Permission package integration

### Key Tables (Spatie)

- `model_has_roles` - User-role assignments
- `model_has_permissions` - Direct permission assignments
- `role_has_permissions` - Role-permission mappings

---

## 3. Multi-Tenant & Branch Management ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model                 | Table                   | Description                 |
| --------------------- | ----------------------- | --------------------------- |
| `Tenant`              | `tenants`               | Organization/workspace      |
| `FacilityBranch`      | `facility_branches`     | Hospital branches/locations |
| `SubscriptionPackage` | `subscription_packages` | SaaS subscription tiers     |
| `TenantSubscription`  | `tenant_subscriptions`  | Active subscriptions        |

### Actions

| Action                          | Purpose                                 |
| ------------------------------- | --------------------------------------- |
| `RegisterWorkspace`             | Register new tenant                     |
| `CreateFacilityBranch`          | Add new branch                          |
| `UpdateFacilityBranch`          | Update branch details                   |
| `DeleteFacilityBranch`          | Remove branch                           |
| `StartTenantSubscription`       | Activate subscription                   |
| `CreateOnboardingPrimaryBranch` | Setup primary branch during onboarding  |
| `UpdateOnboardingProfile`       | Update tenant profile during onboarding |
| `EnsureTenantStaffPositions`    | Initialize default staff positions      |

### Features

- Tenant registration and management
- Branch CRUD operations
- Branch switching (operational context)
- Subscription management
- Onboarding wizard (partial)

### Controllers

- `FacilityBranchController` - Branch management
- `FacilitySwitcherController` - Tenant context switching
- `BranchSwitcherController` - Branch context switching
- `OnboardingController` - Onboarding workflow
- `SubscriptionActivationController` - Subscription management
- `WorkspaceRegistrationController` - Tenant registration

### Traits

- `BelongsToTenant` - Global tenant scope
- `BranchContext` - Runtime branch context

---

## 4. Foundation Data ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Sub-Modules

#### Countries

| Component  | Details                          |
| ---------- | -------------------------------- |
| Model      | `Country` (table: `countries`)   |
| Actions    | `UpdateCountry`, `DeleteCountry` |
| Controller | `CountryController`              |
| Features   | Reference data, CRUD for admin   |

#### Currencies

| Component  | Details                                          |
| ---------- | ------------------------------------------------ |
| Model      | `Currency` (table: `currencies`)                 |
| Actions    | `CreateUnit`, `UpdateCurrency`, `DeleteCurrency` |
| Controller | `CurrencyController`                             |
| Features   | Currency management, African currencies seeded   |

#### Allergens

| Component  | Details                                                |
| ---------- | ------------------------------------------------------ |
| Model      | `Allergen` (table: `allergens`)                        |
| Actions    | `UpdateAllergen`, `DeleteAllergen`                     |
| Controller | `AllergenController`                                   |
| Features   | Allergen master list (medication, food, environmental) |

#### Units

| Component  | Details                                  |
| ---------- | ---------------------------------------- |
| Model      | `Unit` (table: `units`)                  |
| Actions    | `CreateUnit`, `UpdateUnit`, `DeleteUnit` |
| Controller | `UnitController`                         |
| Features   | Measurement units for lab results        |

#### Addresses

| Component  | Details                          |
| ---------- | -------------------------------- |
| Model      | `Address` (table: `addresses`)   |
| Actions    | `UpdateAddress`, `DeleteAddress` |
| Controller | `AddressController`              |
| Features   | Reusable address management      |

#### Subscription Packages

| Component  | Details                                                                               |
| ---------- | ------------------------------------------------------------------------------------- |
| Model      | `SubscriptionPackage` (table: `subscription_packages`)                                |
| Actions    | `CreateSubscriptionPackage`, `UpdateSubscriptionPackage`, `DeleteSubscriptionPackage` |
| Controller | `SubscriptionPackageController`                                                       |
| Features   | SaaS tier management (Starter, Standard, Premium)                                     |

---

## 5. Administration ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Sub-Modules

#### Departments

| Component  | Details                                                    |
| ---------- | ---------------------------------------------------------- |
| Model      | `Department` (table: `departments`)                        |
| Actions    | `CreateDepartment`, `UpdateDepartment`, `DeleteDepartment` |
| Controller | `DepartmentController`                                     |
| Features   | Hospital department management                             |

#### Staff Positions

| Component  | Details                                                             |
| ---------- | ------------------------------------------------------------------- |
| Model      | `StaffPosition` (table: `staff_positions`)                          |
| Actions    | `CreateStaffPosition`, `UpdateStaffPosition`, `DeleteStaffPosition` |
| Controller | `StaffPositionController`                                           |
| Features   | Job positions (Doctor, Nurse, Lab Tech, etc.)                       |

#### Staff

| Component  | Details                                      |
| ---------- | -------------------------------------------- |
| Model      | `Staff` (table: `staff`, `staff_branches`)   |
| Actions    | `CreateStaff`, `UpdateStaff`, `DeleteStaff`  |
| Controller | `StaffController`                            |
| Features   | Employee management, multi-branch assignment |

#### Clinics

| Component  | Details                                        |
| ---------- | ---------------------------------------------- |
| Model      | `Clinic` (table: `clinics`)                    |
| Actions    | `CreateClinic`, `UpdateClinic`, `DeleteClinic` |
| Controller | `ClinicController`                             |
| Features   | Clinic/room management within branches         |

#### Facility Services

| Component  | Details                                                                   |
| ---------- | ------------------------------------------------------------------------- |
| Model      | `FacilityService` (table: `facility_services`)                            |
| Actions    | `CreateFacilityService`, `UpdateFacilityService`, `DeleteFacilityService` |
| Controller | `FacilityServiceController`                                               |
| Features   | Service catalog (procedures, tests) with pricing                          |

#### Drugs

| Component  | Details                                  |
| ---------- | ---------------------------------------- |
| Model      | `Drug` (table: `drugs`)                  |
| Actions    | `CreateDrug`, `UpdateDrug`, `DeleteDrug` |
| Controller | `DrugController`                         |
| Features   | Medication master list                   |

### Enums Used

| Enum                      | Values                                |
| ------------------------- | ------------------------------------- |
| `FacilityServiceCategory` | Lab, Imaging, Procedure, Consultation |
| `DrugCategory`            | `DrugDosageForm`                      |
| `StaffType`               | Doctor, Nurse, Lab Technician, etc.   |

---

## 6. Insurance Management ✅ (Master Data) / ❌ (Workflow)

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model                            | Table                                | Description                  |
| -------------------------------- | ------------------------------------ | ---------------------------- |
| `InsuranceCompany`               | `insurance_companies`                | Insurance providers          |
| `InsurancePackage`               | `insurance_packages`                 | Insurance plans/packages     |
| `InsurancePackagePrice`          | `insurance_package_prices`           | Pricing per billable item    |
| `InsuranceCompanyInvoice`        | `insurance_company_invoices`         | Batched invoices to insurers |
| `InsuranceCompanyInvoicePayment` | `insurance_company_invoice_payments` | Payments from insurers       |

### Actions

| Action                   | Purpose                 |
| ------------------------ | ----------------------- |
| `CreateInsuranceCompany` | Add insurance provider  |
| `UpdateInsuranceCompany` | Update provider details |
| `DeleteInsuranceCompany` | Remove provider         |
| `CreateInsurancePackage` | Add insurance package   |
| `UpdateInsurancePackage` | Update package details  |
| `DeleteInsurancePackage` | Remove package          |

### Features

- ✅ Insurance company CRUD
- ✅ Insurance package management
- ✅ Package pricing (polymorphic: services, drugs, tests, imaging)
- ❌ Claims workflow (not implemented)
- ❌ Invoice generation workflow (not implemented)

### Key Relationships

- Insurance packages belong to companies
- Package prices link to billable items via `billable_type`/`billable_id`
- Invoices belong to companies and branches

---

## 7. Patient Management ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model            | Table               | Description           |
| ---------------- | ------------------- | --------------------- |
| `Patient`        | `patients`          | Patient records       |
| `PatientAllergy` | `patient_allergies` | Patient allergy links |
| `Allergen`       | `allergens`         | Allergen master list  |

### Actions

| Action                         | Purpose                               |
| ------------------------------ | ------------------------------------- |
| `UpdatePatient`                | Update patient details                |
| `DeletePatient`                | Soft delete patient                   |
| `RegisterPatientAndStartVisit` | Register new patient and create visit |

### Features

- Patient CRUD with MRN generation
- Returning patient flow
- Allergy management (CRUD)
- Patient registration integrated with visit creation
- Demographics capture (DOB, gender, blood group, marital status, religion)
- Kin/next-of-kin information

### Enums Used

| Enum              | Values                                   |
| ----------------- | ---------------------------------------- |
| `Gender`          | Male, Female, Other                      |
| `BloodGroup`      | A+, A-, B+, B-, AB+, AB-, O+, O-         |
| `MaritalStatus`   | Single, Married, Divorced, Widowed       |
| `AllergySeverity` | Mild, Moderate, Severe, Life-threatening |
| `AllergyReaction` | Rash, Anaphylaxis, etc.                  |
| `KinRelationship` | Spouse, Parent, Sibling, Child, etc.     |

---

## 8. Scheduling & Appointments ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model                     | Table                        | Description                   |
| ------------------------- | ---------------------------- | ----------------------------- |
| `Appointment`             | `appointments`               | Patient appointments          |
| `AppointmentCategory`     | `appointment_categories`     | Appointment types             |
| `AppointmentMode`         | `appointment_modes`          | In-person, Telemedicine, etc. |
| `DoctorSchedule`          | `doctor_schedules`           | Weekly doctor availability    |
| `DoctorScheduleException` | `doctor_schedule_exceptions` | Holidays, one-off changes     |

### Actions

| Action                           | Purpose                          |
| -------------------------------- | -------------------------------- |
| `CreateDoctorSchedule`           | Define doctor's weekly schedule  |
| `UpdateDoctorSchedule`           | Modify schedule                  |
| `DeleteDoctorSchedule`           | Remove schedule                  |
| `CreateDoctorScheduleException`  | Add exception (holiday, etc.)    |
| `UpdateDoctorScheduleException`  | Modify exception                 |
| `DeleteDoctorScheduleException`  | Remove exception                 |
| `UpdateAppointment`              | Update appointment details       |
| `MarkAppointmentNoShow`          | Mark patient no-show             |
| `RescheduleAppointment`          | Reschedule to new time           |
| `SyncAppointmentStatusFromVisit` | Update status when visit changes |

### Features

- Appointment creation and editing
- Confirmation workflow
- Cancellation handling
- No-show tracking
- Check-in to visit conversion
- Doctor schedule management (weekly slots)
- Schedule exceptions (holidays, one-off changes)
- Queue page for waiting patients
- Appointment categories and modes

### Enums Used

| Enum                    | Values                                                        |
| ----------------------- | ------------------------------------------------------------- |
| `AppointmentStatus`     | Scheduled, Confirmed, CheckedIn, Completed, Cancelled, NoShow |
| `ScheduleDay`           | Monday - Sunday                                               |
| `ScheduleExceptionType` | Holiday, OneOff, Recurring                                    |

### Key Relationships

- Appointments belong to patients, doctors, clinics, branches
- Appointments optionally link to visits
- Schedules belong to doctors and clinics
- Exceptions belong to schedules

---

## 9. Patient Visits ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model                 | Table                    | Description                         |
| --------------------- | ------------------------ | ----------------------------------- |
| `PatientVisit`        | `patient_visits`         | Visit records                       |
| `VisitPayer`          | `visit_payers`           | Payer snapshot per visit            |
| `VisitBilling`        | `visit_billings`         | Billing header (newly added)        |
| `VisitCharge`         | `visit_charges`          | Billable charge lines (newly added) |
| `VisitBillingPayment` | `visit_billing_payments` | Payment records (newly added)       |

### Actions

| Action                           | Purpose                       |
| -------------------------------- | ----------------------------- |
| `StartPatientVisit`              | Create new visit              |
| `RegisterPatientAndStartVisit`   | Combined registration + visit |
| `TransitionPatientVisitStatus`   | Move visit through workflow   |
| `EnsureVisitBilling`             | Create billing header         |
| `UpsertVisitCharge`              | Add/update charge             |
| `ResolveVisitChargeAmount`       | Calculate charge amount       |
| `RecalculateVisitBilling`        | Recalculate totals            |
| `RecordVisitPayment`             | Log payment                   |
| `SyncFacilityServiceOrderCharge` | Sync facility service charge  |
| `SyncLabRequestCharge`           | Sync lab charge               |

### Features

- Visit creation (new and returning patients)
- Visit status transitions
    - `registered` → `in_progress` → `awaiting_payment` → `completed`
- Visit payer snapshot (cash/insurance)
- Visit billing header (auto-created)
- Visit charges with polymorphic sources
- Payment recording
- Visit completion assessment rules
- Visit list/detail workspace

### Visit Status Flow

```
registered → in_progress → awaiting_payment → completed
                 ↓                ↓
              triaged        partially_paid
                              fully_paid
```

### Enums Used

| Enum                | Values                                                                          |
| ------------------- | ------------------------------------------------------------------------------- |
| `VisitStatus`       | Registered, InProgress, Triage, AwaitingPayment, Completed                      |
| `VisitType`         | New, Returning                                                                  |
| `PayerType`         | Cash, Insurance, Sponsor                                                        |
| `BillingStatus`     | Pending, PartialPaid, FullyPaid, InsurancePending, Waived, Refunded, WrittenOff |
| `VisitChargeStatus` | Active, Cancelled, Refunded                                                     |

---

## 10. Triage & Vital Signs ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model          | Table            | Description             |
| -------------- | ---------------- | ----------------------- |
| `TriageRecord` | `triage_records` | Triage assessments      |
| `VitalSign`    | `vital_signs`    | Vital sign measurements |

### Actions

| Action               | Purpose                  |
| -------------------- | ------------------------ |
| `CreateTriageRecord` | Create triage assessment |
| `CreateVitalSign`    | Record vital signs       |

### Features

- Triage assessment (grade, attendance type, conscious level, mobility)
- Chief complaint capture
- Assigned clinic based on triage
- Vital sign capture:
    - Temperature
    - Pulse/Heart rate
    - Blood pressure (systolic/diastolic)
    - O2 saturation
    - Blood glucose
    - Pain score
    - Height, Weight
    - BMI (calculated)
    - Respiratory rate

### Enums Used

| Enum             | Values                                                  |
| ---------------- | ------------------------------------------------------- |
| `TriageGrade`    | Resuscitation, Emergency, Urgent, LessUrgent, NonUrgent |
| `AttendanceType` | WalkIn, Appointment, Referral, Emergency                |
| `ConsciousLevel` | Alert, Voice, Pain, Unresponsive                        |
| `MobilityStatus` | Ambulant, Wheelchair, Bedridden                         |

### Key Relationships

- Triage record belongs to visit and nurse (staff)
- Vital signs belong to triage record
- Vital signs recorded by staff

---

## 11. Consultation ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model          | Table           | Description                 |
| -------------- | --------------- | --------------------------- |
| `Consultation` | `consultations` | Clinical consultation notes |

### Actions

| Action               | Purpose                   |
| -------------------- | ------------------------- |
| `UpdateConsultation` | Update consultation notes |

### Features

- SOAP format documentation:
    - **Subjective:** Chief complaint, HPI, ROS, PMH, family/social history
    - **Objective:** Physical examination findings
    - **Assessment:** Primary and secondary diagnoses
    - **Plan:** Treatment plan, follow-up instructions
- Primary/secondary diagnoses
- Outcome tracking (discharged, admitted, referred, etc.)
- Follow-up instructions
- Referral details
- Consultation completion workflow

### Enums Used

| Enum                  | Values                                         |
| --------------------- | ---------------------------------------------- |
| `ConsultationOutcome` | Discharged, Admitted, Referred, FollowUp, Died |

### Key Relationships

- Consultation belongs to visit and doctor (staff)
- Consultation has many:
    - Lab requests
    - Imaging requests
    - Prescriptions
    - Facility service orders

---

## 12. Consultation Orders ✅

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Lab Requests ✅

#### Models

| Model            | Table               | Description           |
| ---------------- | ------------------- | --------------------- |
| `LabRequest`     | `lab_requests`      | Lab request header    |
| `LabRequestItem` | `lab_request_items` | Individual test items |
| `LabTestCatalog` | `lab_test_catalogs` | Test catalog master   |

#### Actions

| Action                 | Purpose             |
| ---------------------- | ------------------- |
| `CreateLabRequest`     | Create lab request  |
| `SyncLabRequestCharge` | Sync billing charge |

#### Features

- Order lab tests from consultation
- Lab test catalog management (CRUD)
- Request items with status tracking
- Billing status per item
- Priority levels

### Imaging Requests ✅

#### Models

| Model            | Table              | Description          |
| ---------------- | ------------------ | -------------------- |
| `ImagingRequest` | `imaging_requests` | Imaging study orders |

#### Actions

| Action                 | Purpose              |
| ---------------------- | -------------------- |
| `CreateImagingRequest` | Create imaging order |

#### Features

- Order imaging studies (X-ray, CT, MRI, etc.)
- Modality selection
- Body part and laterality
- Priority levels
- Clinical history and indication
- Contrast and pregnancy status flags

### Prescriptions ✅

#### Models

| Model              | Table                | Description                 |
| ------------------ | -------------------- | --------------------------- |
| `Prescription`     | `prescriptions`      | Prescription header         |
| `PrescriptionItem` | `prescription_items` | Individual medication items |

#### Actions

| Action               | Purpose             |
| -------------------- | ------------------- |
| `CreatePrescription` | Create prescription |

#### Features

- Prescribe medications
- Dosage, frequency, route, duration
- External pharmacy flag
- Prescription status tracking

### Facility Service Orders ✅

#### Models

| Model                  | Table                     | Description              |
| ---------------------- | ------------------------- | ------------------------ |
| `FacilityServiceOrder` | `facility_service_orders` | Procedure/service orders |

#### Actions

| Action                              | Purpose              |
| ----------------------------------- | -------------------- |
| `CreateFacilityServiceOrder`        | Create service order |
| `DeletePendingFacilityServiceOrder` | Cancel pending order |
| `SyncFacilityServiceOrderCharge`    | Sync billing charge  |

#### Features

- Order facility services (procedures, tests)
- Quantity and price capture
- Pending/completed status workflow

### Enums Used

| Enum                         | Values                                    |
| ---------------------------- | ----------------------------------------- |
| `LabRequestStatus`           | Pending, InProgress, Completed, Cancelled |
| `LabRequestItemStatus`       | Pending, Received, InProgress, Completed  |
| `Priority`                   | Routine, Urgent, STAT                     |
| `ImagingModality`            | XRay, CT, MRI, Ultrasound, Mammography    |
| `ImagingLaterality`          | Left, Right, Bilateral, None              |
| `PrescriptionStatus`         | Draft, Active, Completed, Cancelled       |
| `PrescriptionItemStatus`     | Active, Dispensed, Cancelled              |
| `FacilityServiceOrderStatus` | Pending, Completed, Cancelled             |

---

## 13. Laboratory Workflow 🟡

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model                      | Table                          | Description                         |
| -------------------------- | ------------------------------ | ----------------------------------- |
| `LabTestCatalog`           | `lab_test_catalogs`            | Test catalog master                 |
| `LabTestCategory`          | `lab_test_categories`          | Test categories                     |
| `SpecimenType`             | `specimen_types`               | Specimen types (blood, urine, etc.) |
| `LabResultType`            | `lab_result_types`             | Result value types                  |
| `LabRequestItem`           | `lab_request_items`            | Individual test items               |
| `LabRequestItemConsumable` | `lab_request_item_consumables` | Consumables used                    |
| `LabResultEntry`           | `lab_result_entries`           | Result entry header                 |
| `LabResultValue`           | `lab_result_values`            | Individual result values            |
| `LabTestResultParameter`   | `lab_test_result_parameters`   | Test parameters/fields              |
| `LabTestResultOption`      | `lab_test_result_options`      | Predefined result options           |

### Actions

| Action                            | Purpose                |
| --------------------------------- | ---------------------- |
| `CreateLabTestCatalog`            | Add test to catalog    |
| `UpdateLabTestCatalog`            | Update test details    |
| `DeleteLabTestCatalog`            | Remove test            |
| `SyncLabTestCatalogConfiguration` | Sync test parameters   |
| `StoreLabResultEntry`             | Save result entry      |
| `ReviewLabResultEntry`            | Review/approve results |
| `ReceiveLabRequestItem`           | Mark item as received  |
| `SyncLabRequestProgress`          | Update progress        |
| `RecordLabRequestItemConsumable`  | Track consumables      |
| `DeleteLabRequestItemConsumable`  | Remove consumable      |
| `SyncLabRequestItemActualCost`    | Update actual cost     |

### Features

- ✅ Lab test catalog CRUD with parameters and reference ranges
- ✅ Specimen type management
- ✅ Result type management
- ✅ Lab dashboard and worklist
- ✅ Request item receive workflow
- ✅ Result entry (multi-parameter)
- ✅ Result review/approval workflow
- ✅ Consumable tracking (actual cost)
- ❌ Sample barcoding (not implemented)
- ❌ LIS integration (not implemented)

---

## 14. Imaging Workflow 🟡

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model            | Table              | Description          |
| ---------------- | ------------------ | -------------------- |
| `ImagingRequest` | `imaging_requests` | Imaging study orders |

### Features

- ✅ Imaging request creation from consultation
- ✅ Modality, body part, laterality, priority
- ✅ Clinical history and indication
- ✅ Contrast and pregnancy status
- ❌ Imaging scheduling (not implemented)
- ❌ Study capture (not implemented)
- ❌ Radiology reporting (not implemented)

### Enums Used

| Enum                   | Values                                               |
| ---------------------- | ---------------------------------------------------- |
| `ImagingRequestStatus` | Pending, Scheduled, InProgress, Completed, Cancelled |
| `ImagingModality`      | XRay, CT, MRI, Ultrasound, Mammography               |
| `ImagingLaterality`    | Left, Right, Bilateral, None                         |
| `ImagingPriority`      | Routine, Urgent, STAT                                |
| `PregnancyStatus`      | NotPregnant, Pregnant, PossiblyPregnant, Unknown     |

---

## 15. Pharmacy 🟡

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`

### Models

| Model              | Table                | Description                 |
| ------------------ | -------------------- | --------------------------- |
| `Drug`             | `drugs`              | Medication master list      |
| `Prescription`     | `prescriptions`      | Prescription header         |
| `PrescriptionItem` | `prescription_items` | Individual medication items |

### Actions

| Action               | Purpose                     |
| -------------------- | --------------------------- |
| `CreateDrug`         | Add medication to formulary |
| `UpdateDrug`         | Update medication details   |
| `DeleteDrug`         | Remove medication           |
| `CreatePrescription` | Create prescription         |

### Features

- ✅ Drug master list (CRUD)
- ✅ Prescription creation from consultation
- ✅ Prescription items with dosage/frequency/route/duration
- ❌ Dispensing records (not implemented)
- ❌ Inventory management (not implemented)
- ❌ Stock tracking (not implemented)
- ❌ Pharmacy workflow (not implemented)

### Enums Used

| Enum                     | Values                                         |
| ------------------------ | ---------------------------------------------- |
| `DrugCategory`           | Antibiotic, Analgesic, Antihypertensive, etc.  |
| `DrugDosageForm`         | Tablet, Capsule, Syrup, Injection, Cream, etc. |
| `PrescriptionStatus`     | Draft, Active, Completed, Cancelled            |
| `PrescriptionItemStatus` | Active, Dispensed, Cancelled                   |

---

## 16. Billing & Payments 🟡

**Module Path:** `app/Actions/`, `app/Http/Controllers/`, `app/Models/`, `app/Services/Billing/`

### Models

| Model                            | Table                                | Description                         |
| -------------------------------- | ------------------------------------ | ----------------------------------- |
| `VisitBilling`                   | `visit_billings`                     | Visit billing header (newly added)  |
| `VisitCharge`                    | `visit_charges`                      | Billable charge lines (newly added) |
| `VisitBillingPayment`            | `visit_billing_payments`             | Payment records (newly added)       |
| `InsuranceCompanyInvoice`        | `insurance_company_invoices`         | Insurer invoice batch               |
| `InsuranceCompanyInvoicePayment` | `insurance_company_invoice_payments` | Insurer payments                    |

### Services

| Service          | Methods                                                                        | Description             |
| ---------------- | ------------------------------------------------------------------------------ | ----------------------- |
| `BillingService` | `ensureVisitBilling`, `addChargeToVisit`, `recordPayment`, `getBillingSummary` | Core billing operations |

### Actions

| Action                           | Purpose                      |
| -------------------------------- | ---------------------------- |
| `EnsureVisitBilling`             | Create billing header        |
| `UpsertVisitCharge`              | Add/update charge            |
| `ResolveVisitChargeAmount`       | Calculate charge amount      |
| `RecalculateVisitBilling`        | Recalculate totals           |
| `RecordVisitPayment`             | Log payment                  |
| `SyncFacilityServiceOrderCharge` | Sync facility service charge |
| `SyncLabRequestCharge`           | Sync lab charge              |

### Features

- ✅ Visit billing header (auto-created with visit)
- ✅ Visit charges (polymorphic sources)
- ✅ Payment recording
- ✅ Billing status tracking
- ✅ BillingService for core operations
- ❌ Full billing UI (not implemented)
- ❌ Invoice generation (not implemented)
- ❌ Cashier workflow (not implemented)
- ❌ Payment-gating before care (not implemented)
- ❌ Claims adjudication (not implemented)

### Billing Flow

```
Visit Created
    ↓
VisitBilling (auto-created)
    ↓
Orders Create Charges (polymorphic)
    ↓
Charges Update Gross Amount
    ↓
Payments Update Paid Amount
    ↓
Balance = Gross - Paid - Discount
```

### Enums Used

| Enum                | Values                                                                          |
| ------------------- | ------------------------------------------------------------------------------- |
| `BillingStatus`     | Pending, PartialPaid, FullyPaid, InsurancePending, Waived, Refunded, WrittenOff |
| `PayerType`         | Cash, Insurance, Sponsor                                                        |
| `VisitChargeStatus` | Active, Cancelled, Refunded                                                     |
| `BillableItemType`  | LabTest, ImagingStudy, Procedure, Consultation, Drug                            |

---

## 17. SaaS Onboarding 🟡

**Module Path:** `app/Actions/`, `app/Http/Controllers/`

### Actions

| Action                          | Purpose                      |
| ------------------------------- | ---------------------------- |
| `RegisterWorkspace`             | Register new tenant          |
| `StartTenantSubscription`       | Activate subscription        |
| `CreateOnboardingPrimaryBranch` | Setup primary branch         |
| `UpdateOnboardingProfile`       | Update tenant profile        |
| `EnsureTenantStaffPositions`    | Initialize default positions |

### Controllers

- `OnboardingController` - Onboarding wizard
- `SubscriptionActivationController` - Subscription management
- `WorkspaceRegistrationController` - Tenant registration

### Features

- ✅ Workspace registration (tenant creation)
- ✅ Onboarding wizard (profile, branch, departments, staff)
- ✅ Subscription activation
- ✅ Mock checkout
- ❌ Self-service tenant signup (not implemented)
- ❌ Subscription billing (not implemented)
- ❌ Tenant subscription lifecycle (not implemented)

---

## 18. Dashboard & Reports ✅ (Basic)

**Module Path:** `app/Http/Controllers/`

### Features

- ✅ Main dashboard page
- ✅ Laboratory dashboard (worklist, counts)
- ❌ Comprehensive reporting (not implemented)
- ❌ Analytics (not implemented)

---

## Cross-Cutting Concerns

| Concern            | Implementation                                                                |
| ------------------ | ----------------------------------------------------------------------------- |
| **Multi-tenancy**  | Global tenant scope (`BelongsToTenant` trait), tenant FKs on all major tables |
| **Branch Context** | `BranchContext` service, active branch middleware                             |
| **Permissions**    | Spatie Permission package, RBAC                                               |
| **Soft Deletes**   | Used on master data and clinical records                                      |
| **Primary Keys**   | UUIDs for distributed compatibility                                           |
| **Audit Fields**   | `created_by`, `updated_by` on most tables                                     |
| **Code Style**     | Laravel Pint, strict types                                                    |

---

## Key Relationships Map

```
Patient
  ├── PatientVisit
  │     ├── VisitPayer (snapshot)
  │     ├── VisitBilling
  │     │     ├── VisitCharge (polymorphic sources)
  │     │     └── VisitBillingPayment
  │     ├── TriageRecord
  │     │     └── VitalSign[]
  │     ├── Consultation
  │     │     ├── LabRequest[]
  │     │     │     └── LabRequestItem[]
  │     │     ├── ImagingRequest[]
  │     │     ├── Prescription[]
  │     │     │     └── PrescriptionItem[]
  │     │     └── FacilityServiceOrder[]
  │     └── Appointment (optional)
  └── PatientAllergy[]
        └── Allergen

Staff
  ├── StaffPosition
  ├── Department
  ├── DoctorSchedule[]
  │     └── DoctorScheduleException[]
  └── FacilityBranch[] (via staff_branches pivot)

FacilityBranch
  ├── Tenant
  ├── Clinic[]
  ├── FacilityService[]
  └── Currency

InsuranceCompany
  └── InsurancePackage[]
        └── InsurancePackagePrice[] (polymorphic)
```

---

## Implementation Gaps & Next Steps

### Critical Gaps

1. **Billing UI** - Backend models exist but no cashier/payment interface
2. **Lab Workflow** - Result entry exists but no specimen tracking or barcoding
3. **Imaging Workflow** - Orders exist but no study capture or reporting
4. **Pharmacy** - Prescriptions exist but no dispensing or inventory
5. **Insurance Claims** - Master data exists but no claims workflow

### Recommended Next Steps

1. Complete billing UI and cashier workflow
2. Wire automatic charge generation from orders
3. Implement visit payment-gating (awaiting_payment status)
4. Build pharmacy dispensing workflow
5. Add insurance claims batch processing

---

## File Reference

### Key Service Files

- `app/Services/Billing/BillingService.php` - Core billing operations

### Key Action Files

- `app/Actions/CreateUser.php` - User creation
- `app/Actions/RegisterPatientAndStartVisit.php` - Patient registration
- `app/Actions/StartPatientVisit.php` - Visit creation
- `app/Actions/CreateTriageRecord.php` - Triage assessment
- `app/Actions/CreateLabRequest.php` - Lab orders
- `app/Actions/CreateImagingRequest.php` - Imaging orders
- `app/Actions/CreatePrescription.php` - Prescriptions
- `app/Actions/CreateFacilityServiceOrder.php` - Service orders

### Key Model Files

- `app/Models/Patient.php`
- `app/Models/PatientVisit.php`
- `app/Models/VisitBilling.php`
- `app/Models/VisitCharge.php`
- `app/Models/Consultation.php`
- `app/Models/LabRequest.php`
- `app/Models/Prescription.php`

### Key Enum Files

- `app/Enums/BillingStatus.php`
- `app/Enums/PayerType.php`
- `app/Enums/VisitChargeStatus.php`
- `app/Enums/VisitStatus.php`

---

_Last Updated: March 26, 2026_
_Document Version: 1.0_
