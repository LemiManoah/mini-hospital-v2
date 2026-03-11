# Hospital Management System - Database Schema Documentation

## Table of Contents

- [Overview](#overview)
- [Core Modules](#core-modules)
    - [Patient Management](#patient-management)
    - [Administration & Security](#administration--security)
    - [Scheduling](#scheduling)
    - [Clinical Operations](#clinical-operations)
    - [Laboratory](#laboratory)
    - [Radiology](#radiology)
    - [Pharmacy](#pharmacy)
    - [Inpatient (IPD)](#inpatient-ipd)
    - [Billing & Finance](#billing--finance)
- [Enums & Constants](#enums--constants)
- [Indexes & Performance](#indexes--performance)

---

## Overview

This schema follows **Laravel 12+** conventions with:

- **UUID Primary Keys** for distributed system compatibility
- **Soft Deletes** for regulatory compliance (HIPAA)
- **Audit Trails** on all clinical tables
- **Temporal Data Tracking** for historical records
- **JSON Columns** for flexible attributes
- **Full Text Search** support for clinical notes

### Naming Conventions

- Table names: `snake_case` plural (e.g., `patient_visits`)
- Pivot tables: `table1_table2` alphabetical (e.g., `medication_allergy`)
- Enum columns: `status`, `type`, `category` suffixes
- Foreign keys: `{$table}_id` (e.g., `patient_id`)
- Timestamp fields: `created_at`, `updated_at`, `deleted_at`

### Multi-tenant Architecture Notes

- **Global Scopes**: The application should use Laravel Global Scopes (e.g., `TenantScope` and `BranchScope`) on all eloquent models to automatically append `where tenant_id = ?` to every query, preventing cross-tenant data leaks.
- **Compound Unique Constraints**: Entities that traditionally have globally unique identifiers (MRNs, invoice numbers, employee numbers, test codes) use compound unique constraints (`tenant_id`, `identifier`) to allow multiple independent physical hospitals/tenants to use the same internal ID sequences without conflict.

---

## Core Modules

### Tenant & Branch Management

#### tenants

Represents the overarching hospital group or organization.

```php
Schema::create('tenants', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 100)->unique();
    $table->string('domain', 100)->unique()->nullable()->comment('For custom subdomains');
    $table->boolean('has_branches')->default(false);
    $table->string('logo')->nullable()->default(null);
    $table->string('stamp')->nullable()->default(null);
    $table->foreignIdFor(SubscriptionPackage::class);
    $table->enum('status', GeneralStatus::class)->default(GeneralStatus::ACTIVE);
    $table->foreignUuid('country')->nullable()->constrained('countries')->nullOnDelete();
    $table->foreignUuid('address')->nullable()->constrained('addresses')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
});
```

### SubscriptionPackage

Subscription package options for all clients

```php
     Schema::create('subscription_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique()->default(null);
                $table->integer('users')->unique()->default(1);
                $table->string('price')->nullable()->default(0);
                $table->enum('status', GeneralStatus::class)->default(GeneralStatus::ACTIVE);
                $table->timestamps();
            });
            SubscriptionPackage::create(['name' => 'Starter Package', 'users' => '2', 'price'=>'2000000']);
            SubscriptionPackage::create(['name' => 'Standard Package', 'users' => '4', 'price'=>'2000000']);
            SubscriptionPackage::create(['name' => 'Platinum Package', 'users' => '6', 'price'=>'2000000']);
            SubscriptionPackage::create(['name' => 'Professional Package', 'users' => '8', 'price'=>'2000000']);
            SubscriptionPackage::create(['name' => 'Advanced Package', 'users' => '10', 'price'=>'2000000']);
            SubscriptionPackage::create(['name' => 'Ultimate Package', 'users' => '12', 'price'=>'2000000']);
            SubscriptionPackage::create(['name' => 'Extreme Package', 'users' => '20', 'price'=>'2000000']);
    }
```

#### facility branches

Represents physical locations/facilities belonging to a tenant.

```php
Schema::create('facility_branches', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->nullable()->default(null)->index();
    $table->foreignUuid('address_id')->constrained('addresses')->nullOnDelete();
    $table->string('main_contact')->nullable()->default(null)->index();
    $table->string('other_contact')->nullable()->default(null);
    $table->string('email')->nullable()->default(null)->index();
    $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->foreignUuid('currency_id')->constrained('currencies')->onDelete('cascade');
    $table->enum('status', GeneralStatus::class)->default(GeneralStatus::ACTIVE);
    $table->boolean('is_main_branch')->default(false);
    $table->boolean('has_store')->default(false);
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->unique(['tenant_id', 'branch_code']);
    $table->index(['tenant_id', 'is_active']);
});
```

---

### Insurance master data

### insurance companies

```php
public function up(): void
{
    Schema::create('insurance_companies', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
        $table->string('name', 150)->index();
        $table->string('email')->nullable()->index();
        $table->string('main_contact', 20)->nullable()->index();
        $table->string('other_contact', 20)->nullable();
        $table->string('address')->nullable();
        $table->enum('status', GeneralStatus::class)->default(GeneralStatus::ACTIVE)->index();
        $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();

        $table->unique(['tenant_id', 'name']);
    });
}
```

### insurance packages

```php
public function up(): void
{
    Schema::create('insurance_packages', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
        $table->foreignUuid('insurance_company_id')->constrained('insurance_companies')->onDelete('cascade');
        $table->string('name', 150)->index();
        $table->enum('status', GeneralStatus::class)->default(GeneralStatus::ACTIVE)->index();
        $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
        $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();

        $table->unique(['tenant_id', 'insurance_company_id', 'name']);
    });
}
```

### insurance company invoices

```php
public function up(): void
{
    Schema::create('insurance_company_invoices', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
        $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
        $table->foreignUuid('insurance_company_id')->constrained('insurance_companies')->onDelete('cascade');
        $table->string('code', 30)->index();
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->date('due_date')->nullable();
        $table->decimal('bill_amount', 14, 2)->default(0);
        $table->decimal('paid_amount', 14, 2)->default(0);
        $table->enum('status', BillingStatus::class)->default(BillingStatus::PENDING)->index();
        $table->boolean('is_printed')->default(false);
        $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();

        $table->unique(['tenant_id', 'code']);
    });
}
```

### insurance package invoice payments

```php
public function up(): void
{
    Schema::create('insurance_company_invoice_payments', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
        $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
        $table->foreignUuid('insurance_company_invoice_id')->constrained('insurance_company_invoices')->onDelete('cascade');
        $table->date('payment_date');
        $table->string('receipt', 100)->nullable()->index();
        $table->decimal('paid_amount', 14, 2)->default(0);
        $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();
    });
}
```

### billable item types (enum)

Use one enum across insurance pricing:

- `service`
- `drug`
- `test`
- `imaging`
- `procedure`
- `bed_day`
- `other`

### insurance package prices (unified abstraction)

```php
public function up(): void
{
    Schema::create('insurance_package_prices', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
        $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
        $table->foreignUuid('insurance_package_id')->constrained('insurance_packages')->onDelete('cascade');

        // Generic billable item pointer
        $table->enum('billable_type', BillableItemType::class)->index();
        $table->uuid('billable_id')->index(); // references the UUID of the item in its source table

        $table->decimal('price', 14, 2)->default(0);
        $table->date('effective_from')->nullable()->index();
        $table->date('effective_to')->nullable()->index();
        $table->enum('status', GeneralStatus::class)->default(GeneralStatus::ACTIVE)->index();
        $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();

        // Versioned pricing: allow multiple versions by effective_from
        $table->unique(
            ['tenant_id', 'facility_branch_id', 'insurance_package_id', 'billable_type', 'billable_id', 'effective_from'],
            'ipp_unique_item_version'
        );
        $table->index(
            ['tenant_id', 'facility_branch_id', 'insurance_package_id', 'billable_type', 'billable_id', 'status'],
            'ipp_lookup_idx'
        );

        // enforce in app layer: effective_to is null or >= effective_from
        // enforce in app layer: no overlapping effective date ranges for the same tenant/branch/package/item
    });
}
```

### billable pointer integrity (important)

Because `billable_id` points to multiple possible tables, DB-level FK cannot be enforced directly.
Use one of these:

1. Application-level validator in request/service layer:
    - `billable_type=service` => `billable_id` must exist in `facility_services`
    - `billable_type=drug` => `billable_id` must exist in `inventory_items`
    - `billable_type=test` => `billable_id` must exist in `facility_tests`
2. Or introduce a materialized `billable_items` table and point `insurance_package_prices.billable_item_id` to it.

### migration strategy from old structure

If legacy tables exist (`insurance_package_services`, `insurance_package_drugs`, `insurance_package_tests`):

1. Create `insurance_package_prices`.
2. Backfill:
    - services -> `billable_type='service'`
    - drugs -> `billable_type='drug'`
    - tests -> `billable_type='test'`
3. Switch code reads/writes to unified table.
4. Archive/drop old tables in a later migration.

### Notes for production correctness

- Use `status = inactive` for master data retirement; avoid deleting rows referenced by historical invoices.
- Keep `softDeletes()` only for reversible admin mistakes, not routine deactivation.
- For versioned pricing, always freeze applied amounts on charge rows (`unit_price_applied`) to preserve invoice integrity.
- Unified model selected: use `insurance_package_prices` as the single source of package pricing.

### Patient Management

#### patients

Core demographic information. Uses UUID for cross-system integration.

```php
Schema::create('patients', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->string('patient_number', 50)->comment('Hospital MRN');
    $table->string('first_name', 100);
    $table->string('last_name', 100);
    $table->string('middle_name', 100)->nullable();
    $table->date('date_of_birth')->nullable();
    $table->integer('age')->nullable();
    $table->enum('age_units', ['year', 'month', 'day'])->nullable();
    $table->string('gender', 10);
    $table->string('email', 255)->nullable()->unique();
    $table->string('phone_number', 20)->index();
    $table->string('alternative_phone', 20)->nullable();
    $table->string('next_of_kin_name', 100)->nullable();
    $table->string('next_of_kin_phone', 20)->nullable();
    $table->string('next_of_kin_relationship', 50)->nullable();
    $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
    $table->string('marital_status', 50)->nullable();
    $table->string('occupation', 100)->nullable();
    $table->string('religion', 50)->nullable();
    $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
    $table->string('blood_group', 10)->nullable();
    $table->boolean('is_organ_donor')->default(false);

    // Audit & Soft Delete
    $table->timestamps();
    $table->softDeletes();
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();

    // Indexes
    $table->index(['last_name', 'first_name']);
    $table->unique(['tenant_id', 'patient_number']);
    $table->index('patient_number');
    $table->index('phone_number');
    $table->index('date_of_birth');
    $table->index('created_at');
});
```

#### countries

```php
Schema::create('countries', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('country_name', 100)->unique();
    $table->string('country_code', 10)->unique();
    $table->string('dial_code', 10);
    $table->string('currency', 10);
    $table->string('currency_symbol', 10);
    $table->timestamps();
});
```

#### Addresses

```php
Schema::create('addresses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('city', 100)->index();
    $table->string('district', 100)->nullable()->index();
    $table->string('state', 100)->nullable();
    $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
    $table->softDeletes();
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->timestamps();
});
```

#### allergens

```php
Schema::create('allergens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 100)->index();
    $table->text('description')->nullable();
    $table->enum('type', [AllergyType::class]);
    $table->softDeletes();
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->timestamps();

    $table->index('name');
    $table->index('type');
});
```

#### patient_allergies

Comprehensive allergy profile with reactions.

```php
Schema::create('patient_allergies', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('patient_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('allergen_id')->constrained('allergens')->onDelete('cascade');
    $table->enum('severity', [AllergySeverity::class]);
    $table->enum('reaction', [AllergyReaction::class]);
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->softDeletes();
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->timestamps();

    $table->index(['patient_id', 'allergen_id']);
    $table->index('severity');
});
```

#### past_medical_histories

Chronic conditions and previous diagnoses.

```php
Schema::create('past_medical_histories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('patient_id')->constrained()->onDelete('cascade');
    $table->string('condition', 255);
    $table->string('icd10_code', 10)->nullable()->index();
    $table->date('onset_date')->nullable();
    $table->date('resolution_date')->nullable();
    $table->boolean('is_ongoing')->default(true);
    $table->text('treatment_notes')->nullable();
    $table->string('surgeon_physician', 100)->nullable();
    $table->softDeletes();
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->timestamps();
});
```

#### currencies

```php
     Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('code')->index();
                $table->string('name');
                $table->string('symbol');
                $table->boolean('modifiable')->default(true);
                $table->timestamps();
            });
            Currency::create(['name'=>'Botswana Pula', 'code'=>'BWP', 'modifiable'=>false, 'symbol'=>'P']);
            Currency::create(['name'=>'CFA Francs', 'code'=>'XOF', 'modifiable'=>false, 'symbol'=>'CFA']);
            Currency::create(['name'=>'Egyptian Pounds', 'code'=>'EGP', 'modifiable'=>false, 'symbol'=>'e£']);
            Currency::create(['name'=>'Ghana Cedi', 'code'=>'GHS', 'modifiable'=>false, 'symbol'=>'GH¢']);
            Currency::create(['name'=>'Kenyan Shillings', 'code'=>'KES', 'modifiable'=>false, 'symbol'=>'KSh']);
            Currency::create(['name'=>'Malawian Kwachas', 'code'=>'MWK', 'modifiable'=>false, 'symbol'=>'MK']);
            Currency::create(['name'=>'Mauritian Rupees', 'code'=>'MUR', 'modifiable'=>false, 'symbol'=>'₨']);
            Currency::create(['name'=>'Moroccan Dirhams', 'code'=>'MAD', 'modifiable'=>false, 'symbol'=>'MAD']);
            Currency::create(['name'=>'Namibian Dollars', 'code'=>'NAD', 'modifiable'=>false, 'symbol'=>'N$']);
            Currency::create(['name'=>'Nigerian Nairas', 'code'=>'NGN', 'modifiable'=>false, 'symbol'=>'₦']);
            Currency::create(['name'=>'Rwandan Francs', 'code'=>'RWF', 'modifiable'=>false, 'symbol'=>'R₣']);
            Currency::create(['name'=>'South African Rands', 'code'=>'ZAR', 'modifiable'=>false, 'symbol'=>'R']);
            Currency::create(['name'=>'Tanzanian Shillings', 'code'=>'TZS', 'modifiable'=>false, 'symbol'=>'TSh']);
            Currency::create(['name'=>'Tunisian Dinars', 'code'=>'TND', 'modifiable'=>false, 'symbol'=>'د.ت']);
            Currency::create(['name'=>'Ugandan Shillings', 'code'=>'UGX', 'modifiable'=>false, 'symbol'=>'USh']);
            Currency::create(['name'=>'Zambian Kwacha', 'code'=>'ZMW', 'modifiable'=>false, 'symbol'=>'ZK']);
            Currency::create(['name'=>'Zimbabwean RTGS', 'code'=>'ZWL', 'modifiable'=>false, 'symbol'=>'$']);
            Currency::create(['name'=>'United States Dollars', 'code'=>'USD', 'modifiable'=>false, 'symbol'=>'$']);
            Currency::create(['name'=>'South Sudan Dollars', 'code'=>'SSD', 'modifiable'=>false, 'symbol'=>'£']);
        }
```

---

### Administration & Security

#### staff

Healthcare providers and administrative staff.

```php
Schema::create('staff', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->string('employee_number', 50);
    $table->string('first_name', 100);
    $table->string('last_name', 100);
    $table->string('email', 255);
    $table->string('phone', 20)->nullable();
    $table->string('password_hash'); // bcrypt
    $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
    $table->rememberToken();
    $table->foreignUuid('department_id')->nullable()->constrained();
    // note: roles and permissions are handled via spatie/laravel-permission
    $table->string('license_number', 100)->nullable()->unique();
    $table->string('specialty', 100)->nullable();
    $table->date('hire_date');
    $table->date('termination_date')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->string('last_login_ip', 45)->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['tenant_id', 'employee_number']);
    $table->unique(['tenant_id', 'email']);
    $table->index('is_active');
    $table->index('department_id');
});
```

#### staff_branches

Pivot table to handle floating staff members across branches.

```php
Schema::create('staff_branches', function (Blueprint $table) {
    $table->foreignUuid('staff_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('branch_id')->constrained()->onDelete('cascade');
    $table->boolean('is_primary_location')->default(false);

    $table->primary(['staff_id', 'branch_id']);
});
```

#### departments

Hospital departments and units.

```php
Schema::create('departments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->string('department_code', 20);
    $table->string('department_name', 100);
    $table->string('location', 100)->nullable();
    $table->foreignUuid('head_of_department_id')->nullable()->constrained('staff')->nullOnDelete();
    $table->boolean('is_clinical')->default(true);
    $table->boolean('is_active')->default(true);
    $table->json('contact_info')->nullable();
    $table->timestamps();

    $table->unique(['tenant_id', 'department_code']);
});
```

#### clinics

Outpatient clinics within departments.

```php
Schema::create('clinics', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->string('clinic_code', 20);
    $table->string('clinic_name', 100);
    $table->foreignUuid('department_id')->constrained();
    $table->foreignUuid('address_id')->constrained();
    $table->string('phone', 20)->nullable();
    $table->integer('daily_capacity')->default(50);
    $table->boolean('accepts_walk_ins')->default(true);
    $table->enum('status', GENERALSTATUS::class)->default(GENERALSTATUS::ACTIVE);
    $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->unique(['tenant_id', 'clinic_code']);
});
```

#### audit_logs

HIPAA-compliant audit trail.

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('table_name', 50)->index();
    $table->uuid('record_id')->index();
    $table->enum('action', ['create', 'update', 'delete', 'view', 'export', 'print']);
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->foreignUuid('user_id')->nullable()->constrained('staff');
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 255)->nullable();
    $table->string('session_id', 100)->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['table_name', 'record_id']);
    $table->index('created_at');
});
```

#### system_settings

Application settings and constants.

```php
Schema::create('general_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignIdFor(Facility::class);
    $table->enum('service_and_price',['bill','bill_services','bill_service_price'])->default('bill_service_price');
    $table->enum('report_after_payment',['Yes','No'])->default('No');
    $table->enum('pay_before_doctor',['No','Yes'])->default('No');
    $table->enum('diagnosis_type',['ICD','Entry'])->default('ICD');
    $table->enum('skip_sample_picking',['Yes','No'])->default('No');
    $table->enum('skip_result_review',['Yes','No'])->default('Yes');
    $table->enum('notification_channel',['sms','whatsapp','both','none'])->default('sms');
    $table->timestamps();
});
```

---

### Scheduling

#### schedules

Doctor clinic schedules.

```php
Schema::create('schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('doctor_id')->constrained('staff');
    $table->foreignUuid('clinic_id')->constrained();
    $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
    $table->time('start_time');
    $table->time('end_time');
    $table->integer('slot_duration_minutes')->default(15);
    $table->integer('max_patients')->default(20);
    $table->date('valid_from');
    $table->date('valid_to')->nullable();
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['doctor_id', 'clinic_id', 'day_of_week', 'start_time'], 'unique_schedule');
});
```

#### appointments

Patient appointments with queue management.

```php
Schema::create('appointments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('patient_id')->constrained();
    $table->foreignUuid('doctor_id')->constrained('staff');
    $table->foreignUuid('clinic_id')->constrained();
    $table->date('appointment_date');
    $table->time('start_time');
    $table->time('end_time')->nullable();
    $table->enum('status', [
        'scheduled', 'confirmed', 'checked_in', 'in_progress',
        'completed', 'no_show', 'cancelled', 'rescheduled'
    ])->default('scheduled')->index();
    $table->text('reason_for_visit');
    $table->string('chief_complaint', 255)->nullable();
    $table->boolean('is_walk_in')->default(false);
    $table->integer('queue_number')->nullable();
    $table->timestamp('checked_in_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('cancellation_reason')->nullable();
    $table->foreignUuid('cancelled_by')->nullable()->constrained('staff');
    $table->timestamps();

    $table->index(['appointment_date', 'status']);
    $table->index(['doctor_id', 'appointment_date']);
});
```

---

### Clinical Operations

#### patient_visits

The central encounter record. All clinical activities link here.

```php
Schema::create('patient_visits', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->string('visit_number', 50); // ENC-YYYYMMDD-XXXX
    $table->foreignUuid('patient_id')->constrained();
    $table->date('visit_date');
    $table->time('visit_time');
    $table->enum('visit_type', [
        'opd_consultation', 'emergency', 'day_care',
        'follow_up', 'procedure', 'telemedicine'
    ])->index();
    $table->enum('status', [
        'registered', 'triaged', 'waiting_consultation', 'in_consultation',
        'waiting_lab', 'waiting_imaging', 'waiting_pharmacy',
        'admitted', 'discharged', 'cancelled'
    ])->default('registered')->index();
    $table->foreignUuid('clinic_id')->nullable()->constrained();
    $table->foreignUuid('doctor_id')->nullable()->constrained('staff');
    $table->foreignUuid('appointment_id')->nullable()->constrained();
    $table->boolean('is_emergency')->default(false)->index();
    $table->text('cancellation_reason')->nullable();
    $table->timestamp('closed_at')->nullable();
    $table->foreignUuid('closed_by')->nullable()->constrained('staff');
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['tenant_id', 'visit_number']);
    $table->index(['patient_id', 'visit_date']);
    $table->index('visit_number');
    $table->index('is_emergency');
});
```

#### triage_records

Emergency and outpatient triage assessment.

```php
Schema::create('triage_records', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('visit_id')->constrained()->unique(); // One triage per visit
    $table->foreignUuid('nurse_id')->constrained('staff');
    $table->timestamp('triage_datetime')->useCurrent();
    $table->enum('triage_grade', ['red', 'yellow', 'green', 'black'])->comment('Red=Emergency, Yellow=Priority, Green=Routine');
    $table->enum('attendance_type', ['new', 're_attendance', 'referral']);
    $table->integer('news_score')->nullable()->comment('National Early Warning Score');
    $table->integer('pews_score')->nullable()->comment('Pediatric Early Warning Score');
    $table->enum('conscious_level', ['alert', 'voice', 'pain', 'unresponsive']);
    $table->enum('mobility_status', ['independent', 'assisted', 'wheelchair', 'stretcher']);
    $table->text('chief_complaint');
    $table->text('history_of_presenting_illness')->nullable();
    $table->foreignUuid('assigned_clinic_id')->nullable()->constrained('clinics');
    $table->boolean('requires_priority')->default(false);
    $table->boolean('is_pediatric')->default(false);
    $table->boolean('poisoning_case')->default(false);
    $table->string('poisoning_agent', 100)->nullable();
    $table->boolean('snake_bite_case')->default(false);
    $table->string('referred_by', 100)->nullable();
    $table->text('nurse_notes')->nullable();
    $table->timestamps();

    $table->index('triage_grade');
    $table->index(['poisoning_case', 'snake_bite_case']);
});
```

#### vital_signs

Comprehensive vital measurements.

```php
Schema::create('vital_signs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('triage_id')->nullable()->constrained();
    $table->foreignUuid('admission_id')->nullable()->constrained('ipd_admissions');
    $table->timestamp('recorded_at')->useCurrent();
    $table->decimal('temperature', 4, 1)->nullable();
    $table->enum('temperature_unit', ['celsius', 'fahrenheit'])->default('celsius');
    $table->integer('pulse_rate')->nullable();
    $table->integer('respiratory_rate')->nullable();
    $table->integer('systolic_bp')->nullable();
    $table->integer('diastolic_bp')->nullable();
    $table->integer('map')->nullable()->comment('Mean Arterial Pressure');
    $table->decimal('oxygen_saturation', 5, 2)->nullable();
    $table->boolean('on_supplemental_oxygen')->default(false);
    $table->string('oxygen_delivery_method', 50)->nullable();
    $table->decimal('oxygen_flow_rate', 4, 1)->nullable();
    $table->decimal('blood_glucose', 5, 2)->nullable();
    $table->enum('blood_glucose_unit', ['mg_dl', 'mmol_l'])->default('mg_dl');
    $table->integer('pain_score')->nullable()->comment('0-10 scale');
    $table->decimal('height_cm', 5, 2)->nullable();
    $table->decimal('weight_kg', 5, 2)->nullable();
    $table->decimal('bmi', 4, 1)->nullable()->storedAs('(weight_kg / ((height_cm/100) * (height_cm/100)))');
    $table->decimal('head_circumference_cm', 5, 2)->nullable();
    $table->decimal('chest_circumference_cm', 5, 2)->nullable();
    $table->decimal('muac_cm', 5, 2)->nullable()->comment('Mid-Upper Arm Circumference');
    $table->string('capillary_refill', 10)->nullable();
    $table->foreignUuid('recorded_by')->constrained('staff');
    $table->timestamps();

    $table->index(['pulse_rate', 'systolic_bp']);
    $table->index('recorded_at');
});
```

#### consultations

Clinical encounter documentation (SOAP format).

```php
Schema::create('consultations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('visit_id')->constrained()->unique();
    $table->foreignUuid('doctor_id')->constrained('staff');
    $table->timestamp('started_at')->useCurrent();
    $table->timestamp('completed_at')->nullable();
    $table->string('chief_complaint', 500);
    $table->text('history_of_present_illness')->nullable();
    $table->text('review_of_systems')->nullable();
    $table->text('past_medical_history_summary')->nullable();
    $table->text('family_history')->nullable();
    $table->text('social_history')->nullable();
    $table->string('subjective_notes', 1000)->nullable();
    $table->text('objective_findings')->nullable();
    $table->text('assessment')->nullable();
    $table->text('plan')->nullable();
    $table->string('primary_diagnosis', 255)->nullable();
    $table->string('primary_icd10_code', 10)->nullable()->index();
    $table->json('secondary_diagnoses')->nullable(); // Array of {code, description}
    $table->enum('outcome', [
        'discharged', 'admitted', 'referred', 'follow_up_required',
        'transferred', 'deceased', 'left_against_advice'
    ])->nullable();
    $table->text('follow_up_instructions')->nullable();
    $table->integer('follow_up_days')->nullable();
    $table->boolean('is_referred')->default(false);
    $table->string('referred_to_department', 100)->nullable();
    $table->string('referred_to_facility', 100)->nullable();
    $table->text('referral_reason')->nullable();
    $table->timestamps();

    $table->fullText(['chief_complaint', 'assessment']);
});
```

---

### Laboratory

#### lab_test_catalogs

Master list of available tests.

```php
Schema::create('lab_test_catalogs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->string('test_code', 20);
    $table->string('test_name', 200);
    $table->string('category', 50)->index(); // Hematology, Chemistry, Microbiology
    $table->string('sub_category', 50)->nullable();
    $table->foreignUuid('department_id')->constrained();
    $table->string('specimen_type', 50); // Blood, Urine, CSF, etc.
    $table->string('container_type', 50)->nullable();
    $table->decimal('volume_required_ml', 5, 2)->nullable();
    $table->string('storage_requirements', 100)->nullable();
    $table->integer('turnaround_time_minutes')->nullable();
    $table->decimal('base_price', 10, 2);
    $table->boolean('requires_fasting')->default(false);
    $table->json('reference_ranges')->nullable(); // Age/sex specific ranges
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['tenant_id', 'test_code']);
});
```

#### lab_requests

Test orders from clinicians.

```php
Schema::create('lab_requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('visit_id')->constrained();
    $table->foreignUuid('consultation_id')->nullable()->constrained();
    $table->foreignUuid('requested_by')->constrained('staff');
    $table->timestamp('request_date')->useCurrent();
    $table->text('clinical_notes')->nullable();
    $table->enum('priority', ['routine', 'urgent', 'stat', 'critical'])->default('routine');
    $table->enum('status', [
        'requested', 'sample_collected', 'in_progress',
        'completed', 'cancelled', 'rejected'
    ])->default('requested')->index();
    $table->string('diagnosis_code', 10)->nullable();
    $table->boolean('is_stat')->default(false);
    $table->enum('billing_status', ['pending', 'billed', 'paid', 'insurance'])->default('pending');
    $table->text('cancellation_reason')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['status', 'priority']);
    $table->index('request_date');
});
```

#### lab_request_items

Individual tests within a request.

```php
Schema::create('lab_request_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('request_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('test_id')->constrained('lab_test_catalogs');
    $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
    $table->decimal('price', 10, 2);
    $table->boolean('is_external')->default(false);
    $table->string('external_lab_name', 100)->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->unique(['request_id', 'test_id']); // Prevent duplicate tests
});
```

#### lab_specimens

Specimen tracking chain of custody.

```php
Schema::create('lab_specimens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('request_id')->constrained();
    $table->string('specimen_number', 50)->unique();
    $table->enum('specimen_type', ['blood', 'urine', 'csf', 'tissue', 'swab', 'sputum', 'other']);
    $table->timestamp('collected_at');
    $table->foreignUuid('collected_by')->constrained('staff');
    $table->string('collection_site', 50)->nullable(); // e.g., "Left arm"
    $table->timestamp('received_at')->nullable();
    $table->foreignUuid('received_by')->nullable()->constrained('staff');
    $table->enum('status', ['collected', 'received', 'processing', 'stored', 'discarded', 'rejected'])->default('collected');
    $table->string('rejection_reason', 255)->nullable();
    $table->string('storage_location', 50)->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    $table->index('specimen_number');
    $table->index('status');
});
```

#### lab_results

Actual test results with critical value flags.

```php
Schema::create('lab_results', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('specimen_id')->constrained();
    $table->foreignUuid('test_id')->constrained('lab_test_catalogs');
    $table->foreignUuid('patient_id')->constrained();
    $table->string('parameter_name', 100)->nullable(); // For multi-parameter tests
    $table->string('result_value', 100); // Stored as string to handle "<0.1", "Negative", etc.
    $table->string('unit', 20)->nullable();
    $table->string('reference_range_low', 50)->nullable();
    $table->string('reference_range_high', 50)->nullable();
    $table->boolean('is_abnormal')->default(false);
    $table->boolean('is_critical')->default(false)->index();
    $table->enum('criticality', ['low', 'high', 'critical', 'alert'])->nullable();
    $table->timestamp('resulted_at');
    $table->foreignUuid('resulted_by')->constrained('staff');
    $table->timestamp('verified_at')->nullable();
    $table->foreignUuid('verified_by')->nullable()->constrained('staff');
    $table->boolean('is_final')->default(false);
    $table->boolean('noted_by_clinician')->default(false);
    $table->timestamp('clinician_noted_at')->nullable();
    $table->string('method_used', 50)->nullable();
    $table->string('instrument_id', 50)->nullable();
    $table->text('comments')->nullable();
    $table->timestamps();

    $table->index(['patient_id', 'resulted_at']);
    $table->index('is_critical');
});
```

---

### Radiology

#### imaging_requests

Radiology order management.

```php
Schema::create('imaging_requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visit_id')->constrained();
    $table->foreignUuid('consultation_id')->nullable()->constrained();
    $table->foreignUuid('requested_by')->constrained('staff');
    $table->enum('modality', ['xray', 'ct', 'mri', 'ultrasound', 'mammography', 'fluoroscopy', 'pet_ct']);
    $table->string('body_part', 100);
    $table->enum('laterality', ['left', 'right', 'bilateral', 'na'])->default('na');
    $table->text('clinical_history');
    $table->text('indication');
    $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
    $table->enum('status', ['requested', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('requested');
    $table->timestamp('scheduled_date')->nullable();
    $table->foreignUuid('scheduled_by')->nullable()->constrained('staff');
    $table->boolean('requires_contrast')->default(false);
    $table->string('contrast_allergy_status', 50)->nullable();
    $table->enum('pregnancy_status', ['unknown', 'not_pregnant', 'pregnant', 'possible'])->default('unknown');
    $table->decimal('radiation_dose_msv', 8, 3)->nullable();
    $table->timestamps();

    $table->index(['modality', 'status']);
    $table->index('scheduled_date');
});
```

#### imaging_studies

Performed studies with DICOM metadata.

```php
Schema::create('imaging_studies', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('request_id')->constrained()->unique();
    $table->string('accession_number', 50)->unique();
    $table->string('dicom_study_uid', 100)->unique()->nullable();
    $table->timestamp('study_datetime');
    $table->foreignUuid('technician_id')->constrained('staff');
    $table->string('study_description', 255)->nullable();
    $table->integer('number_of_images')->default(0);
    $table->string('storage_path', 500)->nullable();
    $table->string('pacs_url', 500)->nullable();
    $table->decimal('radiation_dose', 8, 3)->nullable();
    $table->text('technique')->nullable();
    $table->text('comparison_study')->nullable();
    $table->timestamps();
});
```

#### radiology_reports

Structured reporting with critical findings.

```php
Schema::create('radiology_reports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('study_id')->constrained();
    $table->foreignUuid('radiologist_id')->constrained('staff');
    $table->text('findings');
    $table->text('impression');
    $table->text('recommendations')->nullable();
    $table->enum('critical_finding', ['none', 'critical', 'urgent', 'incidental'])->default('none');
    $table->timestamp('dictated_at');
    $table->timestamp('verified_at')->nullable();
    $table->foreignUuid('verified_by')->nullable()->constrained('staff');
    $table->boolean('is_verified')->default(false);
    $table->boolean('noted_by_clinician')->default(false);
    $table->timestamp(' communicated_at')->nullable()->comment('Critical finding notification');
    $table->text('addendum')->nullable();
    $table->timestamps();

    $table->fullText(['findings', 'impression']);
    $table->index('critical_finding');
});
```

---

### Pharmacy

#### medication_catalogs

Drug formulary management.

```php
Schema::create('medication_catalogs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->string('generic_name', 200)->index();
    $table->string('brand_name', 200)->nullable();
    $table->string('drug_code', 50);
    $table->enum('category', ['tablet', 'capsule', 'injection', 'syrup', 'ointment', 'inhaler', 'infusion', 'other']);
    $table->string('dosage_form', 50);
    $table->string('strength', 50); // e.g., "500mg"
    $table->string('unit', 20); // mg, ml, g, etc.
    $table->string('manufacturer', 100)->nullable();
    $table->boolean('is_controlled')->default(false)->index();
    $table->string('schedule_class', 10)->nullable()->comment('CDSA schedule');
    $table->json('therapeutic_classes')->nullable();
    $table->text('contraindications')->nullable();
    $table->text('interactions')->nullable();
    $table->text('side_effects')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['tenant_id', 'drug_code']);
});
```

#### prescriptions

Medication orders.

```php
Schema::create('prescriptions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visit_id')->constrained();
    $table->foreignUuid('consultation_id')->constrained();
    $table->foreignUuid('prescribed_by')->constrained('staff');
    $table->timestamp('prescription_date')->useCurrent();
    $table->boolean('is_discharge_medication')->default(false);
    $table->boolean('is_long_term')->default(false);
    $table->string('primary_diagnosis', 255)->nullable();
    $table->text('pharmacy_notes')->nullable();
    $table->enum('status', ['pending', 'partially_dispensed', 'fully_dispensed', 'cancelled'])->default('pending');
    $table->timestamps();
});
```

#### prescription_items

Individual line items.

```php
Schema::create('prescription_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('prescription_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('medication_id')->constrained('medication_catalogs');
    $table->string('dosage', 50); // e.g., "1 Tablet"
    $table->string('frequency', 50); // e.g., "TDS" or "Every 8 hours"
    $table->string('route', 50); // Oral, IV, IM, SC, etc.
    $table->integer('duration_days');
    $table->integer('quantity');
    $table->text('instructions')->nullable();
    $table->boolean('is_prn')->default(false)->comment('As needed');
    $table->string('prn_reason', 100)->nullable(); // e.g., "For pain > 4/10"
    $table->boolean('is_external_pharmacy')->default(false);
    $table->enum('status', ['pending', 'dispensed', 'partial', 'cancelled'])->default('pending');
    $table->timestamp('dispensed_at')->nullable();
    $table->timestamps();
});
```

#### dispensing_records

Actual medication dispensing (MAR generation).

```php
Schema::create('dispensing_records', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('prescription_item_id')->constrained();
    $table->foreignUuid('dispensed_by')->constrained('staff');
    $table->timestamp('dispensed_at');
    $table->integer('quantity_dispensed');
    $table->string('batch_number', 50)->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('serial_number', 100)->nullable()->comment('For controlled drugs');
    $table->text('counselling_notes')->nullable();
    $table->string('label_instructions', 500)->nullable();
    $table->timestamps();
});
```

---

### Inpatient (IPD)

#### ipd_admissions

Hospital admissions with bed tracking.

```php
Schema::create('ipd_admissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('patient_id')->constrained();
    $table->foreignUuid('visit_id')->constrained();
    $table->string('admission_number', 50); // ADM-YYYYMMDD-XXXX
    $table->timestamp('admission_datetime');
    $table->foreignUuid('admitting_doctor_id')->constrained('staff');
    $table->foreignUuid('bed_id')->constrained();
    $table->enum('admission_type', ['elective', 'emergency', 'day_case', 'transfer']);
    $table->text('admitting_diagnosis');
    $table->enum('source', ['emergency', 'opd', 'direct_admission', 'transfer']);
    $table->text('history_present_illness')->nullable();
    $table->text('physical_examination')->nullable();
    $table->enum('status', ['admitted', 'discharged', 'transferred', 'absconded'])->default('admitted');
    $table->timestamp('discharge_datetime')->nullable();
    $table->foreignUuid('discharged_by')->nullable()->constrained('staff');
    $table->enum('discharge_type', ['home', 'transfer', 'referred', 'expired', 'left_against_advice'])->nullable();
    $table->text('discharge_diagnosis')->nullable();
    $table->text('discharge_summary')->nullable();
    $table->text('discharge_medications')->nullable();
    $table->date('follow_up_date')->nullable();
    $table->timestamps();

    $table->unique(['tenant_id', 'admission_number']);
    $table->index('admission_number');
    $table->index(['status', 'admission_datetime']);
});
```

#### beds

Bed management within wards.

```php
Schema::create('beds', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('ward_id')->constrained();
    $table->string('bed_number', 20);
    $table->enum('type', ['standard', 'electric', 'icu', 'isolation', 'bariatric', 'pediatric'])->default('standard');
    $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'cleaning'])->default('available');
    $table->decimal('daily_rate', 10, 2);
    $table->text('equipment')->nullable(); // JSON list of attached equipment
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['ward_id', 'bed_number']);
    $table->index('status');
});
```

#### wards

Hospital wards/units.

```php
Schema::create('wards', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->string('ward_code', 20);
    $table->string('ward_name', 100);
    $table->enum('type', ['general', 'shared', 'private', 'iccu', 'nicu', 'picu', 'maternity', 'surgery']);
    $table->foreignUuid('department_id')->constrained();
    $table->integer('capacity');
    $table->integer('current_occupancy')->default(0);
    $table->enum('gender', ['male', 'female', 'mixed'])->default('mixed');
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['tenant_id', 'ward_code']);
});
```

#### nursing_care

Nursing documentation.

```php
Schema::create('nursing_care', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('admission_id')->constrained();
    $table->timestamp('care_time');
    $table->enum('care_type', ['assessment', 'medication', 'procedure', 'hygiene', 'mobility', 'dressing', 'other']);
    $table->text('description');
    $table->text('interventions')->nullable();
    $table->text('patient_response')->nullable();
    $table->foreignUuid('nurse_id')->constrained('staff');
    $table->boolean('is_doctor_notified')->default(false);
    $table->timestamp('doctor_notified_at')->nullable();
    $table->timestamps();

    $table->index(['admission_id', 'care_time']);
});
```

#### medication_administrations

MAR (Medication Administration Record).

```php
Schema::create('medication_administrations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('admission_id')->constrained();
    $table->foreignUuid('prescription_item_id')->constrained();
    $table->timestamp('scheduled_time');
    $table->timestamp('administered_time')->nullable();
    $table->foreignUuid('administered_by')->nullable()->constrained('staff');
    $table->foreignUuid('witnessed_by')->nullable()->constrained('staff');
    $table->enum('status', ['pending', 'given', 'missed', 'refused', 'held', 'rescheduled'])->default('pending');
    $table->string('route_given', 50)->nullable();
    $table->string('site', 50)->nullable(); // e.g., "Left deltoid"
    $table->string('dose_given', 50)->nullable();
    $table->text('notes')->nullable();
    $table->string('reason_not_given', 255)->nullable();
    $table->timestamps();

    $table->index(['admission_id', 'scheduled_time']);
    $table->index('status');
});
```

---

### Billing & Finance

#### charge_masters

Standardized pricing catalog.

```php
Schema::create('charge_masters', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->string('item_code', 50);
    $table->string('description', 255);
    $table->enum('category', [
        'consultation', 'procedure', 'lab_test', 'imaging',
        'medication', 'room_charge', 'supply', 'equipment', 'service'
    ]);
    $table->foreignUuid('department_id')->nullable()->constrained();
    $table->decimal('base_price', 10, 2);
    $table->decimal('cost_price', 10, 2)->nullable();
    $table->enum('price_type', ['fixed', 'per_unit', 'variable'])->default('fixed');
    $table->string('unit_of_measure', 20)->nullable(); // per visit, per day, per ml, etc.
    $table->boolean('is_taxable')->default(true);
    $table->decimal('tax_rate', 5, 2)->default(0.00);
    $table->boolean('requires_doctor_authorization')->default(false);
    $table->boolean('insurance_eligible')->default(true);
    $table->date('effective_from');
    $table->date('effective_to')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['tenant_id', 'item_code']);
    $table->index(['category', 'is_active']);
    $table->index('effective_from');
});
```

#### visit_charges

Accumulated charges for a visit.

```php
Schema::create('visit_charges', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visit_id')->constrained();
    $table->foreignUuid('charge_id')->constrained('charge_masters');
    $table->string('description', 255);
    $table->decimal('quantity', 10, 2)->default(1.00);
    $table->decimal('unit_price', 10, 2);
    $table->decimal('discount_percent', 5, 2)->default(0.00);
    $table->decimal('discount_amount', 10, 2)->default(0.00);
    $table->decimal('tax_amount', 10, 2)->default(0.00);
    $table->decimal('total_amount', 10, 2); // Calculated: (qty * price) - discount + tax
    $table->foreignUuid('ordered_by')->constrained('staff');
    $table->timestamp('ordered_at')->useCurrent();
    $table->foreignUuid('performed_by')->nullable()->constrained('staff');
    $table->enum('status', ['ordered', 'performed', 'billed', 'cancelled'])->default('ordered');
    $table->text('cancellation_reason')->nullable();
    $table->timestamps();
});
```

#### visit_billings

Consolidated billing headers.

```php
Schema::create('visit_billings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('visit_id')->constrained()->unique();
    $table->string('invoice_number', 50)->nullable();
    $table->decimal('sub_total', 12, 2)->default(0.00);
    $table->decimal('discount_total', 12, 2)->default(0.00);
    $table->decimal('tax_total', 12, 2)->default(0.00);
    $table->decimal('grand_total', 12, 2)->default(0.00);
    $table->decimal('amount_paid', 12, 2)->default(0.00);
    $table->decimal('balance_amount', 12, 2)->default(0.00);
    $table->decimal('insurance_covered', 12, 2)->default(0.00);
    $table->decimal('charity_amount', 12, 2)->default(0.00);
    $table->enum('status', [
        'pending', 'partial_paid', 'fully_paid', 'insurance_pending',
        'waived', 'refunded', 'written_off'
    ])->default('pending');
    $table->foreignUuid('insurance_id')->nullable()->constrained('patient_insurances');
    $table->string('claim_number', 100)->nullable();
    $table->timestamp('claim_submitted_at')->nullable();
    $table->timestamp('last_payment_date')->nullable();
    $table->timestamp('finalized_at')->nullable();
    $table->foreignUuid('finalized_by')->nullable()->constrained('staff');
    $table->timestamps();

    $table->unique(['tenant_id', 'invoice_number']);
    $table->index('invoice_number');
    $table->index('status');
});
```

#### payments

Payment transactions.

```php
Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained();
    $table->foreignUuid('branch_id')->constrained();
    $table->foreignUuid('billing_id')->constrained('visit_billings');
    $table->enum('method', ['cash', 'card', 'bank_transfer', 'cheque', 'mobile_money', 'insurance', 'waiver']);
    $table->decimal('amount', 12, 2);
    $table->string('reference_number', 100)->nullable();
    $table->string('transaction_id', 100)->nullable()->comment('Gateway transaction ID');
    $table->date('payment_date');
    $table->foreignUuid('received_by')->constrained('staff');
    $table->string('payer_name', 100)->nullable(); // For non-patient payments
    $table->string('payer_phone', 20)->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_refunded')->default(false);
    $table->timestamp('refunded_at')->nullable();
    $table->timestamps();

    $table->index('reference_number');
    $table->index('payment_date');
});
```

---

## Enums & Constants

Define these in your Laravel Enums or config files:

```php
// app/Enums/VisitStatus.php
enum VisitStatus: string {
    case REGISTERED = 'registered';
    case TRIAGED = 'triaged';
    case WAITING_CONSULTATION = 'waiting_consultation';
    case IN_CONSULTATION = 'in_consultation';
    case WAITING_LAB = 'waiting_lab';
    case WAITING_IMAGING = 'waiting_imaging';
    case WAITING_PHARMACY = 'waiting_pharmacy';
    case ADMITTED = 'admitted';
    case DISCHARGED = 'discharged';
    case CANCELLED = 'cancelled';
}

