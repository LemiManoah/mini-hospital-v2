<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Country;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\KinRelationship;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Models\Patient;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;

final class CityGeneralHospitalPatientSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $country = $this->ugandaCountry();

        if (!$tenant instanceof Tenant || !$country instanceof Country) {
            return;
        }

        $patients = [
            [
                'patient_number' => 'CGH-PAT-1001',
                'first_name' => 'Aisha',
                'last_name' => 'Nakanwagi',
                'date_of_birth' => '1994-08-12',
                'gender' => Gender::FEMALE,
                'phone_number' => '+256-772-100-101',
                'email' => 'aisha.nakanwagi@example.com',
                'marital_status' => MaritalStatus::MARRIED,
                'occupation' => 'Shop Manager',
                'religion' => Religion::MUSLIM,
                'blood_group' => BloodGroup::O_POSITIVE,
                'next_of_kin_name' => 'Yusuf Katende',
                'next_of_kin_phone' => '+256-772-200-101',
                'next_of_kin_relationship' => KinRelationship::SPOUSE,
                'address' => ['city' => 'Kampala', 'district' => 'Nakawa', 'state' => 'Central'],
            ],
            [
                'patient_number' => 'CGH-PAT-1002',
                'first_name' => 'Joel',
                'last_name' => 'Byaruhanga',
                'date_of_birth' => '1988-02-03',
                'gender' => Gender::MALE,
                'phone_number' => '+256-772-100-102',
                'email' => 'joel.byaruhanga@example.com',
                'marital_status' => MaritalStatus::MARRIED,
                'occupation' => 'Driver',
                'religion' => Religion::CHRISTIAN,
                'blood_group' => BloodGroup::A_POSITIVE,
                'next_of_kin_name' => 'Doreen Byaruhanga',
                'next_of_kin_phone' => '+256-772-200-102',
                'next_of_kin_relationship' => KinRelationship::SPOUSE,
                'address' => ['city' => 'Kampala', 'district' => 'Rubaga', 'state' => 'Central'],
            ],
            [
                'patient_number' => 'CGH-PAT-1003',
                'first_name' => 'Ruth',
                'last_name' => 'Nansubuga',
                'date_of_birth' => '1999-11-25',
                'gender' => Gender::FEMALE,
                'phone_number' => '+256-772-100-103',
                'email' => 'ruth.nansubuga@example.com',
                'marital_status' => MaritalStatus::SINGLE,
                'occupation' => 'University Student',
                'religion' => Religion::CHRISTIAN,
                'blood_group' => BloodGroup::B_POSITIVE,
                'next_of_kin_name' => 'Harriet Nansubuga',
                'next_of_kin_phone' => '+256-772-200-103',
                'next_of_kin_relationship' => KinRelationship::PARENT,
                'address' => ['city' => 'Kampala', 'district' => 'Makindye', 'state' => 'Central'],
            ],
            [
                'patient_number' => 'CGH-PAT-1004',
                'first_name' => 'Isaac',
                'last_name' => 'Tumusiime',
                'date_of_birth' => '2016-05-06',
                'gender' => Gender::MALE,
                'phone_number' => '+256-772-100-104',
                'email' => null,
                'marital_status' => null,
                'occupation' => null,
                'religion' => Religion::CHRISTIAN,
                'blood_group' => BloodGroup::O_POSITIVE,
                'next_of_kin_name' => 'Sharon Tumusiime',
                'next_of_kin_phone' => '+256-772-200-104',
                'next_of_kin_relationship' => KinRelationship::PARENT,
                'address' => ['city' => 'Entebbe', 'district' => 'Wakiso', 'state' => 'Central'],
            ],
            [
                'patient_number' => 'CGH-PAT-1005',
                'first_name' => 'Sarah',
                'last_name' => 'Atwine',
                'date_of_birth' => '1972-09-18',
                'gender' => Gender::FEMALE,
                'phone_number' => '+256-772-100-105',
                'email' => 'sarah.atwine@example.com',
                'marital_status' => MaritalStatus::WIDOWED,
                'occupation' => 'Tailor',
                'religion' => Religion::CHRISTIAN,
                'blood_group' => BloodGroup::AB_POSITIVE,
                'next_of_kin_name' => 'Martin Atwine',
                'next_of_kin_phone' => '+256-772-200-105',
                'next_of_kin_relationship' => KinRelationship::CHILD,
                'address' => ['city' => 'Mukono', 'district' => 'Mukono', 'state' => 'Central'],
            ],
        ];

        foreach ($patients as $patientData) {
            $address = $this->upsertAddress($patientData['address'], $country);

            Patient::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'patient_number' => $patientData['patient_number'],
                ],
                [
                    'first_name' => $patientData['first_name'],
                    'last_name' => $patientData['last_name'],
                    'date_of_birth' => $patientData['date_of_birth'],
                    'gender' => $patientData['gender']->value,
                    'phone_number' => $patientData['phone_number'],
                    'email' => $patientData['email'],
                    'marital_status' => $patientData['marital_status']?->value,
                    'occupation' => $patientData['occupation'],
                    'religion' => $patientData['religion']->value,
                    'blood_group' => $patientData['blood_group']->value,
                    'next_of_kin_name' => $patientData['next_of_kin_name'],
                    'next_of_kin_phone' => $patientData['next_of_kin_phone'],
                    'next_of_kin_relationship' => $patientData['next_of_kin_relationship']->value,
                    'address_id' => $address->id,
                    'country_id' => $country->id,
                ],
            );
        }
    }
}
