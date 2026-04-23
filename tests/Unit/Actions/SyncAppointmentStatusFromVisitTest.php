<?php

declare(strict_types=1);

use App\Actions\SyncAppointmentStatusFromVisit;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('leaves the appointment unchanged when the visit status is null', function (): void {
    $tenantId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $appointmentId = (string) Str::uuid();
    $visitId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);

    DB::table('appointment_categories')->insert([
        'id' => 'appt-category',
        'tenant_id' => $tenantId,
        'name' => 'General',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('appointment_modes')->insert([
        'id' => 'appt-mode',
        'tenant_id' => $tenantId,
        'name' => 'Physical',
        'is_virtual' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('appointments')->insert([
        'id' => $appointmentId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'patient_id' => $patientId,
        'appointment_category_id' => 'appt-category',
        'appointment_mode_id' => 'appt-mode',
        'appointment_date' => now()->toDateString(),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'status' => AppointmentStatus::SCHEDULED->value,
        'reason_for_visit' => 'Review',
        'is_walk_in' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'appointment_id' => $appointmentId,
        'visit_number' => 'VIS-100',
        'visit_type' => 'outpatient',
        'status' => 'registered',
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->findOrFail($visitId);
    $visit->status = null;

    resolve(SyncAppointmentStatusFromVisit::class)->handle($visit);

    $appointment = Appointment::query()->findOrFail($appointmentId);

    expect($appointment->status)->toBe(AppointmentStatus::SCHEDULED)
        ->and($appointment->completed_at)->toBeNull();
});