// Priority levels used across modules
enum Priority: string {
    case ROUTINE = 'routine';
    case URGENT = 'urgent';
    case STAT = 'stat';
    case CRITICAL = 'critical';
}

// Billing statuses
enum BillingStatus: string {
    case PENDING = 'pending';
    case PARTIAL_PAID = 'partial_paid';
    case FULLY_PAID = 'fully_paid';
    case INSURANCE_PENDING = 'insurance_pending';
    case WAIVED = 'waived';
    case REFUNDED = 'refunded';
}

//Appointment Status
enum AppointmentStatus: string {
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';
}

//Gender
enum Gender: string {
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
    case UNKNOWN = 'unknown';
}

//Blood Group
enum BloodGroup: string {
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';
    case UNKNOWN = 'unknown';
}

//Insurance Status
enum InsuranceStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}

//Billable Item Type
enum BillableItemType: string {
    case SERVICE = 'service';
    case DRUG = 'drug';
    case TEST = 'test';
    case IMAGING = 'imaging';
    case PROCEDURE = 'procedure';
    case BED_DAY = 'bed_day';
    case OTHER = 'other';
}

//Marital Status
enum MaritalStatus: string {
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';
    case SEPARATED = 'separated';
}

//Patient Type
enum PatientType: string {
    case NEW = 'new';
    case RETURNING = 'returning';
    case EMERGENCY = 'emergency';
    case INPATIENT = 'inpatient';
    case OUTPATIENT = 'outpatient';
}

