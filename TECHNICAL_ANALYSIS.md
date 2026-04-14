# Chart-Backend: Comprehensive Technical Analysis

## 1. System Purpose

This is a **Dental Practice Management System** (clinic/dental office backend) designed to manage all operational aspects of a dental practice or dental clinic chain. It serves as a centralized API backend for:

- **Patient Management**: Complete patient records including demographics, medical history, appointment scheduling
- **Clinical Documentation**: Comprehensive dental charting including odontogram (tooth mapping), periodontal records, orthodontic records, soft tissue examination records
- **Treatment Planning & Invoicing**: Treatment plans, invoice generation, payment tracking, and billing history
- **Business Operations**: Payroll, attendance, leave management, inventory, lab orders, and financial reporting
- **Multi-branch Support**: Multi-location clinic networks with branch-specific data isolation and reporting
- **Staff Management**: Employee records, roles, permissions, and HR operations

**Problem it solves**: Consolidates fragmented dental practice workflows (clinical charting, scheduling, billing, HR, inventory) into a single integrated system.

---

## 2. Architecture Overview

**Architecture Type**: **Monolithic REST API** with a backend-focused design (frontend agnostic).

### High-level Structure:
```
Clients (Web/Mobile)
        ↓
  REST API (JSON)
        ↓
   Laravel 12 Application
   ├── Route Layer (API routes in routes/api.php)
   ├── Controller Layer (HTTP request handlers)
   ├── Model Layer (Eloquent ORM, data access)
   └── Database Layer (SQLite)
```

### Architectural Layers:
- **Request/Response Layer**: Laravel routing + middleware (CORS, authentication)
- **Business Logic Layer**: Controllers handle request processing, validation, and orchestration
- **Data Access Layer**: Eloquent models with relationships to tables
- **Data Persistence Layer**: SQLite database with foreign key relationships
- **Authentication**: Laravel Sanctum (API tokens) for stateless authentication

**Design Paradigm**: **REST API-first, CRUD-heavy** with emphasis on resource management (patients, appointments, invoices, etc.)

---

## 3. Core Components

### Controllers (app/Http/Controllers/)

| Controller | Responsibility |
|---|---|
| **AuthController** | User login/logout, token generation, profile retrieval, role/permission lookup |
| **PatientController** | CRUD for patients; manages 6 types of dental records (dental, periodontal, orthodontic, soft tissue, treatment plans, images) |
| **AppointmentController** | Appointment scheduling, status updates (Scheduled→Completed→Cancelled) |
| **InvoiceController** | Invoice generation, payment tracking, treatment/charge itemization |
| **EmployeeController** | Staff directory, roles, contact info |
| **ManagementController** | Centralized hub for: payroll, attendance, inventory, lab orders, leave requests, staff advances, expenses, dashboard metrics |
| **RolePermissionController** | Role definition and permission assignment (role_id → permissions JSON) |

### Models (app/Models/ - 25 models total)

#### Core Clinical Models:
- **Patient**: Central entity; links to all clinical records, appointments, invoices
- **DentalRecord**: Odontogram entries (tooth ID + condition per tooth)
- **PeriodontalRecord**: Pocket depths, recession, mobility per tooth
- **OrthodonticRecord**: Appliance tracking, wire sizes, bracket changes
- **SoftTissueRecord**: Lesion/pathology documentation
- **PatientImage**: Visual documentation (intraoral/extraoral photos)
- **TreatmentPlan**: Proposed treatments with status (Proposed/Completed) and costing

#### Business Models:
- **Appointment**: Scheduling with patient, doctor, branch, status, token number
- **Invoice**, **InvoiceTreatment**, **InvoiceOtherCharge**, **PaymentTransaction**: Billing pipeline
- **Employee**: Staff with role, salary, hire date, branch assignment
- **Branch**: Multi-location support

#### Operations Models:
- **Payroll**: Gross/net pay, deductions, pay period tracking
- **AttendanceRecord**: Daily attendance (Present/Absent/On Leave)
- **LeaveRequest**: Time-off requests (Pending/Approved/Rejected)
- **InventoryItem**: Supplies stock (name, supplier, stock level, reorder level)
- **LaboratoryOrder**: External lab work (dentures, bridges, etc.)
- **StaffAdvance**: Salary advances to employees
- **Expense**: Operational expenses
- **ChartData**: Revenue metrics by month/branch
- **RolePermission**: Role definitions with JSON permission array

#### Supporting Models:
- **Treatment**: Master list of procedures and costs
- **User**: Standard Laravel auth (currently unused; auth via Employee model)

---

## 4. Data Flow

### Typical Request Flow (e.g., Patient Appointment Booking):

```
POST /api/appointments
  ↓
[Middleware: CORS, auth:sanctum validates token]
  ↓
AppointmentController@store
  ↓
[Validation: patient_id, doctor_id, date, branch_id required]
  ↓
Query: Patient::find, Employee::find, Branch::find (verify FK integrity)
  ↓
Appointment::create({patient_id, doctor_id, date, time, branch_id, status='Scheduled', ...})
  ↓
DB Insert → SQLite
  ↓
Eloquent returns instance with relationships loaded
  ↓
Response: 201 JSON {id, patient_name, doctor_name, status, date, time, ...}
```

