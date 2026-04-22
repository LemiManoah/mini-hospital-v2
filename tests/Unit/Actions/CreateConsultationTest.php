<?php

declare(strict_types=1);

use App\Actions\CreateConsultation;
use App\Data\Clinical\CreateConsultationDTO;
use App\Enums\VisitStatus;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createConsultationActionRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
    {
        public function __construct(private array $validatedInput)
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
        ->and($consultation->chief_complaint)->toBe('Persistent headache')
        ->and($consultation->assessment)->toBe('Likely migraine')
        ->and($consultation->plan)->toBe('Analgesia and review if symptoms persist')
        ->and($consultation->started_at)->not->toBeNull()
        ->and($visit->fresh()->status)->toBe(VisitStatus::IN_PROGRESS)
        ->and($visit->fresh()->started_at)->not->toBeNull();
});
