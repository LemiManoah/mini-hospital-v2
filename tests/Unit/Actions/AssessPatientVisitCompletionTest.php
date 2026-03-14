<?php

declare(strict_types=1);

use App\Actions\AssessPatientVisitCompletion;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('blocks visit completion until a triaged visit has a finalized consultation', function (): void {
    DB::statement('PRAGMA foreign_keys = OFF');

    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $triageId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();

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
    DB::statement('PRAGMA foreign_keys = OFF');

    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $consultationId = (string) Str::uuid();
    $labRequestId = (string) Str::uuid();

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
