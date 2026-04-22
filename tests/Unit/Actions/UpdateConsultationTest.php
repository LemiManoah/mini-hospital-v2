<?php

declare(strict_types=1);

use App\Actions\UpdateConsultation;
use App\Data\Clinical\UpdateConsultationDTO;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('updates a consultation using a typed dto', function (): void {
    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $branchId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-UPDATE-CONSULT',
        'first_name' => 'Test',
        'last_name' => 'Doctor',
        'email' => 'update-consultation@example.com',
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
        'visit_number' => 'VIS-UPDATE-CONSULT',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'doctor_id' => $staffId,
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $consultation = Consultation::query()->create([
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_id' => $visitId,
        'doctor_id' => $staffId,
        'started_at' => now(),
        'chief_complaint' => 'Old complaint',
        'assessment' => 'Old assessment',
    ]);

    $dto = new UpdateConsultationDTO(
        chiefComplaint: 'Updated complaint',
        historyOfPresentIllness: null,
        reviewOfSystems: null,
        pastMedicalHistorySummary: null,
        familyHistory: null,
        socialHistory: null,
        subjectiveNotes: null,
        objectiveFindings: null,
        assessment: 'Updated assessment',
        plan: 'Updated plan',
        primaryDiagnosis: 'Updated diagnosis',
        primaryIcd10Code: 'A01',
    );

    $updated = resolve(UpdateConsultation::class)->handle($consultation, $dto);

    expect($updated->chief_complaint)->toBe('Updated complaint')
        ->and($updated->assessment)->toBe('Updated assessment')
        ->and($updated->plan)->toBe('Updated plan')
        ->and($updated->primary_diagnosis)->toBe('Updated diagnosis')
        ->and($updated->primary_icd10_code)->toBe('A01');
});