### Complex Flow (Patient with Dental Charting):

```
POST /api/patients/{id}/odontogram-records
  ↓
PatientController@saveDentalRecord
  ↓
[Fetch patient; validate tooth_id, condition fields]
  ↓
DentalRecord::create({patient_id, tooth_id (1-32), condition, notes})
  ↓
DB Insert
  ↓
GET /api/patients/{id}
  ↓
Patient::with([
  'dentalRecords',     ← all 32 teeth with condition
  'treatmentPlans',    ← what needs treatment
  'images',            ← photos
  'periodontalRecords', ← gum health
  ...
])
  ↓
Return full patient record with nested arrays of related records
```

### Invoice Generation Flow:

```
POST /api/invoices
  ↓
InvoiceController@store
  ↓
[Validate: patient_id, amount, branch_id, treatment_ids]
  ↓
Invoice::create({patient_id, amount, status='Pending', issue_date, due_date})
  ↓
For each treatment_id:
  InvoiceTreatment::create({invoice_id, procedure, cost})
  ↓
POST /api/invoices/{id}/payments
  ↓
PaymentTransaction::create({invoice_id, amount, date, method})
  ↓
Invoice->paid_amount += payment_amount
  ↓
If paid_amount >= amount: Invoice->status = 'Paid'
```

---

## 5. Key Technologies & Dependencies

| Tech | Version | Purpose |
|---|---|---|
| **PHP** | ^8.2 | Backend runtime |
| **Laravel** | ^12.0 | Web framework (routing, ORM, migrations, middleware) |
| **SQLite** | (built-in) | Lightweight relational DB; default in .env |
| **Laravel Sanctum** | ^4.2 | API token authentication (stateless, JWT-like) |
| **Eloquent ORM** | (Laravel built-in) | Object-relational mapping, query builder |
| **Vite** | ^7.0.7 | Asset bundler (frontend assets) |
| **Tailwind CSS** | ^4.0.0 | Utility-first CSS framework |
| **Axios** | ^1.11.0 | HTTP client (frontend) |
| **Laravel Pint** | ^1.24 | PHP code linting |
| **PHPUnit** | ^11.5.3 | Testing framework |
| **Scribe** | ^5.3 | Auto-API documentation (generates /docs) |
| **Laravel Pail** | ^1.2.2 | Log monitoring CLI |

**Why these choices**:
- **Laravel**: Rapid development, extensive ORM, built-in auth, migrations, and rich ecosystem
- **SQLite**: Zero-setup, file-based DB perfect for small-to-medium clinics; can upgrade to MySQL/PostgreSQL
- **Sanctum**: Lightweight token auth without JWT complexity; good for SPAs and mobile apps
- **Tailwind**: Modern responsive UI (frontend only; this backend is API-only)
- **Scribe**: Auto-docs reduce manual maintenance; .scribe/ directory shows cached API metadata

---

## 6. Entry Points

### HTTP API Routes (routes/api.php)

#### Authentication (public):
```
POST   /login                          → login with email/password
```

#### All other routes protected with `middleware('auth:sanctum')`:

#### Patient Management:
```
GET    /patients                       → list all patients (with filters)
GET    /patients/{id}                  → single patient + all records
POST   /patients                       → create patient
PUT    /patients/{id}                  → update patient
DELETE /patients/{id}                  → archive patient

GET    /patients/{id}/odontogram-records       → dental charts
POST   /patients/{id}/odontogram-records       → save dental record
PUT    /patients/{id}/odontogram-records/{rid} → update tooth condition
DELETE /patients/{id}/odontogram-records/{rid} → remove record

GET    /patients/{id}/periodontal-records      → gum health
GET    /patients/{id}/orthodontic-records      → braces/aligners
GET    /patients/{id}/soft-tissue-records      → pathology
GET    /patients/{id}/images                   → photos
```

#### Scheduling & Billing:
```
GET/POST   /appointments               → appointments CRUD
PATCH      /appointments/{id}/status   → update status

GET/POST   /invoices                   → invoices CRUD
POST       /invoices/{id}/payments     → record payment
```

#### Staff & Org:
```
GET/POST   /employees                  → staff directory
```

#### Management Operations:
```
/management/payrolls                   → payroll CRUD
/management/attendance                 → mark attendance
/management/inventory                  → stock management
/management/lab-orders                 → external lab work
/management/leave-requests             → PTO requests
/management/staff-advances             → salary advances
/management/expenses                   → operational costs
/management/chart-data                 → revenue metrics
```

#### Admin:
```
GET    /settings/roles                 → all roles with permissions
PUT    /settings/roles/{id}            → update role permissions
GET    /roles                          → simple role list
```

### Web Routes (routes/web.php):
```
GET    /                               → welcome page (Laravel default)
```

### Command Line (routes/console.php):
```
[Reserved for artisan commands - migrations, seeding, etc.]
```

---

## 7. How It Achieves Its Goals

