<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            CurrencySeeder::class,
            SubscriptionPackageSeeder::class,
            AllergenSeeder::class,
            UnitSeeder::class,
            DrugSeeder::class,
            AddressSeeder::class,
            FacilitySeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            DepartmentSeeder::class,
            StaffPositionSeeder::class,
            InsuranceCompanySeeder::class,
            InsurancePackageSeeder::class,
            // StaffSeeder::class,
            // UserSeeder::class,
            SupportUserSeeder::class,
            ClinicSeeder::class,
            PatientSeeder::class,
            CityGeneralHospitalSeeder::class,
        ]);
    }
}
