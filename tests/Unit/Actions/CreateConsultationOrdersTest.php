<?php

declare(strict_types=1);

use App\Actions\CreateImagingRequest;
use App\Actions\CreateFacilityServiceOrder;
use App\Actions\CreateLabRequest;
use App\Actions\CreatePrescription;
use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\ImagingModality;
use App\Enums\Priority;
use App\Models\Consultation;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function seedConsultationContext(string $billingType = 'cash'): array
{
    DB::statement('PRAGMA foreign_keys = OFF');

    $tenantId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $insurancePackageId = $billingType === 'insurance'
        ? (string) Str::uuid()
        : null;

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

    DB::table('facility_branches')->insert([
        'id' => $branchId,
        'name' => 'Main Branch',
        'branch_code' => 'MAIN',
        'tenant_id' => $tenantId,
        'currency_id' => (string) Str::uuid(),
        'status' => 'active',
        'is_main_branch' => true,
        'has_store' => false,
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
        'insurance_company_id' => $insurancePackageId === null
            ? null
            : (string) Str::uuid(),
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

it('creates a lab request with priced items from the consultation context and syncs a visit charge', function (): void {
    $context = seedConsultationContext();
    $testId = (string) Str::uuid();

    DB::table('lab_test_catalogs')->insert([
        'id' => $testId,
        'tenant_id' => $context['tenant_id'],
        'test_code' => 'FBC',
        'test_name' => 'Full Blood Count',
        'category' => 'Hematology',
        'base_price' => 25000,
        'requires_fasting' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = resolve(CreateLabRequest::class)->handle($context['consultation'], [
        'test_ids' => [$testId],
        'clinical_notes' => 'Rule out infection',
        'priority' => 'urgent',
        'diagnosis_code' => 'B50',
        'is_stat' => false,
    ], $context['staff_id']);

    expect($request->consultation_id)->toBe($context['consultation']->id)
        ->and($request->priority)->toBe(Priority::URGENT)
        ->and($request->items)->toHaveCount(1)
        ->and($request->items->first()?->price)->toBe(25000.0);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $request->getMorphClass())
        ->where('source_id', $request->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(25000.0)
        ->and((float) $charge->line_total)->toBe(25000.0);
});

it('uses insurance package prices when syncing lab request charges', function (): void {
    $context = seedConsultationContext('insurance');
    $testId = (string) Str::uuid();

    DB::table('lab_test_catalogs')->insert([
        'id' => $testId,
        'tenant_id' => $context['tenant_id'],
        'test_code' => 'MPS',
        'test_name' => 'Malaria Parasite Smear',
        'category' => 'Parasitology',
        'base_price' => 18000,
        'requires_fasting' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_package_prices')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => $context['branch_id'],
        'insurance_package_id' => $context['insurance_package_id'],
        'billable_type' => 'test',
        'billable_id' => $testId,
        'price' => 12000,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = resolve(CreateLabRequest::class)->handle($context['consultation'], [
        'test_ids' => [$testId],
        'clinical_notes' => 'Confirm malaria',
        'priority' => 'routine',
        'diagnosis_code' => 'B50',
        'is_stat' => false,
    ], $context['staff_id']);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $request->getMorphClass())
        ->where('source_id', $request->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(12000.0)
        ->and((float) $charge->line_total)->toBe(12000.0);
});

it('creates a prescription with multiple drug items', function (): void {
    $context = seedConsultationContext();
    $drugId = (string) Str::uuid();

    DB::table('drugs')->insert([
        'id' => $drugId,
        'tenant_id' => $context['tenant_id'],
        'generic_name' => 'Paracetamol',
        'brand_name' => 'Panadol',
        'drug_code' => 'DRG-TEST-001',
        'category' => DrugCategory::ANALGESIC->value,
        'dosage_form' => DrugDosageForm::TABLET->value,
        'strength' => '500mg',
        'unit' => 'tab',
        'is_controlled' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $prescription = resolve(CreatePrescription::class)->handle($context['consultation'], [
        'primary_diagnosis' => 'Malaria',
        'pharmacy_notes' => 'Dispense today',
        'is_discharge_medication' => false,
        'is_long_term' => false,
        'items' => [[
            'drug_id' => $drugId,
            'dosage' => '1 tablet',
            'frequency' => 'TDS',
            'route' => 'oral',
            'duration_days' => 5,
            'quantity' => 15,
            'instructions' => 'After meals',
            'is_prn' => false,
            'is_external_pharmacy' => false,
        ]],
    ], $context['staff_id']);

    expect($prescription->consultation_id)->toBe($context['consultation']->id)
        ->and($prescription->items)->toHaveCount(1)
        ->and($prescription->items->first()?->quantity)->toBe(15);
});

it('creates an imaging request linked to the consultation', function (): void {
    $context = seedConsultationContext();

    $request = resolve(CreateImagingRequest::class)->handle($context['consultation'], [
        'modality' => 'xray',
        'body_part' => 'Chest',
        'laterality' => 'na',
        'clinical_history' => 'Fever with cough',
        'indication' => 'Assess for pneumonia',
        'priority' => 'routine',
        'requires_contrast' => false,
        'contrast_allergy_status' => null,
        'pregnancy_status' => 'unknown',
    ], $context['staff_id']);

    expect($request->consultation_id)->toBe($context['consultation']->id)
        ->and($request->body_part)->toBe('Chest')
        ->and($request->modality)->toBe(ImagingModality::XRAY);
});

it('creates a facility service order and syncs an insurance-priced charge', function (): void {
    $context = seedConsultationContext('insurance');
    $serviceId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $context['tenant_id'],
        'service_code' => 'SRV-100',
        'name' => 'Nebulization',
        'category' => 'other',
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_package_prices')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $context['tenant_id'],
        'facility_branch_id' => $context['branch_id'],
        'insurance_package_id' => $context['insurance_package_id'],
        'billable_type' => 'service',
        'billable_id' => $serviceId,
        'price' => 9500,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $order = resolve(CreateFacilityServiceOrder::class)->handle(
        $context['consultation'],
        [
            'facility_service_id' => $serviceId,
            'clinical_notes' => 'Give bronchodilator support',
            'service_instructions' => 'Once now',
        ],
        $context['staff_id'],
    );

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $context['visit_id'])
        ->where('source_type', $order->getMorphClass())
        ->where('source_id', $order->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(9500.0)
        ->and((float) $charge->line_total)->toBe(9500.0)
        ->and($charge->description)->toBe('Facility service: Nebulization');
});
