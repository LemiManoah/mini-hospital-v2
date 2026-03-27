# Mini-Hospital v2

A multi-tenant hospital management system built with Laravel 12, Inertia.js v2, and React 19. Focused on outpatient (OPD) operations with a complete clinical workflow from patient registration through billing.

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4+
- **Frontend**: React 19, Inertia.js v2, TypeScript
- **Styling**: Tailwind CSS v4, shadcn/ui components
- **Database**: MySQL/PostgreSQL (multi-tenant with branch isolation)
- **Authentication**: Laravel Fortify
- **Permissions**: Spatie Permissions

## Features

### Implemented Modules

| Module                    | Status | Description                                             |
| ------------------------- | ------ | ------------------------------------------------------- |
| Authentication & Users    | ✅     | Login, password reset, 2FA, user management             |
| Roles & Permissions       | ✅     | Role-based access control                               |
| Multi-Tenant & Branches   | ✅     | Tenant management, branch switching                     |
| Foundation Data           | ✅     | Countries, currencies, allergens, units                 |
| Administration            | ✅     | Departments, staff, clinics, facility services, drugs   |
| Insurance Management      | ✅     | Insurance companies and packages (master data)          |
| Patient Management        | ✅     | Patient CRUD, allergies, MRN generation                 |
| Scheduling & Appointments | ✅     | Appointments, doctor schedules, queue management        |
| Patient Visits            | ✅     | Visit workflow, status transitions, payer snapshot      |
| Triage & Vital Signs      | ✅     | Triage assessment, vital signs capture                  |
| Consultation              | ✅     | SOAP notes, diagnoses, outcomes                         |
| Consultation Orders       | ✅     | Lab requests, imaging, prescriptions, facility services |
| Laboratory Workflow       | 🟡     | Test catalog, result entry, workflow stages             |
| Billing & Payments        | 🟡     | Visit billing, charges, payment recording               |
| Dashboard                 | ✅     | Real-time metrics and overview                          |

### Architecture

- **Action-Based Pattern**: Business logic encapsulated in single-action classes
- **Multi-Tenant**: Global tenant scope with branch isolation
- **Event-Driven**: Designed for automatic charge generation from orders

## Getting Started

### Prerequisites

- PHP 8.4+
- Node.js 18+
- Composer
- Bun (recommended) or npm
- MySQL or PostgreSQL

### Installation

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
bun install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Start development server
bun run dev
```

### Development Commands

```bash
# Start development server (concurrent)
composer dev

# Run tests
composer test

# Code formatting
composer lint

# Type checking
composer test:types
```

## Project Structure

```
app/
├── Actions/              # Single-action classes
├── Enums/                # Enum definitions
├── Http/
│   ├── Controllers/      # HTTP controllers
│   └── Middleware/       # Custom middleware
├── Models/               # Eloquent models
├── Services/             # Service classes
│   └── Billing/          # Billing service
└── Support/              # Support classes
resources/
├── js/
│   ├── components/       # React components
│   ├── layouts/           # Layout components
│   ├── pages/            # Page components
│   └── routes/           # Wayfinder route definitions
routes/
├── api.php               # API routes
├── console.php           # Console routes
└── web.php               # Web routes
database/
├── factories/            # Model factories
├── migrations/           # Database migrations
└── seeders/              # Database seeders
```

## Key Models

- `Patient` - Patient records with demographics
- `PatientVisit` - Visit records linked to patients
- `Consultation` - Clinical consultation notes
- `LabRequest` / `LabRequestItem` - Laboratory orders
- `ImagingRequest` - Imaging study orders
- `Prescription` - Medication prescriptions
- `FacilityServiceOrder` - Service/procedure orders
- `VisitBilling` - Visit billing summary
- `VisitCharge` - Billable charge lines

## Key Enums

- `VisitStatus` - Visit workflow states
- `AppointmentStatus` - Appointment states
- `BillingStatus` - Billing settlement states
- `PayerType` - Cash/Insurance/Sponsor
- `LabRequestStatus` - Lab workflow states
- `VisitChargeStatus` - Charge lifecycle states

## Documentation

- [Billing Schema](./billing-schema.md) - Billing foundation design
- [System Overview](./system.md) - Complete module documentation

## License

MIT License
