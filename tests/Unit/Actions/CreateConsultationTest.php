<?php

declare(strict_types=1);

use App\Actions\CreateConsultation;
use App\Data\Clinical\CreateConsultationDTO;
use App\Enums\ConsultationType;
use App\Enums\VisitStatus;
use App\Models\Activity;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createConsultationActionRequest(array $validated): FormRequest
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

it('creates a consultation using triage context and the authenticated clinician', function (): void {
    $tenantId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $triageId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-002',
        'first_name' => 'Test',
        'last_name' => 'Doctor',
        'email' => 'doctor@example.com',
        'type' => 'medical',
        'hire_date' => now()->toDateString(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users')->insert([
        'id' => $userId,
        'staff_id' => $staffId,
        'tenant_id' => $tenantId,
        'email' => 'doctor-user@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'visit_number' => 'VIS-002',
        'visit_type' => 'outpatient',
        'status' => 'registered',
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
        'chief_complaint' => 'Persistent headache',
        'requires_priority' => false,
        'is_pediatric' => false,
        'poisoning_case' => false,
        'snake_bite_case' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::query()->findOrFail($userId);
    $this->actingAs($user);

    $visit = PatientVisit::query()->with('triage')->findOrFail($visitId);

    $consultation = resolve(CreateConsultation::class)->handle($visit, CreateConsultationDTO::fromRequest(createConsultationActionRequest([
        'assessment' => 'Likely migraine',
        'plan' => 'Analgesia and review if symptoms persist',
    ])));

    expect($consultation->doctor_id)->toBe($staffId)
        ->and($consultation->consultation_type)->toBe(ConsultationType::OPD)
        ->and($consultation->chief_complaint)->toBe('Persistent headache')
        ->and($consultation->assessment)->toBe('Likely migraine')
        ->and($consultation->plan)->toBe('Analgesia and review if symptoms persist')
        ->and($consultation->started_at)->not->toBeNull()
        ->and($visit->fresh()->status)->toBe(VisitStatus::IN_PROGRESS)
        ->and($visit->fresh()->started_at)->not->toBeNull();

    expect(Activity::query()
        ->where('log_name', 'clinical')
        ->where('event', 'consultation.started')
        ->where('subject_type', $consultation->getMorphClass())
        ->where('subject_id', $consultation->id)
        ->exists())->toBeTrue();
});

it('syncs a consultation charge when a matching consultation facility service is configured', function (): void {
    $tenantId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $triageId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $currencyId = seedTenantContext($tenantId)['currency_id'];

    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $currencyId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-CONSULT-BILL',
        'first_name' => 'Billing',
        'last_name' => 'Doctor',
        'email' => 'billing-doctor@example.com',
        'type' => 'medical',
        'hire_date' => now()->toDateString(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users')->insert([
        'id' => $userId,
        'staff_id' => $staffId,
        'tenant_id' => $tenantId,
        'email' => 'consult-billing-user@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-CONSULT-BILL',
        'visit_type' => 'follow_up',
        'status' => 'registered',
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

    DB::table('triage_records')->insert([
        'id' => $triageId,
        'tenant_id' => $tenantId,
        'visit_id' => $visitId,
        'nurse_id' => $staffId,
        'triage_datetime' => now(),
        'triage_grade' => 'green',
        'attendance_type' => 're_attendance',
        'conscious_level' => 'alert',
        'mobility_status' => 'independent',
        'chief_complaint' => 'Persistent cough',
        'requires_priority' => false,
        'is_pediatric' => false,
        'poisoning_case' => false,
        'snake_bite_case' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $chargeMasterId = (string) Str::uuid();
    $facilityServiceId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $facilityServiceId,
        'tenant_id' => $tenantId,
        'service_code' => 'SVC-CONSULT-FOLLOW',
        'name' => 'Follow-up Consultation',
        'category' => 'consultation',
        'description' => 'Follow-up Consultation',
        'cost_price' => null,
        'selling_price' => 21000,
        'is_billable' => true,
        'is_consultation' => true,
        'consultation_type' => 'follow_up',
        'charge_master_id' => $chargeMasterId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => null,
        'item_code' => 'SVC-CONSULT-FOLLOW',
        'description' => 'Follow-up Consultation',
        'billable_type' => 'service',
        'billable_id' => $facilityServiceId,
        'unit_price' => 21000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::query()->findOrFail($userId);
    $this->actingAs($user);

    $visit = PatientVisit::query()->with('triage')->findOrFail($visitId);

    $consultation = resolve(CreateConsultation::class)->handle($visit, CreateConsultationDTO::fromRequest(createConsultationActionRequest([
        'assessment' => 'Likely allergic cough',
        'plan' => 'Treat and review',
    ])));

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $visitId)
        ->where('source_type', $consultation->getMorphClass())
        ->where('source_id', $consultation->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and($consultation->consultation_type)->toBe(ConsultationType::FOLLOW_UP)
        ->and($charge->charge_code)->toBe('SVC-CONSULT-FOLLOW')
        ->and($charge->charge_master_id)->toBe($chargeMasterId)
        ->and($charge->description)->toBe('Consultation: Follow-up Consultation')
        ->and((float) $charge->unit_price)->toBe(21000.0)
        ->and((float) $charge->line_total)->toBe(21000.0);
});

it('uses an explicit consultation type to resolve the billing fee', function (): void {
    $tenantId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $staffId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $triageId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $currencyId = seedTenantContext($tenantId)['currency_id'];

    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $currencyId);

    DB::table('staff')->insert([
        'id' => $staffId,
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-CONSULT-REVIEW',
        'first_name' => 'Review',
        'last_name' => 'Doctor',
        'email' => 'review-doctor@example.com',
        'type' => 'medical',
        'hire_date' => now()->toDateString(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users')->insert([
        'id' => $userId,
        'staff_id' => $staffId,
        'tenant_id' => $tenantId,
        'email' => 'review-doctor-user@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-CONSULT-REVIEW',
        'visit_type' => 'new',
        'status' => 'registered',
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
        'chief_complaint' => 'Repeat chest pain review',
        'requires_priority' => false,
        'is_pediatric' => false,
        'poisoning_case' => false,
        'snake_bite_case' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $chargeMasterId = (string) Str::uuid();
    $facilityServiceId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $facilityServiceId,
        'tenant_id' => $tenantId,
        'service_code' => 'SVC-CONSULT-REVIEW',
        'name' => 'Review Consultation',
        'category' => 'consultation',
        'description' => 'Review Consultation',
        'cost_price' => null,
        'selling_price' => 25000,
        'is_billable' => true,
        'is_consultation' => true,
        'consultation_type' => 'review',
        'charge_master_id' => $chargeMasterId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => null,
        'item_code' => 'SVC-CONSULT-REVIEW',
        'description' => 'Review Consultation',
        'billable_type' => 'service',
        'billable_id' => $facilityServiceId,
        'unit_price' => 25000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::query()->findOrFail($userId);
    $this->actingAs($user);

    $visit = PatientVisit::query()->with('triage')->findOrFail($visitId);

    $consultation = resolve(CreateConsultation::class)->handle($visit, CreateConsultationDTO::fromRequest(createConsultationActionRequest([
        'consultation_type' => 'review',
        'assessment' => 'Stable for review visit',
        'plan' => 'Continue current management',
    ])));

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $visitId)
        ->where('source_type', $consultation->getMorphClass())
        ->where('source_id', $consultation->id)
        ->first();

    expect($consultation->consultation_type)->toBe(ConsultationType::REVIEW)
        ->and($charge)->not()->toBeNull()
        ->and($charge->charge_code)->toBe('SVC-CONSULT-REVIEW')
        ->and((float) $charge->line_total)->toBe(25000.0);
});