//Religion
enum Religion: string {
    case CHRISTIAN = 'christian';
    case MUSLIM = 'muslim';
    case HINDU = 'hindu';
    case BUDDHIST = 'buddhist';
    case OTHER = 'other';
    case UNKNOWN = 'unknown';
}

//Visit Status
enum VisitStatus: string {
    case SCHEDULED = 'scheduled';
    case CHECKED_IN = 'checked_in';
    case IN_TREATMENT = 'in_treatment';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';
}

//Visit Type
enum VisitType: string {
    case NEW = 'new';
    case FOLLOW_UP = 'follow_up';
    case EMERGENCY = 'emergency';
    case INPATIENT = 'inpatient';
    case OUTPATIENT = 'outpatient';
}

//kin relationship
enum KinRelationship: string {
    case SPOUSE = 'spouse';
    case PARENT = 'parent';
    case CHILD = 'child';
    case SIBLING = 'sibling';
    case OTHER = 'other';
    case UNKNOWN = 'unknown';
}

//Allergy Severity
enum AllergySeverity: string {
    case MILD = 'mild';
    case MODERATE = 'moderate';
    case SEVERE = 'severe';
    case LIFE_THREATENING = 'life_threatening';
}

//Allergy Type
enum AllergyType: string {
    case MEDICATION = 'medication';
    case FOOD = 'food';
    case ENVIRONMENTAL = 'environmental';
    case LATEX = 'latex';
    case CONTRAST = 'contrast';
}

