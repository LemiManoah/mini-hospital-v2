<?php

declare(strict_types=1);

use App\Actions\CreateVitalSign;
use App\Data\Clinical\CreateVitalSignDTO;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createVitalSignActionRequest(array $validated): FormRequest
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

it('normalizes numeric strings before calculating derived vital sign values', function (): void {
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
        'employee_number' => 'EMP-001',
        'first_name' => 'Test',
        'last_name' => 'Nurse',
        'email' => 'nurse@example.com',
        'type' => 'nursing',
        'hire_date' => now()->toDateString(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users')->insert([
        'id' => $userId,
        'staff_id' => $staffId,
        'tenant_id' => $tenantId,
        'email' => 'user@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'visit_number' => 'VIS-001',
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
        'chief_complaint' => 'Headache',
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

    $vitalSign = resolve(CreateVitalSign::class)->handle($visit, CreateVitalSignDTO::fromRequest(createVitalSignActionRequest([
        'systolic_bp' => '120',
        'diastolic_bp' => '80',
        'height_cm' => '180',
        'weight_kg' => '75',
        'temperature_unit' => 'celsius',
        'blood_glucose_unit' => 'mg_dl',
    ])));

    expect($vitalSign->systolic_bp)->toBe(120)
        ->and($vitalSign->diastolic_bp)->toBe(80)
        ->and($vitalSign->map)->toBe(93)
        ->and((float) $vitalSign->bmi)->toBe(23.15);
});