### Clinical Record Management:
- Uses **polymorphic-like pattern** without Laravel polymorphism:
  - Patient is the root; 6+ record types (DentalRecord, PeriodontalRecord, etc.) each link directly to Patient
  - Allows independent CRUD for each record type
  - **Non-obvious design**: Stores `occlusal_records` as JSON directly on Patient model rather than separate table (performance + simplicity tradeoff)

### Role-Based Access Control (RBAC):
- **Simple but manual approach**:
  ```php
  Employee->role_id → RolePermission->permissions (JSON array)
  // e.g., ['users.create', 'users.edit', 'appointments.manage']
  ```
- Middleware `CheckPermission` would enforce this (defined but not fully wired in routes)
- **Non-obvious choice**: Permissions stored as JSON strings, not normalized tables—simpler to manage but less queryable

### Multi-branch Isolation:
- Every major entity (`Patient`, `Appointment`, `Invoice`, `Employee`, etc.) has `branch_id` FK
- Filtering by branch is **application-level**, not database-level enforced
- **Assumption**: Logged-in user's branch_id guides their view (not currently enforced by middleware)

### Financial Tracking:
- Invoice → InvoiceTreatment (itemization) → PaymentTransaction (payment log)
- Tracks `amount` (total) vs `paid_amount` (received) to calculate outstanding balance
- Status enum (Pending/Paid/Overdue) is **not automatically updated**—relies on manual status updates

### Treatment Planning:
- TreatmentPlan decouples proposed care from invoicing
- Status (Proposed/Completed) allows tracking treatment progression
- Invoice references treatment via line items, not direct FK

---

## 8. Configuration & Environment

### .env Variables (.env.example):

```env
APP_NAME=Laravel
APP_ENV=local                    # development vs production
APP_DEBUG=true                   # error verbosity
APP_URL=http://localhost         # base URL for link generation

# Database
DB_CONNECTION=sqlite             # driver (sqlite default)
DB_DATABASE=database.sqlite      # SQLite file location
# (Commented: MySQL/PostgreSQL options available)

# Authentication
BCRYPT_ROUNDS=12                 # password hash cost

# Sessions
SESSION_DRIVER=database          # store in DB, not cookies
SESSION_LIFETIME=120             # 2 hours

# Cache
CACHE_STORE=database             # in-DB cache (not Redis)

# Queue
QUEUE_CONNECTION=database        # background jobs in DB

# Mail
MAIL_MAILER=log                  # dev: log to console (not SMTP)

# AWS/Storage (if needed)
FILESYSTEM_DISK=local            # local files, not S3

# Frontend (Vite)
VITE_APP_NAME="${APP_NAME}"
```

### Database Location:
- Default: `database/database.sqlite` (created on first migration)
- Can switch to MySQL/PostgreSQL by changing `DB_CONNECTION=mysql` and adding credentials

### Key Assumptions:
1. **Single Server**: No distributed setup; assumes monolithic deployment
2. **SQLite as Default**: Works for small-to-medium clinics; not tested at scale
3. **Application-Level Auth**: No database-level multi-tenancy; branch isolation is app logic
4. **No Caching Layer**: Uses DB cache; no Redis configured
5. **No Queue Workers**: Background jobs in DB queue; expects `php artisan queue:listen` running
6. **Stateless API**: Sanctum tokens; scalable across multiple servers if needed

### Setup Commands (from composer.json):
```bash
npm run dev          # Vite dev server + Laravel server + queue listener + logs
php artisan migrate  # Create/update database schema
npm run build        # Compile frontend assets
```

---

## 9. Summary: Key Design Characteristics

| Aspect | Choice | Rationale |
|---|---|---|
| **API Style** | RESTful JSON | Easy for frontend/mobile, language-agnostic |
| **Authentication** | Sanctum tokens | Stateless, no server-side session storage |
| **Database** | SQLite (upgradable) | Low setup friction for SMBs; can migrate later |
| **ORM** | Eloquent | Native Laravel; expressive syntax; auto relationships |
| **Branching** | App-level FK | Simple; works until enterprise multi-tenancy needed |
| **Permissions** | JSON in DB | Flexible; avoids permission table explosion |
| **Dental Records** | Separate models | Allows independent lifecycle management |
| **Financial Tracking** | Ledger style | Invoice + Transactions; simple audit trail |
| **Frontend** | Decoupled | Backend agnostic; could serve web, mobile, desktop |

---

## Conclusion

This is a **feature-rich, monolithic Laravel API** designed for **small-to-medium dental practices**. It prioritizes:
- **Rapid deployment** (SQLite, few external deps)
- **Ease of expansion** (Eloquent's flexibility, JSON fields)
- **Clinical domain accuracy** (rich patient/record models)

### Potential Future Improvements:
- Enforce multi-tenancy at DB layer (current: app logic)
- Add comprehensive permission middleware
- Implement soft deletes for audit compliance
- Cache frequently accessed data (e.g., patient lists)
- Add queue jobs for async operations (reminders, reports)
- API versioning strategy for backward compatibility
- Comprehensive logging and audit trail for compliance
- Real-time notifications (WebSocket integration)
- Advanced reporting and analytics dashboard
