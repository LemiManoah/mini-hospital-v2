<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InventoryLocationType;
use App\Models\FacilityBranch;
use App\Models\InventoryLocation;
use App\Models\Tenant;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;

final class InventoryLocationSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $creator = $tenant instanceof Tenant ? $this->cityGeneralRegistrar($tenant) : null;
        $mainBranch = $tenant instanceof Tenant ? $this->cityGeneralMainBranch($tenant) : null;

        if (! $tenant instanceof Tenant || ! $mainBranch instanceof FacilityBranch) {
            return;
        }

        foreach ($this->locations() as $location) {
            InventoryLocation::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $mainBranch->id,
                    'location_code' => $location['location_code'],
                ],
                [
                    'name' => $location['name'],
                    'type' => $location['type'],
                    'description' => $location['description'],
                    'is_dispensing_point' => $location['is_dispensing_point'],
                    'is_active' => true,
                    'created_by' => $creator?->id,
                    'updated_by' => $creator?->id,
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     location_code: string,
     *     name: string,
     *     type: InventoryLocationType,
     *     description: string,
     *     is_dispensing_point: bool
     * }>
     */
    private function locations(): array
    {
        return [
            [
                'location_code' => 'CGH-MAIN-STORE',
                'name' => 'Main Medical Store',
                'type' => InventoryLocationType::MAIN_STORE,
                'description' => 'Central stock holding point for City General Hospital main branch.',
                'is_dispensing_point' => false,
            ],
            [
                'location_code' => 'CGH-MAIN-PHARM',
                'name' => 'Main Pharmacy',
                'type' => InventoryLocationType::PHARMACY,
                'description' => 'Primary dispensing counter for outpatient prescriptions at the main branch.',
                'is_dispensing_point' => true,
            ],
            [
                'location_code' => 'CGH-MAIN-LAB',
                'name' => 'Laboratory Store',
                'type' => InventoryLocationType::LABORATORY,
                'description' => 'Laboratory reagent and consumable store for the main branch.',
                'is_dispensing_point' => false,
            ],
            [
                'location_code' => 'CGH-MAIN-PROC',
                'name' => 'Procedure Room Cabinet',
                'type' => InventoryLocationType::PROCEDURE_ROOM,
                'description' => 'Fast-access cabinet for treatment room consumables and emergency items.',
                'is_dispensing_point' => false,
            ],
        ];
    }
}
