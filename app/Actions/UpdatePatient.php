<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PayerType;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Support\Facades\DB;

final class UpdatePatient
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): Patient
    {
        return DB::transaction(static function () use ($patient, $data): Patient {
            $ageInputMode = (string) ($data['age_input_mode'] ?? 'dob');

            $patient->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $ageInputMode === 'dob' ? ($data['date_of_birth'] ?? null) : null,
                'age' => $ageInputMode === 'age' ? ($data['age'] ?? null) : null,
                'age_units' => $ageInputMode === 'age' ? ($data['age_units'] ?? null) : null,
                'gender' => $data['gender'],
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'],
                'alternative_phone' => $data['alternative_phone'] ?? null,
                'next_of_kin_name' => $data['next_of_kin_name'] ?? null,
                'next_of_kin_phone' => $data['next_of_kin_phone'] ?? null,
                'next_of_kin_relationship' => $data['next_of_kin_relationship'] ?? null,
                'address_id' => $data['address_id'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'religion' => $data['religion'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'default_payer_type' => $data['payer_type'],
            ]);

            if (($data['payer_type'] ?? PayerType::CASH->value) === PayerType::INSURANCE->value) {
                $patient->insurances()->delete();

                PatientInsurance::create([
                    'patient_id' => $patient->id,
                    'insurance_company_id' => $data['insurance_company_id'],
                    'insurance_package_id' => $data['insurance_package_id'],
                ]);
            } else {
                $patient->insurances()->delete();
            }

            return $patient;
        });
    }
}
