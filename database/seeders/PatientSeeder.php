<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\KinRelationship;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Models\Address;
use App\Models\Country;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * @phpstan-type PatientSeedData array{
 *   patient_number: string,
 *   first_name: string,
 *   last_name: string,
 *   middle_name: string|null,
 *   date_of_birth: string,
 *   gender: Gender,
 *   phone_number: string,
 *   email: string|null,
 *   marital_status: MaritalStatus|null,
 *   occupation: string|null,
 *   religion: Religion,
 *   blood_group: BloodGroup,
 *   next_of_kin_name: string,
 *   next_of_kin_phone: string,
 *   next_of_kin_relationship: KinRelationship
 * }
 */
final class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();

        if (! $tenant) {
            return;
        }

        $addresses = Address::query()->inRandomOrder()->take(10)->get()->values();
        $country = Country::query()->where('country_name', 'Uganda')->first()
            ?? Country::query()->first();

        if ($addresses->isEmpty() || ! $country instanceof Country) {
            return;
        }

        /** @var list<PatientSeedData> $patients */
        $patients = [
            ['patient_number' => 'PAT-0001', 'first_name' => 'Amara', 'last_name' => 'Nakigozi', 'middle_name' => 'Grace', 'date_of_birth' => '1990-05-14', 'gender' => Gender::FEMALE, 'phone_number' => '+256-700-100-001', 'email' => 'amara.nakigozi@example.com', 'marital_status' => MaritalStatus::MARRIED, 'occupation' => 'Teacher', 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::A_POSITIVE, 'next_of_kin_name' => 'Samuel Nakigozi', 'next_of_kin_phone' => '+256-700-200-001', 'next_of_kin_relationship' => KinRelationship::SPOUSE],
            ['patient_number' => 'PAT-0002', 'first_name' => 'Brian', 'last_name' => 'Ochieng', 'middle_name' => null, 'date_of_birth' => '1985-08-22', 'gender' => Gender::MALE, 'phone_number' => '+256-700-100-002', 'email' => null, 'marital_status' => MaritalStatus::SINGLE, 'occupation' => 'Engineer', 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::O_POSITIVE, 'next_of_kin_name' => 'Rose Ochieng', 'next_of_kin_phone' => '+256-700-200-002', 'next_of_kin_relationship' => KinRelationship::SIBLING],
            ['patient_number' => 'PAT-0003', 'first_name' => 'Fatuma', 'last_name' => 'Namusisi', 'middle_name' => 'Zainab', 'date_of_birth' => '1975-02-10', 'gender' => Gender::FEMALE, 'phone_number' => '+256-700-100-003', 'email' => 'fatuma.namusisi@example.com', 'marital_status' => MaritalStatus::MARRIED, 'occupation' => 'Businesswoman', 'religion' => Religion::MUSLIM, 'blood_group' => BloodGroup::B_POSITIVE, 'next_of_kin_name' => 'Hassan Namusisi', 'next_of_kin_phone' => '+256-700-200-003', 'next_of_kin_relationship' => KinRelationship::SPOUSE],
            ['patient_number' => 'PAT-0004', 'first_name' => 'Charles', 'last_name' => 'Ssekandi', 'middle_name' => 'Patrick', 'date_of_birth' => '1960-11-30', 'gender' => Gender::MALE, 'phone_number' => '+256-700-100-004', 'email' => null, 'marital_status' => MaritalStatus::WIDOWED, 'occupation' => 'Retired', 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::AB_POSITIVE, 'next_of_kin_name' => 'David Ssekandi', 'next_of_kin_phone' => '+256-700-200-004', 'next_of_kin_relationship' => KinRelationship::CHILD],
            ['patient_number' => 'PAT-0005', 'first_name' => 'Diana', 'last_name' => 'Akello', 'middle_name' => null, 'date_of_birth' => '2000-03-18', 'gender' => Gender::FEMALE, 'phone_number' => '+256-700-100-005', 'email' => 'diana.akello@example.com', 'marital_status' => MaritalStatus::SINGLE, 'occupation' => 'Student', 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::O_NEGATIVE, 'next_of_kin_name' => 'Margaret Akello', 'next_of_kin_phone' => '+256-700-200-005', 'next_of_kin_relationship' => KinRelationship::PARENT],
            ['patient_number' => 'PAT-0006', 'first_name' => 'George', 'last_name' => 'Tumwine', 'middle_name' => 'Robert', 'date_of_birth' => '1978-07-04', 'gender' => Gender::MALE, 'phone_number' => '+256-700-100-006', 'email' => null, 'marital_status' => MaritalStatus::MARRIED, 'occupation' => 'Farmer', 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::B_NEGATIVE, 'next_of_kin_name' => 'Agnes Tumwine', 'next_of_kin_phone' => '+256-700-200-006', 'next_of_kin_relationship' => KinRelationship::SPOUSE],
            ['patient_number' => 'PAT-0007', 'first_name' => 'Hawa', 'last_name' => 'Nantume', 'middle_name' => 'Khadija', 'date_of_birth' => '1993-12-01', 'gender' => Gender::FEMALE, 'phone_number' => '+256-700-100-007', 'email' => 'hawa.nantume@example.com', 'marital_status' => MaritalStatus::DIVORCED, 'occupation' => 'Nurse', 'religion' => Religion::MUSLIM, 'blood_group' => BloodGroup::A_NEGATIVE, 'next_of_kin_name' => 'Ibrahim Nantume', 'next_of_kin_phone' => '+256-700-200-007', 'next_of_kin_relationship' => KinRelationship::SIBLING],
            ['patient_number' => 'PAT-0008', 'first_name' => 'Isaac', 'last_name' => 'Wanyama', 'middle_name' => null, 'date_of_birth' => '2015-06-09', 'gender' => Gender::MALE, 'phone_number' => '+256-700-100-008', 'email' => null, 'marital_status' => null, 'occupation' => null, 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::O_POSITIVE, 'next_of_kin_name' => 'Joyce Wanyama', 'next_of_kin_phone' => '+256-700-200-008', 'next_of_kin_relationship' => KinRelationship::PARENT],
            ['patient_number' => 'PAT-0009', 'first_name' => 'Josephine', 'last_name' => 'Atim', 'middle_name' => 'Mary', 'date_of_birth' => '1968-04-25', 'gender' => Gender::FEMALE, 'phone_number' => '+256-700-100-009', 'email' => null, 'marital_status' => MaritalStatus::MARRIED, 'occupation' => 'Accountant', 'religion' => Religion::CHRISTIAN, 'blood_group' => BloodGroup::AB_NEGATIVE, 'next_of_kin_name' => 'Peter Atim', 'next_of_kin_phone' => '+256-700-200-009', 'next_of_kin_relationship' => KinRelationship::SPOUSE],
            ['patient_number' => 'PAT-0010', 'first_name' => 'Kenneth', 'last_name' => 'Byamugisha', 'middle_name' => 'Joel', 'date_of_birth' => '1955-09-17', 'gender' => Gender::MALE, 'phone_number' => '+256-700-100-010', 'email' => null, 'marital_status' => MaritalStatus::MARRIED, 'occupation' => 'Lawyer', 'religion' => Religion::OTHER, 'blood_group' => BloodGroup::A_POSITIVE, 'next_of_kin_name' => 'Margaret Byamugisha', 'next_of_kin_phone' => '+256-700-200-010', 'next_of_kin_relationship' => KinRelationship::SPOUSE],
        ];

        $addressIndex = 0;

        foreach ($patients as $data) {
            $address = $addresses->get($addressIndex % $addresses->count());
            $addressIndex++;

            if (! $address instanceof Address) {
                throw new RuntimeException('PatientSeeder expected a valid address instance.');
            }

            Patient::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'patient_number' => $data['patient_number']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'middle_name' => $data['middle_name'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender']->value,
                    'phone_number' => $data['phone_number'],
                    'email' => $data['email'],
                    'marital_status' => $data['marital_status']?->value,
                    'occupation' => $data['occupation'],
                    'religion' => $data['religion']->value,
                    'blood_group' => $data['blood_group']->value,
                    'next_of_kin_name' => $data['next_of_kin_name'],
                    'next_of_kin_phone' => $data['next_of_kin_phone'],
                    'next_of_kin_relationship' => $data['next_of_kin_relationship']->value,
                    'address_id' => $address->id,
                    'country_id' => $country->id,
                    'tenant_id' => $tenant->id,
                ],
            );
        }
    }
}
