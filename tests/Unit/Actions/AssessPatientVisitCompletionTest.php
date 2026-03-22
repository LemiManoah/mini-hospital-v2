<?php

declare(strict_types=1);

use App\Actions\AssessPatientVisitCompletion;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('blocks visit completion until a triaged visit has a finalized consultation', function (): void {
    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $triageId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-003',
        'first_name' => 'Test',
        'last_name' => 'Doctor',
        'email' => 'assess@example.com',
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
        'visit_number' => 'VIS-003',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('triage_records')->insert([
        'id' => $triageId,
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'nurse_id' => $staffId,
        'triage_datetime' => now(),
        'triage_grade' => 'green',
        'attendance_type' => 'new',
        'conscious_level' => 'alert',
        'mobility_status' => 'independent',
        'chief_complaint' => 'Cough',
        'requires_priority' => false,
        'is_pediatric' => false,
        'poisoning_case' => false,
        'snake_bite_case' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('consultations')->insert([
        'id' => $consultationId,
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'doctor_id' => $staffId,
        'started_at' => now(),
        'chief_complaint' => 'Cough',
        'primary_diagnosis' => 'Upper respiratory tract infection',
        'assessment' => 'Likely viral',
        'plan' => 'Supportive care',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $result = resolve(AssessPatientVisitCompletion::class)->handle($visit);

    expect($result['can_complete'])->toBeFalse()
        ->and($result['blocking_reasons'])->toContain('This visit cannot be completed until the consultation has been finalized.');

    DB::table('consultations')
        ->where('id', $consultationId)
        ->update([
            'completed_at' => now(),
            'outcome' => 'discharged',
            'updated_at' => now(),
        ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $result = resolve(AssessPatientVisitCompletion::class)->handle($visit);

    expect($result['can_complete'])->toBeTrue()
        ->and($result['blocking_reasons'])->toBe([]);
});

it('blocks visit completion when downstream consultation orders are still pending', function (): void {
    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();
    $labRequestId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-004',
        'first_name' => 'Test',
        'last_name' => 'Doctor',
        'email' => 'orders-block@example.com',
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
        'visit_number' => 'VIS-004',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'doctor_id' => $staffId,
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('triage_records')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'nurse_id' => $staffId,
        'triage_datetime' => now(),
        'triage_grade' => 'green',
        'attendance_type' => 'new',
        'conscious_level' => 'alert',
        'mobility_status' => 'independent',
        'chief_complaint' => 'Fever',
        'requires_priority' => false,
        'is_pediatric' => false,
        'poisoning_case' => false,
        'snake_bite_case' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('consultations')->insert([
        'id' => $consultationId,
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'doctor_id' => $staffId,
        'started_at' => now(),
        'completed_at' => now(),
        'chief_complaint' => 'Fever',
        'primary_diagnosis' => 'Malaria',
        'assessment' => 'Needs lab confirmation',
        'plan' => 'Order malaria test',
        'outcome' => 'discharged',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('lab_requests')->insert([
        'id' => $labRequestId,
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'consultation_id' => $consultationId,
        'requested_by' => $staffId,
        'request_date' => now(),
        'priority' => 'routine',
        'status' => 'requested',
        'is_stat' => false,
        'billing_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $result = resolve(AssessPatientVisitCompletion::class)->handle($visit);

    expect($result['can_complete'])->toBeFalse()
        ->and($result['pending_services_count'])->toBe(1)
        ->and($result['blocking_reasons'])->toContain('This visit still has 1 pending service.');
});

it('counts pending facility service orders as blocking downstream work', function (): void {
    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();
    $facilityServiceId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-005',
        'first_name' => 'Service',
        'last_name' => 'Doctor',
        'email' => 'facility-service-block@example.com',
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
        'visit_number' => 'VIS-005',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'doctor_id' => $staffId,
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('triage_records')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'nurse_id' => $staffId,
        'triage_datetime' => now(),
        'triage_grade' => 'green',
        'attendance_type' => 'new',
        'conscious_level' => 'alert',
        'mobility_status' => 'independent',
        'chief_complaint' => 'Wound review',
        'requires_priority' => false,
        'is_pediatric' => false,
        'poisoning_case' => false,
        'snake_bite_case' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('consultations')->insert([
        'id' => $consultationId,
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'doctor_id' => $staffId,
        'started_at' => now(),
        'completed_at' => now(),
        'chief_complaint' => 'Wound review',
        'primary_diagnosis' => 'Soft tissue injury',
        'assessment' => 'Needs dressing',
        'plan' => 'Send to treatment room',
        'outcome' => 'discharged',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('facility_services')->insert([
        'id' => $facilityServiceId,
        'tenant_id' => $tenantId,
        'service_code' => 'SRV-001',
        'name' => 'Wound Dressing',
        'category' => 'dressing',
        'is_billable' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('facility_service_orders')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'facility_branch_id' => null,
        'visit_id' => $visitId,
        'consultation_id' => $consultationId,
        'facility_service_id' => $facilityServiceId,
        'ordered_by' => $staffId,
        'status' => 'pending',
        'clinical_notes' => 'Daily dressing required',
        'service_instructions' => 'Use sterile pack',
        'ordered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $result = resolve(AssessPatientVisitCompletion::class)->handle($visit);

    expect($result['can_complete'])->toBeFalse()
        ->and($result['pending_services_count'])->toBe(1)
        ->and($result['blocking_reasons'])->toContain('This visit still has 1 pending service.');
});

it('warns when a visit has an unpaid balance and clears the warning after settlement', function (): void {
    $tenantId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $branchId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'visit_number' => 'VIS-006',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_payers')->insert([
        'id' => $payerId,
        'tenant_id' => $tenantId,
        'patient_visit_id' => $visitId,
        'billing_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'patient_visit_id' => $visitId,
        'visit_payer_id' => $payerId,
        'payer_type' => 'cash',
        'gross_amount' => 0,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => 0,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_charges')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visitId,
        'source_type' => 'manual',
        'source_id' => (string) Str::uuid(),
        'description' => 'Registration fee',
        'quantity' => 1,
        'unit_price' => 30,
        'line_total' => 30,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $result = resolve(AssessPatientVisitCompletion::class)->handle($visit);

    expect($result['can_complete'])->toBeTrue()
        ->and($result['has_unpaid_balance'])->toBeTrue()
        ->and($result['unpaid_balance'])->toBe(30.0)
        ->and($result['warning_messages'])->toContain('This patient still has an unpaid balance of 30.00 for this visit.');

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visitId,
        'receipt_number' => 'RCT-2001',
        'payment_date' => now(),
        'amount' => 30,
        'payment_method' => 'cash',
        'is_refund' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $result = resolve(AssessPatientVisitCompletion::class)->handle($visit);

    expect($result['has_unpaid_balance'])->toBeFalse()
        ->and($result['unpaid_balance'])->toBe(0.0)
        ->and($result['warning_messages'])->toBe([]);
});
