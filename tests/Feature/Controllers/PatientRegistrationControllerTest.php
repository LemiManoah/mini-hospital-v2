<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createPatientRegistrationContext(): array
{
    $tenantContext = seedTenantContext();

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'currency_id' => $tenantContext['currency_id'],
        'name' => 'City General Hospital',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo('patients.create');

    return [$tenantContext['tenant_id'], $branch, $user];
}

it('registers a patient and opens the new visit page using the seeded numbering format', function (): void {
    [$tenantId, $branch, $user] = createPatientRegistrationContext();

    $existingPatient = Patient::query()->create([
        'tenant_id' => $tenantId,
        'patient_number' => 'CGH-PAT-1005',
        'first_name' => 'Existing',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000001',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    PatientVisit::query()->create([
        'tenant_id' => $tenantId,
        'patient_id' => $existingPatient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'CGH-VIS-2026005',
        'visit_type' => VisitType::OPD_CONSULTATION->value,
        'status' => VisitStatus::REGISTERED,
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('patients.store'), [
            'first_name' => 'Calvin',
            'last_name' => 'Rush',
            'age_input_mode' => 'dob',
            'date_of_birth' => '1985-08-31',
            'gender' => 'male',
            'phone_number' => '+256700000010',
            'visit_type' => VisitType::OPD_CONSULTATION->value,
            'billing_type' => 'cash',
            'redirect_to' => 'visit',
        ]);

    $newPatient = Patient::query()
        ->where('first_name', 'Calvin')
        ->where('last_name', 'Rush')
        ->firstOrFail();

    $newVisit = PatientVisit::query()
        ->where('patient_id', $newPatient->id)
        ->firstOrFail();

    $response->assertRedirectToRoute('visits.show', $newVisit);
    $response->assertSessionHas('success', 'Patient registered and visit started successfully.');

    expect($newPatient->patient_number)->toBe('CGH-PAT-1006');
    expect($newVisit->visit_number)->toBe('CGH-VIS-2026006');
});

it('rejects invalid dropdown values during patient registration', function (): void {
    [, $branch, $user] = createPatientRegistrationContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->from(route('patients.create'))
        ->post(route('patients.store'), [
            'first_name' => 'Calvin',
            'last_name' => 'Rush',
            'age_input_mode' => 'dob',
            'date_of_birth' => '1985-08-31',
            'gender' => 'male',
            'phone_number' => '+256700000010',
            'next_of_kin_relationship' => 'favourite-cousin',
            'marital_status' => 'complicated',
            'religion' => 'secret-society',
            'blood_group' => 'ZZ',
            'visit_type' => 'made-up-visit',
            'billing_type' => 'cash',
            'redirect_to' => 'visit',
        ]);

    $response->assertRedirect(route('patients.create'));
    $response->assertSessionHasErrors([
        'next_of_kin_relationship',
        'marital_status',
        'religion',
        'blood_group',
        'visit_type',
    ]);

    expect(Patient::query()->count())->toBe(0);
    expect(PatientVisit::query()->count())->toBe(0);
});
