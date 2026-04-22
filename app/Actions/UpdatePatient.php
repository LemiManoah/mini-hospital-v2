<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\UpdatePatientDTO;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

final class UpdatePatient
{
    public function handle(Patient $patient, UpdatePatientDTO $data): Patient
    {
        return DB::transaction(static function () use ($patient, $data): Patient {
            $patient->update([
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'middle_name' => $data->middleName,
                'date_of_birth' => $data->ageInputMode === 'dob' ? $data->dateOfBirth : null,
                'age' => $data->ageInputMode === 'age' ? $data->age : null,
                'age_units' => $data->ageInputMode === 'age' ? $data->ageUnits : null,
                'gender' => $data->gender,
                'email' => $data->email,
                'phone_number' => $data->phoneNumber,
                'alternative_phone' => $data->alternativePhone,
                'next_of_kin_name' => $data->nextOfKinName,
                'next_of_kin_phone' => $data->nextOfKinPhone,
                'next_of_kin_relationship' => $data->nextOfKinRelationship,
                'address_id' => $data->addressId,
                'marital_status' => $data->maritalStatus,
                'occupation' => $data->occupation,
                'religion' => $data->religion,
                'country_id' => $data->countryId,
                'blood_group' => $data->bloodGroup,
            ]);

            return $patient;
        });
    }
}