//Allergy Reaction
enum AllergyReaction: string {
    case RASH = 'rash';
    case ANAPHYLAXIS = 'anaphylaxis';
    case BREATHING_DIFFICULTY = 'breathing_difficulty';
    case ITCHING = 'itching';
    case SWELLING = 'swelling';
    case OTHER = 'other';
}
```

---

## Indexes & Performance

### Critical Indexes for Production

```php
// In your migration files or separate migration for indexes:

// Patient search performance
Schema::table('patients', function (Blueprint $table) {
    $table->index([
        DB::raw('last_name(10)'),
        DB::raw('first_name(10)')
    ], 'idx_patient_name');
    $table->index('medical_record_number');
});

// Visit queue management
Schema::table('patient_visits', function (Blueprint $table) {
    $table->index(['clinic_id', 'status', 'created_at']);
    $table->index(['doctor_id', 'status']);
});

// Lab turnaround time monitoring
Schema::table('lab_results', function (Blueprint $table) {
    $table->index(['resulted_at', 'is_critical']);
    $table->index(['test_id', 'patient_id']);
});

// Financial reporting
Schema::table('visit_charges', function (Blueprint $table) {
    $table->index(['ordered_at', 'department_id']);
    $table->index('status');
});
```

---

## Relationships Summary

```
patients
├── hasMany: addresses, contacts, insurances, allergies, histories
├── hasMany: visits, admissions
└── hasMany: appointments

