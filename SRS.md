# Software Requirements Specification (SRS)
## Dental Practice Management System

**Document Version**: 1.0  
**Date**: 2024  
**Project**: Chart-Backend (Dental Management System)  
**Platform**: Laravel 12 REST API

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Overview](#system-overview)
3. [Functional Requirements](#functional-requirements)
   - [3.1 Authentication & Authorization](#31-authentication--authorization)
   - [3.2 Patient Management](#32-patient-management)
   - [3.3 Clinical Documentation](#33-clinical-documentation)
   - [3.4 Appointment Scheduling](#34-appointment-scheduling)
   - [3.5 Billing & Invoicing](#35-billing--invoicing)
   - [3.6 Staff Management](#36-staff-management)
   - [3.7 Business Operations](#37-business-operations)
4. [Non-Functional Requirements](#non-functional-requirements)
5. [Data Model](#data-model)
6. [API Specifications](#api-specifications)
7. [Business Logic & Workflows](#business-logic--workflows)
8. [Implementation Guide](#implementation-guide)

---

## Executive Summary

The **Dental Practice Management System** is a comprehensive web-based solution designed to streamline operations for dental clinics and multi-branch dental organizations. The system consolidates:

- Patient demographics and clinical records
- Appointment scheduling and patient flow management
- Treatment planning and clinical documentation
- Billing, invoicing, and payment processing
- Staff management, payroll, and HR operations
- Inventory and supply chain management
- Financial reporting and analytics

**Key Goals**:
- Provide a centralized platform for clinical and business operations
- Support multi-branch clinic networks with independent data isolation
- Enable role-based access control for different staff types
- Track complete patient treatment history from diagnosis to billing
- Generate financial reports and operational metrics

---

## System Overview

### 1.1 Architecture

The system follows a **3-tier REST API architecture**:

```
┌─────────────────────────────────────────┐
│      Client Layer (Web/Mobile App)      │
│    (Consumes REST API JSON endpoints)   │
└─────────────────┬───────────────────────┘
                  │ HTTP/HTTPS
                  ↓
┌─────────────────────────────────────────┐
│      API Layer (Laravel 12)             │
│  Routes → Controllers → Services        │
│  (routes/api.php, app/Http/Controllers/)│
└─────────────────┬───────────────────────┘
                  │ SQL
                  ↓
┌─────────────────────────────────────────┐
│    Data Layer (SQLite / MySQL)          │
│  Models, Migrations, Database Schema    │
│  (app/Models/, database/migrations/)    │
└─────────────────────────────────────────┘
```

**File Structure**:
```
chart-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/        ← Business logic
│   │   └── Middleware/         ← Request filtering
│   ├── Models/                 ← Data models (Eloquent)
│   └── Providers/
├── routes/
│   ├── api.php                 ← API endpoint definitions
│   └── web.php
├── database/
│   ├── migrations/             ← Schema definitions
│   └── seeders/
├── config/                     ← Configuration files
└── .env                        ← Environment variables
```

### 1.2 Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | Laravel 12 (PHP 8.2) | Web framework, routing, ORM |
| **API** | RESTful JSON over HTTP/HTTPS | Client communication |
| **Database** | SQLite (default) / MySQL | Data persistence |
| **Authentication** | Laravel Sanctum | API token-based auth |
| **Frontend** | Vue.js / React (decoupled) | User interface |
| **Documentation** | Scribe API Docs | Auto-generated API docs |

---

## Functional Requirements

### 3.1 Authentication & Authorization

#### 3.1.1 User Login

**Description**: Users authenticate using email and password to receive an API token.

**File References**:
- **Controller**: `app/Http/Controllers/AuthController.php` (lines 69-95)
- **Route**: `routes/api.php` (line 14: `POST /login`)
- **Model**: `app/Models/Employee.php` (lines 75-76 in AuthController)

**Flow**:
```
POST /api/login
Body: { "email": "doctor@clinic.com", "password": "secret" }
  ↓
AuthController->login()
  ↓
1. Validate email & password format
   (app/Http/Controllers/AuthController.php:71-74)
  ↓
2. Query Employee table by email
   Employee::with('branch')->where('email', $request->email)->first()
  ↓
3. Hash comparison
   Hash::check($request->password, $employee->password)
  ↓
4. If match:
   - Fetch role permissions from RolePermission table
   - Generate Sanctum token: $employee->createToken()
   - Return token + user profile + permissions
  ↓
Response 200: {
  "token": "1|a1b2c3d4e5f6...",
  "user": {
    "name": "Dr. Smith",
    "email": "doctor@clinic.com",
    "role": "Dentist",
    "permissions": ["appointments.view", "patients.edit"],
    "branchId": 1,
    "branchName": "Downtown Clinic"
  }
}
```

**Database Schema** (Employee table):
```php
// database/migrations/2025_10_12_121923_create_appointments_table.php (lines 11-26)
Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();  ← Login identifier
    $table->string('password');         ← Bcrypt hashed
    $table->string('role');             ← e.g., "Dentist", "Receptionist"
    $table->unsignedBigInteger('role_id');  ← FK to role_permissions
    $table->foreignId('branch_id');     ← Which branch they work at
    $table->timestamps();
});
```

#### 3.1.2 Token-Based Authentication

**Description**: All protected endpoints require a valid Sanctum token in the `Authorization` header.

**File References**:
- **Middleware**: `routes/api.php` (line 17: `middleware('auth:sanctum')`)
- **Guard Configuration**: `config/auth.php` (Laravel default)
- **Model Trait**: `app/Models/Employee.php` (line 13: `use HasApiTokens`)

**Implementation**:
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Protected endpoints here
    Route::apiResource('patients', PatientController::class);
});
```

When a request arrives with header `Authorization: Bearer {token}`:
1. Sanctum middleware validates the token against `personal_access_tokens` table
2. Populates `auth()->user()` with the logged-in Employee
3. Controller accesses user via `auth()->user()` or dependency injection

**Example in Controller**:
```php
// app/Http/Controllers/PatientController.php
public function index(Request $request) {
    $currentEmployee = auth()->user();  // Get authenticated user
    $branchId = $currentEmployee->branch_id;  // Get their branch
}
```

#### 3.1.3 Role-Based Access Control (RBAC)

**Description**: Different user roles (Super Admin, Dentist, Receptionist, HR, etc.) have different permissions.

**File References**:
- **Model**: `app/Models/RolePermission.php`
- **Controller**: `app/Http/Controllers/RolePermissionController.php`
- **Middleware**: `app/Http/Middleware/CheckPermission.php`
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 127-132)

**Database Schema**:
```php
Schema::create('role_permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name');  // e.g., "Dentist", "Receptionist", "Super Admin"
    $table->json('permissions');  // JSON array: ["patients.view", "appointments.create"]
    $table->timestamps();
});
```

**Example Permission JSON**:
```json
{
  "id": 1,
  "name": "Dentist",
  "permissions": [
    "patients.view",
    "patients.edit",
    "appointments.view",
    "appointments.create",
    "appointments.complete",
    "invoices.view"
  ]
}
```

**Permission Check in Controller**:
```php
// app/Models/Employee.php (lines 74-83)
public function hasPermission(string $permission): bool {
    $rolePermission = RolePermission::where('name', $this->role)->first();
    if (!$rolePermission) {
        return false;
    }
    return in_array($permission, $rolePermission->permissions);
}
```

**Manual Permission Enforcement** (in controllers):
```php
public function store(Request $request) {
    if (!auth()->user()->hasPermission('patients.create')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    // Create patient
}
```

**Future Middleware Enforcement**:
```php
// app/Http/Middleware/CheckPermission.php
public function handle($request, Closure $next) {
    $requiredPermission = $this->getRequiredPermission($request);
    if (!auth()->user()->hasPermission($requiredPermission)) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    return $next($request);
}
```

---

### 3.2 Patient Management

#### 3.2.1 Patient CRUD Operations

**Description**: Create, read, update, and delete patient records with demographic information.

**File References**:
- **Controller**: `app/Http/Controllers/PatientController.php` (lines 54-85+)
- **Model**: `app/Models/Patient.php`
- **Routes**: `routes/api.php` (lines 24-25)
- **Database Schema**: `database/migrations/2025_10_12_121921_create_branches_table.php` (lines 25-41)

**Patient Data Model**:
```php
// app/Models/Patient.php
class Patient extends Model {
    protected $fillable = [
        'first_name',      // Required
        'last_name',       // Required
        'email',           // Required, unique
        'phone',           // Optional
        'address',         // Optional
        'gender',          // Enum: Male, Female, Other
        'date_of_birth',   // Required, date format
        'last_visit',      // Optional, date of last appointment
        'status',          // Enum: Active, Inactive (default: Active)
        'branch_id',       // Required, FK to branches
        'history',         // Optional, medical/dental history text
        'occlusal_records',// Optional, JSON object (bite info)
        'avatar',          // Optional, image URL
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_visit' => 'date',
        'occlusal_records' => 'array',  // Auto-cast JSON
    ];
}
```

**Database Schema**:
```php
// database/migrations/2025_10_12_121921_create_branches_table.php:25-41
Schema::create('patients', function (Blueprint $table) {
    $table->id();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('avatar')->nullable();
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('address')->nullable();
    $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
    $table->date('date_of_birth');
    $table->date('last_visit')->nullable();
    $table->enum('status', ['Active', 'Inactive'])->default('Active');
    $table->foreignId('branch_id')->constrained('branches');
    $table->text('history')->nullable();
    $table->json('occlusal_records')->nullable();  // Bite/occlusion info
    $table->timestamps();  // created_at, updated_at
});
```

**API Endpoints**:

**1. Create Patient**
```
POST /api/patients
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "256700000000",
  "date_of_birth": "1985-05-20",
  "address": "123 Main St",
  "gender": "Male",
  "branch_id": 1,
  "status": "Active",
  "history": "No known allergies"
}

Response 201: {
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  ...
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-15T10:30:00Z"
}
```

**2. List Patients**
```
GET /api/patients?branch_id=1&status=Active&gender=Male
Authorization: Bearer {token}

Response 200: {
  "message": "Patients retrieved successfully.",
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "256700000000",
      "branch_id": 1,
      "status": "Active",
      "dentalRecords": [...],
      "treatmentPlans": [...],
      "images": [...],
      "periodontalRecords": [...],
      ...
    }
  ]
}
```

**Controller Implementation** (app/Http/Controllers/PatientController.php:54-85):
```php
public function index(Request $request) {
    $query = Patient::with([
        'dentalRecords',
        'treatmentPlans',
        'images',
        'periodontalRecords',
        'softTissueRecords',
        'orthodonticRecords',
        'branch'
    ]);

    // Optional filtering
    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
    }

    $patients = $query->orderBy('first_name')->get();

    return response()->json([
        'message' => 'Patients retrieved successfully.',
        'data' => $patients
    ]);
}
```

**3. Get Single Patient**
```
GET /api/patients/{id}
Authorization: Bearer {token}

Response 200: {
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "256700000000",
  "dentalRecords": [
    {
      "id": 1,
      "tooth_id": 11,
      "condition": "Caries",
      "notes": "Class II cavity on distal surface"
    },
    ...
  ],
  "treatmentPlans": [...],
  "invoices": [...],
  "appointments": [...]
}
```

**4. Update Patient**
```
PUT /api/patients/{id}
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "phone": "256700000001",
  "status": "Inactive"
}

Response 200: {
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "phone": "256700000001",
  "status": "Inactive",
  ...
}
```

**5. Delete Patient**
```
DELETE /api/patients/{id}
Authorization: Bearer {token}

Response 204: (No Content)
```

#### 3.2.2 Patient Relationships

**Description**: Each patient is linked to multiple related records.

**File References**:
- **Model Relationships**: `app/Models/Patient.php` (lines 40-88)

**Relationships**:
```php
// app/Models/Patient.php
public function branch(): BelongsTo {
    return $this->belongsTo(Branch::class);
}

public function dentalRecords(): HasMany {
    return $this->hasMany(DentalRecord::class);
}

public function treatmentPlans(): HasMany {
    return $this->hasMany(TreatmentPlan::class);
}

public function images(): HasMany {
    return $this->hasMany(PatientImage::class);
}

public function periodontalRecords(): HasMany {
    return $this->hasMany(PeriodontalRecord::class);
}

public function softTissueRecords(): HasMany {
    return $this->hasMany(SoftTissueRecord::class);
}

public function orthodonticRecords(): HasMany {
    return $this->hasMany(OrthodonticRecord::class);
}

public function appointments(): HasMany {
    return $this->hasMany(Appointment::class);
}

public function invoices(): HasMany {
    return $this->hasMany(Invoice::class);
}

public function labOrders(): HasMany {
    return $this->hasMany(LaboratoryOrder::class);
}
```

**Database Foreign Keys**:
```
Patient (id=1)
  ├── DentalRecord (patient_id=1) [tooth_id 1-32]
  ├── PeriodontalRecord (patient_id=1)
  ├── SoftTissueRecord (patient_id=1)
  ├── OrthodonticRecord (patient_id=1)
  ├── PatientImage (patient_id=1)
  ├── TreatmentPlan (patient_id=1)
  ├── Appointment (patient_id=1)
  ├── Invoice (patient_id=1)
  └── LaboratoryOrder (patient_id=1)
```

---

### 3.3 Clinical Documentation

#### 3.3.1 Dental Records (Odontogram)

**Description**: Document tooth-by-tooth clinical findings using standard dental tooth numbering (1-32 per FDI system).

**File References**:
- **Model**: `app/Models/DentalRecord.php`
- **Controller Methods**: `app/Http/Controllers/PatientController.php` (lines 34-37)
- **Routes**: `routes/api.php` (lines 33-37)
- **Database Schema**: `database/migrations/2025_10_12_121921_create_branches_table.php` (lines 43-52)

**Tooth Numbering System (FDI)**:
```
Upper Right (11-18)  |  Upper Left (21-28)
18 17 16 15 14 13 12 11 | 21 22 23 24 25 26 27 28
----------------------------------------
48 47 46 45 44 43 42 41 | 31 32 33 34 35 36 37 38
Lower Right (41-48)  |  Lower Left (31-38)
```

**Tooth Conditions** (examples):
- Caries (cavity)
- Restored (filling/crown)
- Missing
- Impacted
- Sound (healthy)
- Fractured
- Endodontically treated (root canal)

**Database Schema**:
```php
// database/migrations/2025_10_12_121921_create_branches_table.php:43-52
Schema::create('dental_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id');  // Link to patient
    $table->integer('tooth_id');      // 1-32 per FDI system
    $table->string('condition');      // "Caries", "Restored", "Missing", etc.
    $table->text('notes')->nullable();// Additional clinical notes
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
});
```

**API Endpoints**:

**1. Get Dental Records for Patient**
```
GET /api/patients/{patientId}/odontogram-records
Authorization: Bearer {token}

Response 200: [
  {
    "id": 1,
    "patient_id": 1,
    "tooth_id": 11,
    "condition": "Caries",
    "notes": "Class II cavity on distal surface",
    "created_at": "2024-01-15T10:00:00Z"
  },
  {
    "id": 2,
    "patient_id": 1,
    "tooth_id": 21,
    "condition": "Restored",
    "notes": "Composite filling placed 2020",
    "created_at": "2024-01-15T10:00:00Z"
  },
  ...
]
```

**2. Add Dental Record**
```
POST /api/patients/{patientId}/odontogram-records
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "tooth_id": 11,
  "condition": "Caries",
  "notes": "Class II cavity on distal surface"
}

Response 201: {
  "id": 1,
  "patient_id": 1,
  "tooth_id": 11,
  "condition": "Caries",
  "notes": "Class II cavity on distal surface",
  "created_at": "2024-01-15T10:00:00Z"
}
```

**3. Update Dental Record**
```
PUT /api/patients/{patientId}/odontogram-records/{recordId}
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "condition": "Restored",
  "notes": "Filling placed"
}

Response 200: {
  "id": 1,
  "patient_id": 1,
  "tooth_id": 11,
  "condition": "Restored",
  "notes": "Filling placed",
  "updated_at": "2024-01-16T14:30:00Z"
}
```

**4. Delete Dental Record**
```
DELETE /api/patients/{patientId}/odontogram-records/{recordId}
Authorization: Bearer {token}

Response 204: (No Content)
```

**Controller Implementation** (app/Http/Controllers/PatientController.php):
```php
public function saveDentalRecord(Request $request, $patientId) {
    $validated = $request->validate([
        'tooth_id' => 'required|integer|between:1,32',
        'condition' => 'required|string',
        'notes' => 'nullable|string',
    ]);

    $record = DentalRecord::create([
        'patient_id' => $patientId,
        ...$validated
    ]);

    return response()->json($record, 201);
}

public function getDentalRecords($patientId) {
    $records = DentalRecord::where('patient_id', $patientId)->get();
    return response()->json($records);
}

public function updateDentalRecord(Request $request, $patientId, $recordId) {
    $record = DentalRecord::findOrFail($recordId);
    $record->update($request->all());
    return response()->json($record);
}

public function deleteDentalRecord($patientId, $recordId) {
    DentalRecord::findOrFail($recordId)->delete();
    return response()->json(null, 204);
}
```

#### 3.3.2 Periodontal Records

**Description**: Document periodontal examination findings (gum health, pockets, bleeding, etc.).

**File References**:
- **Model**: `app/Models/PeriodontalRecord.php`
- **Controller**: `app/Http/Controllers/PatientController.php` (lines 40-43)
- **Routes**: `routes/api.php` (lines 39-43)
- **Database Schema**: `database/migrations/2025_10_12_121921_create_branches_table.php` (lines 79-91)

**Database Schema**:
```php
Schema::create('periodontal_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id');
    $table->integer('tooth_id');       // Which tooth examined
    $table->json('pockets');           // Pocket depths (mm) - e.g., [3, 3, 2, 4]
    $table->integer('recession');      // Recession in mm
    $table->string('mobility');        // Tooth mobility: "None", "I", "II", "III"
    $table->string('furcation')->nullable();  // Furcation involvement
    $table->json('conditions');        // Other findings: ["BOP", "Plaque"]
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
});
```

**API Example**:
```
POST /api/patients/{patientId}/periodontal-records
Body: {
  "tooth_id": 11,
  "pockets": [3, 2, 3, 2],  // 4 sites per tooth
  "recession": 1,
  "mobility": "None",
  "furcation": null,
  "conditions": ["BOP", "Plaque"]
}
```

#### 3.3.3 Soft Tissue Records

**Description**: Document oral pathology and soft tissue findings.

**File References**:
- **Model**: `app/Models/SoftTissueRecord.php`
- **Controller**: `app/Http/Controllers/PatientController.php` (lines 57-61)
- **Database Schema**: `database/migrations/2025_10_12_121921_create_branches_table.php` (lines 93-103)

**Database Schema**:
```php
Schema::create('soft_tissue_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id');
    $table->date('date');
    $table->string('location');       // e.g., "Buccal mucosa", "Hard palate"
    $table->string('type');           // "Ulcer", "Lesion", "Inflammation", etc.
    $table->text('description');      // Detailed description
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
});
```

**API Example**:
```
POST /api/patients/{patientId}/soft-tissue-records
Body: {
  "date": "2024-01-15",
  "location": "Buccal mucosa",
  "type": "Ulcer",
  "description": "Round ulcer, 5mm diameter, painful"
}
```

#### 3.3.4 Orthodontic Records

**Description**: Track orthodontic treatment progress (braces, aligners, etc.).

**File References**:
- **Model**: `app/Models/OrthodonticRecord.php`
- **Database Schema**: `database/migrations/2025_10_12_121921_create_branches_table.php` (lines 105-116)

**Database Schema**:
```php
Schema::create('orthodontic_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id');
    $table->date('date');
    $table->string('appliance');      // "Fixed braces", "Invisalign", "Expander"
    $table->string('wire_size')->nullable();  // e.g., "0.016x0.022"
    $table->json('bracket_changes')->nullable();  // Details of bracket/wire changes
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
});
```

#### 3.3.5 Patient Images

**Description**: Store clinical photos (intraoral and extraoral).

**File References**:
- **Model**: `app/Models/PatientImage.php`
- **Controller**: `app/Http/Controllers/PatientController.php` (lines 28-31)
- **Database Schema**: `database/migrations/2025_10_12_121921_create_branches_table.php` (lines 68-77)

**Database Schema**:
```php
Schema::create('patient_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id');
    $table->string('url');            // Image file path or S3 URL
    $table->string('description');    // e.g., "Intraoral right side"
    $table->date('date');
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
});
```

**API Example**:
```
POST /api/patients/{patientId}/images
Body: {
  "url": "https://s3.aws.com/patient-1-intraoral.jpg",
  "description": "Intraoral right side",
  "date": "2024-01-15"
}

