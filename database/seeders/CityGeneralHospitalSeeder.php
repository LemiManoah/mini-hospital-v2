<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class CityGeneralHospitalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CityGeneralHospitalReferenceSeeder::class,
            CityGeneralHospitalInventoryUserSeeder::class,
            InventoryItemSeeder::class,
            InventoryLocationSeeder::class,
            InventoryLocationItemSeeder::class,
            CityGeneralHospitalInventoryWorkflowSeeder::class,
            CityGeneralHospitalPatientSeeder::class,
            CityGeneralHospitalEncounterSeeder::class,
            CityGeneralHospitalReportSeeder::class,
        ]);
    }
}
