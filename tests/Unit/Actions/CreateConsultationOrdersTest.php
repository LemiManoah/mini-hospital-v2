<?php

declare(strict_types=1);

use App\Actions\CreateFacilityServiceOrder;
use App\Actions\CreateImagingOrder;
use App\Actions\CreateLabOrder;
use App\Actions\CreatePrescription;
use App\Actions\DeletePendingFacilityServiceOrder;
use App\Data\Clinical\CreateFacilityServiceOrderDTO;
use App\Data\Clinical\CreateImagingOrderDTO;
use App\Data\Clinical\CreateLabOrderDTO;
use App\Data\Clinical\CreatePrescriptionDTO;
use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\ImagingModality;
use App\Enums\InventoryItemType;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Models\Activity;
use App\Models\Consultation;
use App\Models\PatientVisit;
use App\Models\Permission;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function seedLabCatalogRefs(): array
{
    $categoryId = (string) Str::uuid();
    $specimenTypeId = (string) Str::uuid();
    $resultTypeId = (string) Str::uuid();

    DB::table('lab_test_categories')->insert([
        'id' => $categoryId,
        'tenant_id' => null,
        'name' => 'Test Category '.Str::lower(Str::random(5)),
        'description' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('specimen_types')->insert([
        'id' => $specimenTypeId,
        'tenant_id' => null,
        'name' => 'Test Specimen '.Str::lower(Str::random(5)),
        'description' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('result_types')->insert([
        'id' => $resultTypeId,
        'tenant_id' => null,
        'code' => 'free_entry_'.Str::lower(Str::random(5)),
        'name' => 'Free Entry Test',
        'description' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$categoryId, $specimenTypeId, $resultTypeId];
}

function seedConsultationContext(string $billingType = 'cash'): array
{
    $tenantId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $insuranceCompanyId = $billingType === 'insurance'
        ? (string) Str::uuid()
        : null;
    $insurancePackageId = $billingType === 'insurance'
        ? (string) Str::uuid()
        : null;

    $tenantContext = seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);

    if ($insuranceCompanyId !== null && $insurancePackageId !== null) {
        seedInsuranceCoverage($tenantId, $insuranceCompanyId, $insurancePackageId);
    }

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-ORDERS',
        'first_name' => 'Test',
        'last_name' => 'Doctor',
        'email' => 'orders@example.com',
        'type' => 'medical',
        'hire_date' => now()->toDateString(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-ORD-001',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'doctor_id' => $staffId,
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_payers')->insert([
        'id' => $payerId,
        'tenant_id' => $tenantId,
        'patient_visit_id' => $visitId,
        'billing_type' => $billingType,
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('consultations')->insert([
        'id' => $consultationId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_id' => $visitId,
        'doctor_id' => $staffId,
        'started_at' => now(),
        'primary_diagnosis' => 'Malaria',
        'primary_icd10_code' => 'B50',
        'history_of_present_illness' => 'Fever and chills for three days',
        'assessment' => 'Treat and investigate',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'staff_id' => $staffId,
        'visit_id' => $visitId,
        'payer_id' => $payerId,
        'insurance_package_id' => $insurancePackageId,
        'consultation' => Consultation::query()->findOrFail($consultationId),
    ];
}

if (! function_exists('createFacilityServiceOrderRequest')) {
    function createFacilityServiceOrderRequest(array $validated): FormRequest
    {
        return new class($validated) extends FormRequest
        {
            public function __construct(private readonly array $validatedInput)
            {
                parent::__construct();
            }

            public function validated($key = null, $default = null): array
            {
                return $this->validatedInput;
            }
        };
    }
}

if (! function_exists('createLabOrderDtoRequest')) {
    function createLabOrderDtoRequest(array $validated): FormRequest
    {
        return new class($validated) extends FormRequest
        {
            public function __construct(private readonly array $validatedInput)
            {
                parent::__construct();
            }

            public function validated($key = null, $default = null): array
            {
                return $this->validatedInput;
            }
        };
    }
}

if (! function_exists('createPrescriptionDtoRequest')) {
    function createPrescriptionDtoRequest(array $validated): FormRequest
    {
        return new class($validated) extends FormRequest
        {
            public function __construct(private readonly array $validatedInput)
            {
                parent::__construct();
            }

            public function validated($key = null, $default = null): array
            {
                return $this->validatedInput;
            }
        };
    }
}

if (! function_exists('createImagingOrderDtoRequest')) {
    function createImagingOrderDtoRequest(array $validated): FormRequest
    {
        return new class($validated) extends FormRequest
        {
            public function __construct(private readonly array $validatedInput)
            {
                parent::__construct();
            }

            public function validated($key = null, $default = null): array
            {
                return $this->validatedInput;
            }
        };
    }
}

function createOrderNotificationRecipient(string $tenantId, array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $user = User::factory()
        ->withoutTwoFactor()
        ->create([
            'tenant_id' => $tenantId,
        ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('creates a lab order with priced items from the consultation context and syncs a visit charge', function (): void {
    $context = seedConsultationContext();
    $recipient = createOrderNotificationRecipient($context['tenant_id'], ['lab_orders.view']);
    $testId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();
    [$categoryId, $specimenTypeId, $resultTypeId] = seedLabCatalogRefs();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'FBC',
        'description' => 'Full Blood Count',
        'billable_type' => 'test',
        'billable_id' => $testId,
        'unit_price' => 27500,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('lab_test_catalogs')->insert([
        'id' => $testId,
        'tenant_id' => $context['tenant_id'],
        'test_code' => 'FBC',
        'test_name' => 'Full Blood Count',
        'lab_test_category_id' => $categoryId,
        'result_type_id' => $resultTypeId,
        'charge_master_id' => $chargeMasterId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('lab_test_catalog_specimen_type')->insert([
        'lab_test_catalog_id' => $testId,
        'specimen_type_id' => $specimenTypeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = resolve(CreateLabOrder::class)->handle($context['consultation'], CreateLabOrderDTO::fromRequest(createLabOrderDtoRequest([
        'test_ids' => [$testId],
        'clinical_notes' => 'Rule out infection',
        'priority' => 'urgent',
        'diagnosis_code' => 'B50',
        'is_stat' => false,
    ])), $context['staff_id']);

    expect($request->consultation_id)->toBe($context['consultation']->id)
        ->and($request->priority)->toBe(Priority::URGENT)
        ->and($request->items)->toHaveCount(1)
        ->and($request->items->first()?->price)->toBe(27500.0);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $request->items->first()?->getMorphClass())
        ->where('source_id', $request->items->first()?->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(27500.0)
        ->and((float) $charge->line_total)->toBe(27500.0);

    expect(Activity::query()
        ->where('log_name', 'laboratory')
        ->where('event', 'lab_order.created')
        ->where('subject_type', $request->getMorphClass())
        ->where('subject_id', $request->id)
        ->exists())->toBeTrue();

    $notification = $recipient->notifications()->first();

    expect($notification)->not()->toBeNull()
        ->and($notification?->data['type'] ?? null)->toBe('lab_order_created')
        ->and($notification?->data['resource_id'] ?? null)->toBe($request->id);
});

it('moves a registered visit into progress when a visit-level lab order is created', function (): void {
    $context = seedConsultationContext();
    $testId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();
    [$categoryId, $specimenTypeId, $resultTypeId] = seedLabCatalogRefs();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'CRP',
        'description' => 'C-Reactive Protein',
        'billable_type' => 'test',
        'billable_id' => $testId,
        'unit_price' => 30000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')
        ->where('id', $context['visit_id'])
        ->update([
            'status' => 'registered',
            'started_at' => null,
        ]);

    DB::table('lab_test_catalogs')->insert([
        'id' => $testId,
        'tenant_id' => $context['tenant_id'],
        'test_code' => 'CRP',
        'test_name' => 'C-Reactive Protein',
        'lab_test_category_id' => $categoryId,
        'result_type_id' => $resultTypeId,
        'charge_master_id' => $chargeMasterId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('lab_test_catalog_specimen_type')->insert([
        'lab_test_catalog_id' => $testId,
        'specimen_type_id' => $specimenTypeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($context['visit_id']);

    resolve(CreateLabOrder::class)->handle($visit, CreateLabOrderDTO::fromRequest(createLabOrderDtoRequest([
        'test_ids' => [$testId],
        'clinical_notes' => 'Inflammatory marker',
        'priority' => 'routine',
        'diagnosis_code' => 'R50',
        'is_stat' => false,
    ])), $context['staff_id']);

    expect($visit->fresh()->status)->toBe(VisitStatus::IN_PROGRESS)
        ->and($visit->fresh()->started_at)->not->toBeNull();
});

it('uses insurance policy prices when syncing lab order charges', function (): void {
    $context = seedConsultationContext('insurance');
    $testId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();
    [$categoryId, $specimenTypeId, $resultTypeId] = seedLabCatalogRefs();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'MPS',
        'description' => 'Malaria Parasite Smear',
        'billable_type' => 'test',
        'billable_id' => $testId,
        'unit_price' => 18000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('lab_test_catalogs')->insert([
        'id' => $testId,
        'tenant_id' => $context['tenant_id'],
        'test_code' => 'MPS',
        'test_name' => 'Malaria Parasite Smear',
        'lab_test_category_id' => $categoryId,
        'result_type_id' => $resultTypeId,
        'charge_master_id' => $chargeMasterId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('lab_test_catalog_specimen_type')->insert([
        'lab_test_catalog_id' => $testId,
        'specimen_type_id' => $specimenTypeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    seedInsurancePolicyItem($context['tenant_id'], $context['branch_id'], $context['insurance_package_id'], 'lab', $chargeMasterId, 12000, 'fixed', 2000);

    $request = resolve(CreateLabOrder::class)->handle($context['consultation'], CreateLabOrderDTO::fromRequest(createLabOrderDtoRequest([
        'test_ids' => [$testId],
        'clinical_notes' => 'Confirm malaria',
        'priority' => 'routine',
        'diagnosis_code' => 'B50',
        'is_stat' => false,
    ])), $context['staff_id']);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $request->items->first()?->getMorphClass())
        ->where('source_id', $request->items->first()?->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(12000.0)
        ->and((float) $charge->line_total)->toBe(12000.0)
        ->and((float) $charge->copay_amount)->toBe(2000.0);
});

it('creates a prescription with multiple drug items', function (): void {
    $context = seedConsultationContext();
    $recipient = createOrderNotificationRecipient($context['tenant_id'], ['pharmacy_dispensing.view']);
    $drugId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'DRUG-PARACETAMOL',
        'description' => 'Paracetamol',
        'billable_type' => 'drug',
        'billable_id' => $drugId,
        'unit_price' => 1750,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_items')->insert([
        'id' => $drugId,
        'tenant_id' => $context['tenant_id'],
        'item_type' => InventoryItemType::DRUG->value,
        'name' => 'Paracetamol',
        'generic_name' => 'Paracetamol',
        'brand_name' => 'Panadol',
        'category' => DrugCategory::ANALGESIC->value,
        'dosage_form' => DrugDosageForm::TABLET->value,
        'strength' => '500mg',
        'charge_master_id' => $chargeMasterId,
        'expires' => true,
        'is_controlled' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $prescription = resolve(CreatePrescription::class)->handle($context['consultation'], CreatePrescriptionDTO::fromRequest(createPrescriptionDtoRequest([
        'primary_diagnosis' => 'Malaria',
        'pharmacy_notes' => 'Dispense today',
        'is_discharge_medication' => false,
        'is_long_term' => false,
        'items' => [[
            'inventory_item_id' => $drugId,
            'dosage' => '1 tablet',
            'frequency' => 'TDS',
            'route' => 'oral',
            'duration_days' => 5,
            'quantity' => 15,
            'instructions' => 'After meals',
            'is_prn' => false,
            'is_external_pharmacy' => false,
        ]],
    ])), $context['staff_id']);

    expect($prescription->consultation_id)->toBe($context['consultation']->id)
        ->and($prescription->items)->toHaveCount(1)
        ->and($prescription->items->first()?->quantity)->toBe(15);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $prescription->getMorphClass())
        ->where('source_id', $prescription->id)
        ->first();

    expect($charge)->toBeNull();

    $notification = $recipient->notifications()->first();

    expect($notification)->not()->toBeNull()
        ->and($notification?->data['type'] ?? null)->toBe('prescription_created')
        ->and($notification?->data['resource_id'] ?? null)->toBe($prescription->id);
});

it('uses insurance policy prices when syncing prescription charges', function (): void {
    $context = seedConsultationContext('insurance');
    $drugId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'DRUG-AMOXICILLIN',
        'description' => 'Amoxicillin 500mg capsule',
        'billable_type' => 'drug',
        'billable_id' => $drugId,
        'unit_price' => 1500,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_items')->insert([
        'id' => $drugId,
        'tenant_id' => $context['tenant_id'],
        'item_type' => InventoryItemType::DRUG->value,
        'name' => 'Amoxicillin',
        'generic_name' => 'Amoxicillin',
        'brand_name' => null,
        'category' => DrugCategory::ANTIBIOTIC->value,
        'dosage_form' => DrugDosageForm::CAPSULE->value,
        'strength' => '500mg',
        'charge_master_id' => $chargeMasterId,
        'expires' => true,
        'is_controlled' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    seedInsurancePolicyItem($context['tenant_id'], $context['branch_id'], $context['insurance_package_id'], 'pharmacy', $chargeMasterId, 1200);

    $prescription = resolve(CreatePrescription::class)->handle($context['consultation'], CreatePrescriptionDTO::fromRequest(createPrescriptionDtoRequest([
        'primary_diagnosis' => 'Bacterial infection',
        'pharmacy_notes' => null,
        'is_discharge_medication' => false,
        'is_long_term' => false,
        'items' => [[
            'inventory_item_id' => $drugId,
            'dosage' => '1 capsule',
            'frequency' => 'BD',
            'route' => 'oral',
            'duration_days' => 7,
            'quantity' => 14,
            'instructions' => 'After food',
            'is_prn' => false,
            'prn_reason' => null,
            'is_external_pharmacy' => false,
        ]],
    ])), $context['staff_id']);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $prescription->getMorphClass())
        ->where('source_id', $prescription->id)
        ->first();

    expect($charge)->toBeNull();
});

it('creates an imaging order linked to the consultation', function (): void {
    $context = seedConsultationContext();
    $recipient = createOrderNotificationRecipient($context['tenant_id'], ['imaging_orders.view']);
    $studyCatalogId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => $context['branch_id'],
        'item_code' => 'IMG-CXR',
        'description' => 'Chest X-Ray',
        'billable_type' => 'imaging',
        'billable_id' => $studyCatalogId,
        'unit_price' => 40000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('imaging_study_catalogs')->insert([
        'id' => $studyCatalogId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => $context['branch_id'],
        'code' => 'IMG-CXR',
        'name' => 'Chest X-Ray',
        'modality' => 'xray',
        'body_part' => 'Chest',
        'charge_master_id' => $chargeMasterId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = resolve(CreateImagingOrder::class)->handle($context['consultation'], CreateImagingOrderDTO::fromRequest(createImagingOrderDtoRequest([
        'imaging_study_catalog_id' => $studyCatalogId,
        'modality' => 'xray',
        'body_part' => 'Chest',
        'laterality' => 'na',
        'clinical_history' => 'Fever with cough',
        'indication' => 'Assess for pneumonia',
        'priority' => 'routine',
        'requires_contrast' => false,
        'contrast_allergy_status' => null,
        'pregnancy_status' => 'unknown',
    ])), $context['staff_id']);

    expect($request->consultation_id)->toBe($context['consultation']->id)
        ->and($request->imaging_study_catalog_id)->toBe($studyCatalogId)
        ->and($request->body_part)->toBe('Chest')
        ->and($request->modality)->toBe(ImagingModality::XRAY);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $request->getMorphClass())
        ->where('source_id', $request->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(40000.0)
        ->and((float) $charge->line_total)->toBe(40000.0)
        ->and($charge->charge_master_id)->toBe($chargeMasterId);

    expect(Activity::query()
        ->where('log_name', 'clinical')
        ->where('event', 'imaging_order.created')
        ->where('subject_type', $request->getMorphClass())
        ->where('subject_id', $request->id)
        ->exists())->toBeTrue();

    $notification = $recipient->notifications()->first();

    expect($notification)->not()->toBeNull()
        ->and($notification?->data['type'] ?? null)->toBe('imaging_order_created')
        ->and($notification?->data['resource_id'] ?? null)->toBe($request->id);
});

it('creates a facility service order with consultation context and syncs an insurance-priced charge', function (): void {
    $context = seedConsultationContext('insurance');
    $recipient = createOrderNotificationRecipient($context['tenant_id'], ['facility_services.view']);
    $serviceId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $context['tenant_id'],
        'service_code' => 'SRV-100',
        'name' => 'Nebulization',
        'category' => 'other',
        'charge_master_id' => $chargeMasterId,
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'SRV-100',
        'description' => 'Nebulization',
        'billable_type' => 'service',
        'billable_id' => $serviceId,
        'unit_price' => 7000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    seedInsurancePolicyItem($context['tenant_id'], $context['branch_id'], $context['insurance_package_id'], 'services', $chargeMasterId, 9500);

    $order = resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
            'facility_service_id' => $serviceId,
        ])),
        $context['staff_id'],
    );

    expect($order->tenant_id)->toBe($context['tenant_id'])
        ->and($order->facility_branch_id)->toBe($context['branch_id'])
        ->and($order->visit_id)->toBe($context['visit_id'])
        ->and($order->consultation_id)->toBe($context['consultation']->id)
        ->and($order->facility_service_id)->toBe($serviceId)
        ->and($order->ordered_by)->toBe($context['staff_id'])
        ->and($order->status)->toBe(FacilityServiceOrderStatus::PENDING)
        ->and($order->ordered_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($order->relationLoaded('service'))->toBeTrue()
        ->and($order->relationLoaded('orderedBy'))->toBeTrue()
        ->and($order->service?->name)->toBe('Nebulization')
        ->and($order->service?->service_code)->toBe('SRV-100')
        ->and($order->orderedBy?->id)->toBe($context['staff_id']);

    $this->assertDatabaseHas('facility_service_orders', [
        'id' => $order->id,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => $context['branch_id'],
        'visit_id' => $context['visit_id'],
        'consultation_id' => $context['consultation']->id,
        'facility_service_id' => $serviceId,
        'ordered_by' => $context['staff_id'],
        'status' => FacilityServiceOrderStatus::PENDING->value,
    ]);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $order->getMorphClass())
        ->where('source_id', $order->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(9500.0)
        ->and((float) $charge->line_total)->toBe(9500.0)
        ->and($charge->description)->toBe('Facility service: Nebulization');

    expect(Activity::query()
        ->where('log_name', 'clinical')
        ->where('event', 'service_order.created')
        ->where('subject_type', $order->getMorphClass())
        ->where('subject_id', $order->id)
        ->exists())->toBeTrue();

    $notification = $recipient->notifications()->first();

    expect($notification)->not()->toBeNull()
        ->and($notification?->data['type'] ?? null)->toBe('facility_service_order_created')
        ->and($notification?->data['resource_id'] ?? null)->toBe($order->id);
});

it('creates a facility service order with the charge master unit price for cash visits', function (): void {
    $context = seedConsultationContext('cash');
    $serviceId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $context['tenant_id'],
        'service_code' => 'SRV-101',
        'name' => 'Oxygen Therapy',
        'category' => 'other',
        'charge_master_id' => $chargeMasterId,
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'SRV-101',
        'description' => 'Oxygen Therapy',
        'billable_type' => 'service',
        'billable_id' => $serviceId,
        'unit_price' => 12000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $order = resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
            'facility_service_id' => $serviceId,
        ])),
        $context['staff_id'],
    );

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $order->getMorphClass())
        ->where('source_id', $order->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(12000.0)
        ->and((float) $charge->line_total)->toBe(12000.0)
        ->and($charge->charge_code)->toBe('SRV-101');
});

it('creates a facility service order without syncing a charge for non-billable services', function (): void {
    $context = seedConsultationContext('cash');
    $serviceId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $context['tenant_id'],
        'service_code' => 'SRV-102',
        'name' => 'Wound Review',
        'category' => 'other',
        'is_billable' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $order = resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
            'facility_service_id' => $serviceId,
        ])),
        $context['staff_id'],
    );

    expect($order->status)->toBe(FacilityServiceOrderStatus::PENDING)
        ->and($order->service?->is_billable)->toBeFalse();

    $this->assertDatabaseHas('facility_service_orders', [
        'id' => $order->id,
        'facility_service_id' => $serviceId,
        'status' => FacilityServiceOrderStatus::PENDING->value,
    ]);

    $this->assertDatabaseMissing('visit_charges', [
        'patient_visit_id' => $context['visit_id'],
        'source_type' => $order->getMorphClass(),
        'source_id' => $order->id,
    ]);
});

it('prevents duplicate pending facility service orders for the same visit', function (): void {
    $context = seedConsultationContext('cash');
    $serviceId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $context['tenant_id'],
        'service_code' => 'SRV-103',
        'name' => 'Nebulization',
        'category' => 'other',
        'charge_master_id' => $chargeMasterId,
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'SRV-103',
        'description' => 'Nebulization',
        'billable_type' => 'service',
        'billable_id' => $serviceId,
        'unit_price' => 7000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
            'facility_service_id' => $serviceId,
        ])),
        $context['staff_id'],
    );

    expect(fn () => resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
            'facility_service_id' => $serviceId,
        ])),
        $context['staff_id'],
    ))->toThrow(ValidationException::class);

    expect(DB::table('facility_service_orders')
        ->where('visit_id', $context['visit_id'])
        ->where('facility_service_id', $serviceId)
        ->count())->toBe(1);
});

it('deletes a pending facility service order and its synced charge', function (): void {
    $context = seedConsultationContext('cash');
    $serviceId = (string) Str::uuid();
    $chargeMasterId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $context['tenant_id'],
        'service_code' => 'SRV-104',
        'name' => 'Wound Dressing',
        'category' => 'other',
        'charge_master_id' => $chargeMasterId,
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => null,
        'item_code' => 'SRV-104',
        'description' => 'Wound Dressing',
        'billable_type' => 'service',
        'billable_id' => $serviceId,
        'unit_price' => 8000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $order = resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
            'facility_service_id' => $serviceId,
        ])),
        $context['staff_id'],
    );

    resolve(DeletePendingFacilityServiceOrder::class)->handle($order);

    $this->assertDatabaseMissing('facility_service_orders', [
        'id' => $order->id,
    ]);

    $this->assertDatabaseMissing('visit_charges', [
        'patient_visit_id' => $context['visit_id'],
        'source_type' => $order->getMorphClass(),
        'source_id' => $order->id,
        'deleted_at' => null,
    ]);
});
