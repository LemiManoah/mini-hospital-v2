<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class QrooMedicalCenterSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            QrooMedicalCenterReferenceSeeder::class,
            QrooMedicalCenterInventoryUserSeeder::class,
            InventoryItemSeeder::class,
            InventoryLocationSeeder::class,
            InventoryLocationItemSeeder::class,
            QrooMedicalCenterInventoryWorkflowSeeder::class,
            QrooMedicalCenterPatientSeeder::class,
            QrooMedicalCenterEncounterSeeder::class,
            QrooMedicalCenterReportSeeder::class,
        ]);
    }
}
