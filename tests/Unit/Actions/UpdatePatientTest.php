<?php

declare(strict_types=1);

use App\Actions\UpdatePatient;
use App\Data\Patient\UpdatePatientDTO;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('updates a patient using a typed dto', function (): void {
    $tenantId = (string) Str::uuid();
    $patientId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);

    DB::table('patients')
        ->where('id', $patientId)
        ->update([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com',
            'date_of_birth' => null,
            'age' => 10,
            'age_units' => 'year',
        ]);

    $patient = Patient::query()->findOrFail($patientId);

    $dto = new UpdatePatientDTO(
        firstName: 'Calvin',
        lastName: 'Rush',
        middleName: null,
        ageInputMode: 'dob',
        dateOfBirth: '1985-08-31',
        age: null,
        ageUnits: null,
        gender: 'male',
        email: 'patient@example.com',
        phoneNumber: '+256700000010',
        alternativePhone: null,
        nextOfKinName: 'Sarah',
        nextOfKinPhone: '+256700000011',
        nextOfKinRelationship: 'mother',
        addressId: null,
        maritalStatus: null,
        occupation: 'Teacher',
        religion: null,
        countryId: null,
        bloodGroup: 'o_positive',
    );

    $updated = resolve(UpdatePatient::class)->handle($patient, $dto);

    expect($updated->first_name)->toBe('Calvin')
        ->and($updated->last_name)->toBe('Rush')
        ->and($updated->date_of_birth?->toDateString())->toBe('1985-08-31')
        ->and($updated->age)->toBeNull()
        ->and($updated->email)->toBe('patient@example.com')
        ->and($updated->next_of_kin_name)->toBe('Sarah')
        ->and($updated->occupation)->toBe('Teacher')
        ->and($updated->blood_group)->toBe('o_positive');
});
