<?php

declare(strict_types=1);

use App\Actions\CompleteConsultation;
use App\Data\Clinical\CompleteConsultationDTO;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('completes a consultation using a typed dto', function (): void {
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
        'employee_number' => 'EMP-COMPLETE-CONSULT',
        'first_name' => 'Test',
        'last_name' => 'Doctor',
        'email' => 'complete-consultation@example.com',
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
        'visit_number' => 'VIS-COMPLETE-CONSULT',
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

    $dto = new CompleteConsultationDTO(
        chiefComplaint: 'Final complaint',
        historyOfPresentIllness: null,
        reviewOfSystems: null,
        pastMedicalHistorySummary: null,
        familyHistory: null,
        socialHistory: null,
        subjectiveNotes: null,
        objectiveFindings: null,
        assessment: 'Final assessment',
        plan: 'Final plan',
        primaryDiagnosis: 'Final diagnosis',
        primaryIcd10Code: 'B02',
        outcome: 'follow_up_required',
        followUpInstructions: 'Review after medication',
        followUpDays: 14,
        isReferred: true,
        referredToDepartment: 'Cardiology',
        referredToFacility: null,
        referralReason: 'Needs specialist opinion',
    );

    $completed = resolve(CompleteConsultation::class)->handle($consultation, $dto);

    expect($completed->chief_complaint)->toBe('Final complaint')
        ->and($completed->assessment)->toBe('Final assessment')
        ->and($completed->primary_diagnosis)->toBe('Final diagnosis')
        ->and($completed->outcome?->value)->toBe('follow_up_required')
        ->and($completed->follow_up_days)->toBe(14)
        ->and($completed->is_referred)->toBeTrue()
        ->and($completed->referred_to_department)->toBe('Cardiology')
        ->and($completed->referral_reason)->toBe('Needs specialist opinion')
        ->and($completed->completed_at)->not->toBeNull();
});