Response 201: {
  "id": 1,
  "patient_id": 1,
  "url": "https://s3.aws.com/patient-1-intraoral.jpg",
  "description": "Intraoral right side",
  "date": "2024-01-15"
}
```

---

### 3.4 Appointment Scheduling

#### 3.4.1 Appointment Creation and Management

**Description**: Schedule appointments between patients and dentists, track appointment status and flow.

**File References**:
- **Model**: `app/Models/Appointment.php`
- **Controller**: `app/Http/Controllers/AppointmentController.php`
- **Routes**: `routes/api.php` (lines 69-71)
- **Database Schema**: `database/migrations/2025_10_12_121923_create_appointments_table.php` (lines 28-45)

**Database Schema**:
```php
Schema::create('appointments', function (Blueprint $table) {
    $table->id();
    $table->string('patient_name');
    $table->foreignId('patient_id');           // Link to patient
    $table->string('doctor_name');
    $table->foreignId('doctor_id');            // Link to employee (dentist)
    $table->string('treatment');               // e.g., "Checkup", "Filling"
    $table->enum('status', [
        'Scheduled',   // Upcoming
        'Completed',   // Finished
        'Cancelled',   // Cancelled
        'Waiting'      // In waiting area
    ]);
    $table->date('date');
    $table->string('time');                    // HH:MM format
    $table->foreignId('branch_id');            // Which branch
    $table->string('token_number');            // Queue/token for patient flow
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
    $table->foreign('doctor_id')->references('id')->on('employees');
    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Endpoints**:

**1. Create Appointment**
```
POST /api/appointments
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "patient_id": 1,
  "patient_name": "John Doe",
  "doctor_id": 5,
  "doctor_name": "Dr. Smith",
  "treatment": "Root Canal - Tooth #11",
  "date": "2024-02-15",
  "time": "10:00",
  "branch_id": 1,
  "status": "Scheduled"
}

Response 201: {
  "id": 1,
  "patient_id": 1,
  "patient_name": "John Doe",
  "doctor_id": 5,
  "doctor_name": "Dr. Smith",
  "treatment": "Root Canal - Tooth #11",
  "date": "2024-02-15",
  "time": "10:00",
  "status": "Scheduled",
  "branch_id": 1,
  "token_number": "A001",
  "created_at": "2024-01-15T10:00:00Z"
}
```

**2. Get All Appointments**
```
GET /api/appointments
Authorization: Bearer {token}

Response 200: [
  {
    "id": 1,
    "patient_name": "John Doe",
    "doctor_name": "Dr. Smith",
    "treatment": "Root Canal",
    "status": "Scheduled",
    "date": "2024-02-15",
    "time": "10:00",
    "token_number": "A001"
  },
  ...
]
```

**3. Get Single Appointment**
```
GET /api/appointments/{id}
Authorization: Bearer {token}

Response 200: {
  "id": 1,
  "patient_id": 1,
  "patient_name": "John Doe",
  "doctor_id": 5,
  "doctor_name": "Dr. Smith",
  "treatment": "Root Canal - Tooth #11",
  "date": "2024-02-15",
  "time": "10:00",
  "status": "Scheduled",
  "branch_id": 1,
  "token_number": "A001",
  "patient": { id, name, email, phone },
  "doctor": { id, name, email, role },
  "branch": { id, name, address }
}
```

**4. Update Appointment Status**
```
PATCH /api/appointments/{id}/status
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "status": "Completed"
}

Response 200: {
  "id": 1,
  "status": "Completed",
  "updated_at": "2024-02-15T11:30:00Z"
}
```

**Status Workflow**:
```
Scheduled ─→ Waiting ─→ Completed
    ↓
  Cancelled
```

**Controller Implementation** (app/Http/Controllers/AppointmentController.php):
```php
public function store(Request $request) {
    $validated = $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'patient_name' => 'required|string',
        'doctor_id' => 'required|exists:employees,id',
        'doctor_name' => 'required|string',
        'treatment' => 'required|string',
        'date' => 'required|date',
        'time' => 'required|string',
        'branch_id' => 'required|exists:branches,id',
    ]);

    // Generate unique token number for queue
    $tokenNumber = 'A' . str_pad(Appointment::count() + 1, 3, '0', STR_PAD_LEFT);

    $appointment = Appointment::create([
        ...$validated,
        'status' => 'Scheduled',
        'token_number' => $tokenNumber,
    ]);

    return response()->json($appointment, 201);
}

public function updateStatus(Request $request, $id) {
    $appointment = Appointment::findOrFail($id);
    
    $validated = $request->validate([
        'status' => 'required|in:Scheduled,Completed,Cancelled,Waiting',
    ]);

    $appointment->update($validated);
    return response()->json($appointment);
}
```

---

### 3.5 Billing & Invoicing

#### 3.5.1 Invoice Creation and Management

**Description**: Generate invoices for treatment provided, itemize treatments and charges, track payments.

**File References**:
- **Models**: `app/Models/Invoice.php`, `app/Models/InvoiceTreatment.php`, `app/Models/InvoiceOtherCharge.php`, `app/Models/PaymentTransaction.php`
- **Controller**: `app/Http/Controllers/InvoiceController.php` (lines 75-82)
- **Routes**: `routes/api.php` (lines 73-75)
- **Database Schema**: `database/migrations/2025_10_12_121923_create_appointments_table.php` (lines 47-93)

**Database Schema**:
```php
// Invoice table (main)
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('patient_name');
    $table->foreignId('patient_id');        // Link to patient
    $table->decimal('amount', 10, 2);       // Total invoice amount
    $table->decimal('paid_amount', 10, 2)->default(0);  // Already paid
    $table->enum('status', [
        'Pending',   // Awaiting payment
        'Paid',      // Fully paid
        'Overdue'    // Past due date, unpaid
    ]);
    $table->date('issue_date');
    $table->date('due_date');
    $table->foreignId('branch_id');
    $table->string('payment_method')->nullable();  // e.g., "Cash", "Card", "Insurance"
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
    $table->foreign('branch_id')->references('id')->on('branches');
});

// Line items - treatments
Schema::create('invoice_treatments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id');
    $table->string('procedure');       // e.g., "Filling - Tooth #11"
    $table->decimal('cost', 10, 2);
    $table->timestamps();

    $table->foreign('invoice_id')->references('id')->on('invoices');
});

// Line items - other charges (lab, materials, etc.)
Schema::create('invoice_other_charges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id');
    $table->string('description');     // e.g., "Lab fee", "Materials"
    $table->decimal('amount', 10, 2);
    $table->timestamps();

    $table->foreign('invoice_id')->references('id')->on('invoices');
});

// Payment log
Schema::create('payment_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id');
    $table->date('date');
    $table->decimal('amount', 10, 2);  // Amount paid in this transaction
    $table->string('method');          // "Cash", "Card", "Insurance", "Cheque"
    $table->timestamps();

    $table->foreign('invoice_id')->references('id')->on('invoices');
});
```

**Data Model**:
```php
// app/Models/Invoice.php
class Invoice extends Model {
    protected $fillable = [
        'patient_id',
        'patient_name',
        'amount',           // Total
        'paid_amount',      // Cumulative payments
        'status',           // Pending, Paid, Overdue
        'issue_date',
        'due_date',
        'branch_id',
        'payment_method',
    ];

    public function treatments(): HasMany {
        return $this->hasMany(InvoiceTreatment::class);
    }

    public function otherCharges(): HasMany {
        return $this->hasMany(InvoiceOtherCharge::class);
    }

    public function transactions(): HasMany {
        return $this->hasMany(PaymentTransaction::class);
    }
}

// app/Models/InvoiceTreatment.php
class InvoiceTreatment extends Model {
    protected $fillable = ['invoice_id', 'procedure', 'cost'];
}

// app/Models/PaymentTransaction.php
class PaymentTransaction extends Model {
    protected $fillable = ['invoice_id', 'date', 'amount', 'method'];
}
```

**API Endpoints**:

**1. Create Invoice**
```
POST /api/invoices
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "patient_id": 1,
  "patient_name": "John Doe",
  "amount": 500.00,
  "issue_date": "2024-01-15",
  "due_date": "2024-02-15",
  "branch_id": 1,
  "treatments": [
    {
      "procedure": "Root Canal - Tooth #11",
      "cost": 300.00
    },
    {
      "procedure": "Crown - Tooth #11",
      "cost": 200.00
    }
  ],
  "other_charges": [
    {
      "description": "Lab fee",
      "amount": 0
    }
  ]
}

Response 201: {
  "id": 1,
  "patient_id": 1,
  "patient_name": "John Doe",
  "amount": 500.00,
  "paid_amount": 0,
  "status": "Pending",
  "issue_date": "2024-01-15",
  "due_date": "2024-02-15",
  "branch_id": 1,
  "treatments": [
    { "procedure": "Root Canal - Tooth #11", "cost": 300.00 },
    { "procedure": "Crown - Tooth #11", "cost": 200.00 }
  ],
  "other_charges": [
    { "description": "Lab fee", "amount": 0 }
  ],
  "transactions": []
}
```

**2. Get All Invoices**
```
GET /api/invoices?branch_id=1&status=Pending
Authorization: Bearer {token}

Response 200: {
  "data": [
    {
      "id": 1,
      "patient_name": "John Doe",
      "amount": 500.00,
      "paid_amount": 100.00,
      "status": "Pending",
      "issue_date": "2024-01-15",
      "due_date": "2024-02-15",
      "outstanding": 400.00,  // amount - paid_amount
      "treatments": [...],
      "transactions": [...]
    }
  ]
}
```

**3. Record Payment**
```
POST /api/invoices/{invoiceId}/payments
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "amount": 100.00,
  "date": "2024-01-20",
  "method": "Cash"
}

Response 201: {
  "id": 1,
  "invoice_id": 1,
  "amount": 100.00,
  "date": "2024-01-20",
  "method": "Cash",
  "created_at": "2024-01-20T14:30:00Z"
}

Note: After this request, the Invoice record is updated:
  paid_amount becomes 100.00
  If paid_amount >= amount, status changes to "Paid"
```

**Controller Implementation** (app/Http/Controllers/InvoiceController.php):
```php
public function store(Request $request) {
    $validated = $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'patient_name' => 'required|string',
        'amount' => 'required|numeric',
        'issue_date' => 'required|date',
        'due_date' => 'required|date',
        'branch_id' => 'required|exists:branches,id',
    ]);

    $invoice = Invoice::create([
        ...$validated,
        'status' => 'Pending',
        'paid_amount' => 0,
    ]);

    // Add treatments
    if ($request->has('treatments')) {
        foreach ($request->treatments as $treatment) {
            InvoiceTreatment::create([
                'invoice_id' => $invoice->id,
                'procedure' => $treatment['procedure'],
                'cost' => $treatment['cost'],
            ]);
        }
    }

    // Add other charges
    if ($request->has('other_charges')) {
        foreach ($request->other_charges as $charge) {
            InvoiceOtherCharge::create([
                'invoice_id' => $invoice->id,
                'description' => $charge['description'],
                'amount' => $charge['amount'],
            ]);
        }
    }

    return response()->json($invoice->load('treatments', 'otherCharges'), 201);
}

public function addPayment(Request $request, $invoiceId) {
    $invoice = Invoice::findOrFail($invoiceId);

    $validated = $request->validate([
        'amount' => 'required|numeric',
        'date' => 'required|date',
        'method' => 'required|string',
    ]);

    // Record payment transaction
    $transaction = PaymentTransaction::create([
        'invoice_id' => $invoiceId,
        ...$validated,
    ]);

    // Update invoice paid_amount
    $invoice->paid_amount += $validated['amount'];

    // Auto-update status if fully paid
    if ($invoice->paid_amount >= $invoice->amount) {
        $invoice->status = 'Paid';
    }

    $invoice->save();

    return response()->json($transaction, 201);
}
```

#### 3.5.2 Outstanding Balance Tracking

**Description**: Automatically calculate and track outstanding balances for each invoice.

**Logic**:
```
Outstanding Balance = Invoice.amount - Invoice.paid_amount

Example:
Invoice amount: $500
After payment 1: paid_amount = $100 → Outstanding = $400
After payment 2: paid_amount = $250 → Outstanding = $250
After payment 3: paid_amount = $500 → Outstanding = $0 (status = "Paid")
```

---

### 3.6 Staff Management

#### 3.6.1 Employee Management

**Description**: Maintain employee records including contact info, role, salary, and hire date.

**File References**:
- **Model**: `app/Models/Employee.php`
- **Controller**: `app/Http/Controllers/EmployeeController.php`
- **Routes**: `routes/api.php` (line 78)
- **Database Schema**: `database/migrations/2025_10_12_121923_create_appointments_table.php` (lines 11-26)

**Database Schema**:
```php
Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('avatar')->nullable();
    $table->string('email')->unique();
    $table->string('role');             // "Dentist", "Hygienist", "Receptionist", "Manager"
    $table->unsignedBigInteger('role_id');  // Link to RolePermission
    $table->enum('status', ['Active', 'On Leave'])->default('Active');
    $table->decimal('salary', 10, 2);
    $table->date('hire_date');
    $table->string('password');         // Bcrypt hashed
    $table->foreignId('branch_id');     // Which branch they work at
    $table->timestamps();

    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**Model**:
```php
// app/Models/Employee.php
class Employee extends Model {
    use HasFactory, HasApiTokens;  // Sanctum tokens for login

    protected $fillable = [
        'name', 'avatar', 'email', 'role', 'status',
        'salary', 'hire_date', 'branch_id', 'password', 'role_id'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function branch(): BelongsTo {
        return $this->belongsTo(Branch::class);
    }

    public function appointments(): HasMany {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function payrolls(): HasMany {
        return $this->hasMany(Payroll::class);
    }

    public function attendance(): HasMany {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany {
        return $this->hasMany(LeaveRequest::class);
    }
}
```

**API Endpoints**:

**1. Create Employee**
```
POST /api/employees
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "name": "Dr. James Smith",
  "email": "james@clinic.com",
  "password": "secure_password",
  "role": "Dentist",
  "role_id": 2,
  "salary": 5000.00,
  "hire_date": "2023-01-15",
  "branch_id": 1,
  "status": "Active"
}

Response 201: {
  "id": 5,
  "name": "Dr. James Smith",
  "email": "james@clinic.com",
  "role": "Dentist",
  "salary": 5000.00,
  "hire_date": "2023-01-15",
  "branch_id": 1,
  "status": "Active"
}
```

**2. Get All Employees**
```
GET /api/employees
Authorization: Bearer {token}

Response 200: [
  {
    "id": 5,
    "name": "Dr. James Smith",
    "email": "james@clinic.com",
    "role": "Dentist",
    "status": "Active",
    "salary": 5000.00,
    "branch": { "id": 1, "name": "Downtown Clinic" }
  },
  ...
]
```

**3. Update Employee**
```
PUT /api/employees/{id}
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "salary": 5500.00,
  "status": "On Leave"
}

Response 200: {
  "id": 5,
  "name": "Dr. James Smith",
  "email": "james@clinic.com",
  "role": "Dentist",
  "salary": 5500.00,
  "status": "On Leave"
}
```

---

### 3.7 Business Operations

#### 3.7.1 Payroll Management

**Description**: Create and track payroll records for employees.

**File References**:
- **Model**: `app/Models/Payroll.php`
- **Controller**: `app/Http/Controllers/ManagementController.php` (lines 38-86)
- **Routes**: `routes/api.php` (lines 82-84)
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 21-36)

**Database Schema**:
```php
Schema::create('payrolls', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id');
    $table->string('employee_name');
    $table->string('pay_period');       // e.g., "August 2025"
    $table->decimal('gross_pay', 10, 2);        // Base salary
    $table->decimal('deductions', 10, 2);       // Taxes, benefits, etc.
    $table->decimal('net_pay', 10, 2);          // gross_pay - deductions
    $table->date('pay_date');
    $table->enum('status', ['Paid', 'Pending']);
    $table->foreignId('branch_id');
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees');
    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Endpoints**:

**1. Create Payroll**
```
POST /api/management/payrolls
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "employee_id": 5,
  "employee_name": "Dr. James Smith",
  "pay_period": "August 2025",
  "gross_pay": 5000.00,
  "deductions": 500.00,
  "net_pay": 4500.00,
  "pay_date": "2025-08-31",
  "branch_id": 1
}

Response 201: {
  "id": 1,
  "employee_name": "Dr. James Smith",
  "pay_period": "August 2025",
  "gross_pay": 5000.00,
  "net_pay": 4500.00,
  "status": "Pending"
}
```

**2. Get Payrolls**
```
GET /api/management/payrolls?branch_id=1
Authorization: Bearer {token}

Response 200: [
  {
    "id": 1,
    "employee_name": "Dr. James Smith",
    "pay_period": "August 2025",
    "gross_pay": 5000.00,
    "net_pay": 4500.00,
    "status": "Pending"
  }
]
```

**Controller Implementation** (app/Http/Controllers/ManagementController.php:66-86):
```php
public function createPayroll(Request $request) {
    $validated = $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'employee_name' => 'required|string',
        'pay_period' => 'required|string',
        'gross_pay' => 'required|numeric',
        'deductions' => 'required|numeric',
        'net_pay' => 'required|numeric',
        'pay_date' => 'required|date',
        'branch_id' => 'required|exists:branches,id',
    ]);

    $payroll = Payroll::create([
        ...$validated,
        'status' => 'Pending',
    ]);

    return response()->json($payroll, 201);
}
```

#### 3.7.2 Attendance Tracking

**Description**: Record daily attendance for employees.

**File References**:
- **Model**: `app/Models/AttendanceRecord.php`
- **Controller**: `app/Http/Controllers/ManagementController.php`
- **Routes**: `routes/api.php` (lines 86-88)
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 38-51)

**Database Schema**:
```php
Schema::create('attendance_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id');
    $table->string('employee_name');
    $table->date('date');
    $table->enum('status', ['Present', 'Absent', 'On Leave']);
    $table->string('check_in')->nullable();    // Time in HH:MM
    $table->string('check_out')->nullable();   // Time out HH:MM
    $table->foreignId('branch_id');
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees');
    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Example**:
```
POST /api/management/attendance
Body: {
  "employee_id": 5,
  "employee_name": "Dr. James Smith",
  "date": "2024-08-15",
  "status": "Present",
  "check_in": "08:30",
  "check_out": "17:00",
  "branch_id": 1
}

Response 201: {
  "id": 1,
  "employee_name": "Dr. James Smith",
  "date": "2024-08-15",
  "status": "Present",
  "check_in": "08:30",
  "check_out": "17:00"
}
```

#### 3.7.3 Leave Requests

**Description**: Submit and approve employee leave (vacation, sick leave, etc.).

**File References**:
- **Model**: `app/Models/LeaveRequest.php`
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 84-98)

**Database Schema**:
```php
Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id');
    $table->string('employee_name');
    $table->string('leave_type');       // "Vacation", "Sick Leave", "Personal"
    $table->date('start_date');
    $table->date('end_date');
    $table->text('reason');
    $table->enum('status', ['Pending', 'Approved', 'Rejected']);
    $table->foreignId('branch_id');
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees');
    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Example**:
```
POST /api/management/leave-requests
Body: {
  "employee_id": 5,
  "employee_name": "Dr. James Smith",
  "leave_type": "Vacation",
  "start_date": "2024-09-01",
  "end_date": "2024-09-07",
  "reason": "Personal vacation",
  "branch_id": 1
}

Response 201: {
  "id": 1,
  "employee_name": "Dr. James Smith",
  "leave_type": "Vacation",
  "start_date": "2024-09-01",
  "end_date": "2024-09-07",
  "status": "Pending"
}

PATCH /api/management/leave-requests/{id}
Body: { "status": "Approved" }
Response 200: { status: "Approved", ... }
```

#### 3.7.4 Inventory Management

**Description**: Track dental supplies and materials inventory.

**File References**:
- **Model**: `app/Models/InventoryItem.php`
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 53-65)

**Database Schema**:
```php
Schema::create('inventory_items', function (Blueprint $table) {
    $table->id();
    $table->string('name');             // e.g., "Composite Resin A2"
    $table->string('supplier');
    $table->integer('stock');           // Current quantity
    $table->integer('reorder_level');   // Minimum quantity threshold
    $table->decimal('price', 10, 2);
    $table->enum('status', ['In Stock', 'Low Stock', 'Out of Stock']);
    $table->foreignId('branch_id');
    $table->timestamps();

    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Example**:
```
GET /api/management/inventory
Response 200: [
  {
    "id": 1,
    "name": "Composite Resin A2",
    "supplier": "3M",
    "stock": 25,
    "reorder_level": 10,
    "status": "In Stock",
    "price": 50.00
  }
]

PATCH /api/management/inventory/{id}
Body: { "stock": 15 }
Response 200: { stock: 15, status: "Low Stock", ... }
```

#### 3.7.5 Laboratory Orders

**Description**: Track external laboratory work (crowns, dentures, bridges, etc.).

**File References**:
- **Model**: `app/Models/LaboratoryOrder.php`
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 67-82)

**Database Schema**:
```php
Schema::create('laboratory_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id');
    $table->string('patient_name');
    $table->string('lab');              // Lab name
    $table->string('item');             // "Crown", "Denture", "Bridge"
    $table->integer('tooth')->nullable();    // Tooth number if applicable
    $table->enum('status', [
        'Sent',      // Sent to lab
        'Received',  // Received from lab
        'Completed', // Delivered to patient
        'Cancelled'
    ]);
    $table->date('sent_date');
    $table->date('due_date');
    $table->foreignId('branch_id');
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients');
    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Example**:
```
POST /api/management/lab-orders
Body: {
  "patient_id": 1,
  "patient_name": "John Doe",
  "lab": "ABC Dental Lab",
  "item": "Crown",
  "tooth": 11,
  "sent_date": "2024-08-15",
  "due_date": "2024-08-25",
  "branch_id": 1
}

Response 201: {
  "id": 1,
  "patient_name": "John Doe",
  "lab": "ABC Dental Lab",
  "item": "Crown",
  "status": "Sent"
}

PATCH /api/management/lab-orders/{id}/status
Body: { "status": "Received" }
```

#### 3.7.6 Financial Dashboard

**Description**: Aggregate revenue and operational metrics for reporting.

**File References**:
- **Model**: `app/Models/ChartData.php`
- **Controller**: `app/Http/Controllers/ManagementController.php`
- **Routes**: `routes/api.php` (line 112)
- **Database Schema**: `database/migrations/2025_10_12_121924_create_management_tables.php` (lines 11-19)

**Database Schema**:
```php
Schema::create('chart_data', function (Blueprint $table) {
    $table->id();
    $table->string('month');            // e.g., "August 2025"
    $table->decimal('revenue', 10, 2);  // Total revenue for month
    $table->foreignId('branch_id');
    $table->timestamps();

    $table->foreign('branch_id')->references('id')->on('branches');
});
```

**API Example**:
```
GET /api/management/chart-data?branch_id=1
Response 200: [
  { "month": "July 2025", "revenue": 15000.00 },
  { "month": "August 2025", "revenue": 18500.00 },
  { "month": "September 2025", "revenue": 17200.00 }
]
```

---

## Non-Functional Requirements

### 4.1 Performance

- **API Response Time**: All endpoints should respond within 500ms for typical loads
- **Database Query Optimization**: Use eager loading (`.with()`) to prevent N+1 query problems
- **Caching**: Cache frequently accessed data (roles, branches) in database cache layer

### 4.2 Security

- **Password Hashing**: All passwords hashed with bcrypt (BCRYPT_ROUNDS=12)
- **API Authentication**: Token-based auth via Laravel Sanctum
- **CORS**: Enable CORS only for trusted origins (frontend domain)
- **Input Validation**: All user inputs validated server-side
- **SQL Injection Prevention**: Use Eloquent ORM (parameterized queries)
- **HTTPS**: All API calls over HTTPS in production

**Implementation Reference**:
```php
// bootstrap/app.php - CORS configuration
$middleware->api(prepend: [
    \Illuminate\Http\Middleware\HandleCors::class,
]);

// routes/api.php - CSRF exemption for API
$middleware->validateCsrfTokens(except: [
    'api/*',
]);
```

### 4.3 Scalability

- **Stateless API**: Uses token auth (Sanctum) not sessions → easy horizontal scaling
- **Database Design**: Foreign keys, proper indexing for fast lookups
- **Multi-branch Architecture**: Built-in support for clinic chains

### 4.4 Data Integrity

- **Foreign Key Constraints**: All relationships enforced at database level
- **Atomic Transactions**: Invoice + payment transactions atomic operations
- **Soft Deletes**: (Not implemented) Consider adding for audit trail
- **Audit Logging**: Track who created/modified records via `created_by`, `updated_by` fields

### 4.5 Compliance

- **HIPAA Readiness** (if applicable):
  - Encrypt patient data at rest and in transit
  - Log all data access and modifications
  - Implement user authentication and authorization
  - Regular backups and disaster recovery

---

## Data Model

### 5.1 Entity Relationship Diagram (Conceptual)

```
                    Branch
                      |
        ┌─────────────┼─────────────┐
        |             |             |
    Employee      Patient       Appointment
        |             |             |
      Payroll     DentalRecord   Invoice
        |         Periodontal      |
    Attendance   SoftTissue    InvoiceTreatment
        |        Orthodontic   InvoiceOtherCharge
    LeaveRequest PatientImage  PaymentTransaction
        |        TreatmentPlan
    StaffAdvance LaboratoryOrder
        
    InventoryItem
    Expense
    ChartData
    RolePermission
```

### 5.2 Key Relationships

| Parent | Child | Type | Cardinality |
|--------|-------|------|-------------|
| Branch | Patient | FK | 1 to Many |
| Branch | Employee | FK | 1 to Many |
| Branch | Appointment | FK | 1 to Many |
| Patient | DentalRecord | FK | 1 to Many |
| Patient | PeriodontalRecord | FK | 1 to Many |
| Patient | SoftTissueRecord | FK | 1 to Many |
| Patient | OrthodonticRecord | FK | 1 to Many |
| Patient | PatientImage | FK | 1 to Many |
| Patient | TreatmentPlan | FK | 1 to Many |
| Patient | Appointment | FK | 1 to Many |
| Patient | Invoice | FK | 1 to Many |
| Patient | LaboratoryOrder | FK | 1 to Many |
| Employee | Appointment | FK (doctor_id) | 1 to Many |
| Employee | Payroll | FK | 1 to Many |
| Employee | AttendanceRecord | FK | 1 to Many |
| Employee | LeaveRequest | FK | 1 to Many |
| Employee | StaffAdvance | FK | 1 to Many |
| Invoice | InvoiceTreatment | FK | 1 to Many |
| Invoice | InvoiceOtherCharge | FK | 1 to Many |
| Invoice | PaymentTransaction | FK | 1 to Many |

---

## API Specifications

### 6.1 Base URL & Authentication

```
Base URL: https://clinic.example.com/api

Authentication: Bearer Token (Sanctum)
Header: Authorization: Bearer {token}
```

### 6.2 Response Format

All responses are JSON with consistent structure:

**Success Response (200, 201)**:
```json
{
  "message": "Resource created successfully",
  "data": { /* resource object */ }
}
```

**List Response (200)**:
```json
{
  "message": "Resources retrieved successfully",
  "data": [ /* array of resources */ ]
}
```

**Error Response (4xx, 5xx)**:
```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Field validation error"]
  }
}
```

### 6.3 HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 204 | No Content - Successful DELETE |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Internal Server Error |

### 6.4 Pagination (Future Enhancement)

Currently, lists return all records. For scalability, implement:

```
GET /api/patients?page=1&per_page=20&sort=first_name&order=asc

Response 200: {
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

---

## Business Logic & Workflows

### 7.1 Patient Check-in Workflow

```
1. Receptionist receives patient
   ↓
2. POST /api/appointments/{appointmentId}/status with "Waiting"
   ↓
3. System generates token_number (e.g., "A001")
   ↓
4. Display token number on screen
   ↓
5. Patient waits in reception area
```

### 7.2 Treatment & Billing Workflow

```
1. Dentist examines patient
   ↓
2. POST /api/patients/{patientId}/odontogram-records (document findings)
   POST /api/patients/{patientId}/treatments (add treatment plan)
   ↓
3. Treatment completed
   PATCH /api/appointments/{appointmentId}/status = "Completed"
   ↓
4. POST /api/invoices (create invoice for treatment)
   ├── Add InvoiceTreatment line items
   ├── Add InvoiceOtherCharge for lab/materials
   └── Set due_date
   ↓
5. Patient pays
   POST /api/invoices/{invoiceId}/payments
   ├── Record payment amount, method, date
   ├── Update Invoice.paid_amount
   ├── If paid_amount >= amount: status = "Paid"
   └── Return payment confirmation
```

### 7.3 Inventory Low Stock Alert (Future)

```
For each InventoryItem:
  IF stock <= reorder_level:
    status = "Low Stock"
    Send alert to manager
  ELSE IF stock == 0:
    status = "Out of Stock"
    Stop allowing appointment creation for related procedures
```

### 7.4 Lab Order Workflow

```
1. POST /api/management/lab-orders (send to lab)
   status = "Sent"
   ↓
2. Lab receives and works on order
   ↓
3. PATCH /api/management/lab-orders/{id}/status = "Received"
   ↓
4. Dentist fits restoration
   ↓
5. PATCH /api/management/lab-orders/{id}/status = "Completed"
```

---

## Implementation Guide

### 8.1 Setting Up the Project

**Step 1: Clone & Install Dependencies**
```bash
git clone https://github.com/your-org/chart-backend.git
cd chart-backend

composer install
cp .env.example .env

php artisan key:generate
```

**Step 2: Configure Database**
```env
# .env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

**Step 3: Run Migrations**
```bash
php artisan migrate

# Or with fresh DB:
php artisan migrate:fresh
```

**Step 4: Seed Initial Data (Optional)**
```bash
php artisan db:seed
```

**Step 5: Start Development Server**
```bash
php artisan serve
# API runs on http://localhost:8000/api
```

### 8.2 Creating a New Feature

**Example: Add Patient X-Ray Records**

**Step 1: Create Migration**
```bash
php artisan make:migration create_patient_xrays_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_create_patient_xrays_table.php
public function up() {
    Schema::create('patient_xrays', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->string('type');        // "Panoramic", "Periapical"
        $table->date('date_taken');
        $table->string('file_path');   // Where image is stored
        $table->text('notes')->nullable();
        $table->timestamps();
        
        $table->foreign('patient_id')->references('id')->on('patients');
    });
}
```

**Step 2: Create Model**
```bash
php artisan make:model PatientXray
```

```php
// app/Models/PatientXray.php
class PatientXray extends Model {
    protected $fillable = ['patient_id', 'type', 'date_taken', 'file_path', 'notes'];
    
    public function patient(): BelongsTo {
        return $this->belongsTo(Patient::class);
    }
}
```

**Step 3: Add Relationship to Patient Model**
```php
// app/Models/Patient.php
public function xrays(): HasMany {
    return $this->hasMany(PatientXray::class);
}
```

**Step 4: Create Controller Methods**
```php
// In PatientController
public function getXrays($patientId) {
    return response()->json(
        PatientXray::where('patient_id', $patientId)->get()
    );
}

public function addXray(Request $request, $patientId) {
    $validated = $request->validate([
        'type' => 'required|string',
        'date_taken' => 'required|date',
        'file_path' => 'required|string',
        'notes' => 'nullable|string',
    ]);
    
    $xray = PatientXray::create([
        'patient_id' => $patientId,
        ...$validated
    ]);
    
    return response()->json($xray, 201);
}
```

**Step 5: Add Routes**
```php
// routes/api.php
Route::get('/patients/{patientId}/xrays', [PatientController::class, 'getXrays']);
Route::post('/patients/{patientId}/xrays', [PatientController::class, 'addXray']);
```

**Step 6: Test the Endpoint**
```bash
curl -X POST http://localhost:8000/api/patients/1/xrays \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "Panoramic",
    "date_taken": "2024-08-15",
    "file_path": "/xrays/patient1_pano.jpg",
    "notes": "Full mouth radiographs"
  }'
```

### 8.3 Database Migration Strategy

**Creating Migration**:
```bash
php artisan make:migration create_table_name
```

**Naming Convention**:
- `create_*_table.php` — New table
- `add_*_to_*_table.php` — Add column
- `drop_*_from_*_table.php` — Remove column

**Example Migration Structure**:
```php
public function up() {
    Schema::create('table_name', function (Blueprint $table) {
        $table->id();                    // Primary key
        $table->foreignId('parent_id');  // Foreign key
        $table->string('name');          // String column
        $table->enum('status', [...]);   // Enumeration
        $table->json('metadata');        // JSON column
        $table->timestamps();            // created_at, updated_at
        
        $table->foreign('parent_id')->references('id')->on('parent_table');
    });
}

public function down() {
    Schema::dropIfExists('table_name');
}
```

### 8.4 Testing API Endpoints

**Using cURL**:
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "doctor@clinic.com", "password": "secret"}'

# Response:
# {"token": "1|a1b2c3...", "user": {...}}

# Get Patients
curl -X GET http://localhost:8000/api/patients \
  -H "Authorization: Bearer 1|a1b2c3..."
```

**Using Postman**:
1. Set `Authorization` header: `Bearer {token}`
2. Set `Content-Type`: `application/json`
3. Use endpoints from routes/api.php

**Using PHP Artisan Tinker**:
```bash
php artisan tinker

>>> $patient = Patient::find(1);
>>> $patient->dentalRecords;
>>> $patient->invoices;
```

---

## Deployment Considerations

### 8.5 Production Checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production`
- [ ] Use strong `APP_KEY`
- [ ] Configure `APP_URL` to production domain
- [ ] Switch to MySQL/PostgreSQL database
- [ ] Set up HTTPS/SSL certificate
- [ ] Configure `MAIL_MAILER` for real email service
- [ ] Set up backups (daily database backups)
- [ ] Enable query logging for monitoring
- [ ] Set up error logging (Sentry, Rollbar)
- [ ] Configure CORS for frontend domain
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`

### 8.6 Environment Variables Checklist

```env
# App
APP_NAME="Dental Clinic"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://clinic.example.com

# Database
DB_CONNECTION=mysql
DB_HOST=db.example.com
DB_PORT=3306
DB_DATABASE=dental_clinic
DB_USERNAME=root
DB_PASSWORD=strong_password

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=email_password

# AWS S3 (for file storage)
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=clinic-storage
```

---

## Conclusion

This SRS provides a comprehensive specification for a Dental Practice Management System. The implementation uses:

- **Laravel 12** for rapid, secure development
- **RESTful API** for client agnostic architecture
- **Eloquent ORM** for elegant database interactions
- **Sanctum** for token-based authentication
- **SQLite/MySQL** for data persistence

A developer with knowledge of PHP and Laravel can follow this document to:
1. Understand system requirements and functionality
2. Implement features by following the provided patterns
3. Build additional modules using the established architecture
4. Deploy and scale the system for production use

All file paths reference actual code in the `chart-backend` codebase for easy cross-reference during implementation.