patient_visits
├── hasOne: triage, consultation, billing
├── hasMany: lab_requests, imaging_requests, prescriptions, charges
└── belongsTo: patient, doctor, clinic

consultations
├── hasMany: lab_requests, imaging_requests, prescriptions, procedure_requests
└── belongsTo: visit, doctor

ipd_admissions
├── hasMany: vital_signs, nursing_care, medication_administrations
├── belongsTo: patient, visit, bed, doctor
└── hasOne: discharge_summary

staff
├── belongsTo: department
├── hasMany: visits(doctor), consultations, prescriptions, lab_requests
└── hasMany: schedules, appointments(doctor)
```

---

## Data Integrity & Constraints

### Foreign Key Strategy

- **Restrict**: For master data (departments, charge_masters) - prevents accidental deletion
- **Cascade**: For visit-related records (charges automatically delete with visit)
- **Set Null**: For staff references (keep audit trail even if staff leaves)

### Validation Rules (Business Logic)

- Discharge date must be after admission date
- Appointment end time after start time
- Lab results cannot be modified after verification
- Cannot delete patients with clinical records (soft delete only)
- Insurance validity must cover visit date

---

## Migration Execution Order

Run migrations in this sequence to avoid foreign key errors:

1. `departments`, `staff` (no dependencies)
2. `clinics`, `wards`, `beds` (depend on departments)
3. `charge_masters`, `medication_catalogs`, `lab_test_catalogs` (master data)
4. `patients` (base entity)
5. `patient_addresses`, `patient_contacts`, `patient_insurances`, `patient_allergies`, `past_medical_histories` (depend on patients)
6. `schedules`, `appointments` (depend on staff/clinics/patients)
7. `patient_visits` (depend on patients/staff/clinics)
8. `triage_records`, `consultations` (depend on visits)
9. `vital_signs` (depend on triage)
10. `lab_requests`, `imaging_requests`, `prescriptions`, `procedure_requests` (depend on visits/consultations)
11. `lab_specimens`, `lab_results` (depend on lab_requests)
12. `imaging_studies`, `radiology_reports` (depend on imaging_requests)
13. `prescription_items`, `dispensing_records` (depend on prescriptions)
14. `ipd_admissions`, `nursing_care`, `medication_administrations` (depend on visits/beds)
15. `visit_charges`, `visit_billings`, `payments` (financial - last to ensure references exist)
16. `audit_logs` (captures all above)

---

## Security Notes

1. **PHI Encryption**: Consider encrypting `patients.email`, `patients.phone_number`, `patient_contacts.value` at database level
2. **Audit Trail**: Never delete `audit_logs` - archive after 7 years per HIPAA
3. **Access Control**: Implement row-level security for `staff` to only see their department's patients
4. **Backup Strategy**: Exclude `audit_logs` from regular backups if large, keep separate compliance backup
