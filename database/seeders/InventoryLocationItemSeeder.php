<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationItem;
use App\Models\Tenant;
use Database\Seeders\Concerns\InteractsWithQrooMedicalCenter;
use Illuminate\Database\Seeder;

final class InventoryLocationItemSeeder extends Seeder
{
    use InteractsWithQrooMedicalCenter;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $creator = $tenant instanceof Tenant ? $this->cityGeneralRegistrar($tenant) : null;
        $mainBranch = $tenant instanceof Tenant ? $this->cityGeneralMainBranch($tenant) : null;

        if (! $tenant instanceof Tenant || ! $mainBranch instanceof FacilityBranch) {
            return;
        }

        $locations = InventoryLocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $mainBranch->id)
            ->get()
            ->keyBy('location_code');

        $items = InventoryItem::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy(static fn (InventoryItem $item): string => $item->generic_name ?? $item->name);

        foreach ($this->assignments() as $assignment) {
            $location = $locations->get($assignment['location_code']);
            $item = $items->get($assignment['item_name']);
            if ($location === null) {
                continue;
            }

            if ($item === null) {
                continue;
            }

            InventoryLocationItem::query()->updateOrCreate(
                [
                    'inventory_location_id' => $location->id,
                    'inventory_item_id' => $item->id,
                ],
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $mainBranch->id,
                    'minimum_stock_level' => $assignment['minimum_stock_level'],
                    'reorder_level' => $assignment['reorder_level'],
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
     *     item_name: string,
     *     minimum_stock_level: int,
     *     reorder_level: int
     * }>
     */
    private function assignments(): array
    {
        return [
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Paracetamol', 'minimum_stock_level' => 800, 'reorder_level' => 1200],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Amoxicillin', 'minimum_stock_level' => 500, 'reorder_level' => 900],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Artemether/Lumefantrine', 'minimum_stock_level' => 240, 'reorder_level' => 480],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Salbutamol', 'minimum_stock_level' => 30, 'reorder_level' => 60],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Omeprazole', 'minimum_stock_level' => 200, 'reorder_level' => 400],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Ceftriaxone', 'minimum_stock_level' => 80, 'reorder_level' => 150],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Examination Gloves', 'minimum_stock_level' => 120, 'reorder_level' => 200],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => '5ml Syringe', 'minimum_stock_level' => 200, 'reorder_level' => 350],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'CBC Reagent Pack', 'minimum_stock_level' => 20, 'reorder_level' => 40],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Malaria Rapid Test Kit', 'minimum_stock_level' => 60, 'reorder_level' => 120],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Sterile Gauze Roll', 'minimum_stock_level' => 80, 'reorder_level' => 150],
            ['location_code' => 'QMC-MAIN-STORE', 'item_name' => 'Sharps Container', 'minimum_stock_level' => 12, 'reorder_level' => 24],
            ['location_code' => 'QMC-MAIN-PHARM', 'item_name' => 'Paracetamol', 'minimum_stock_level' => 300, 'reorder_level' => 500],
            ['location_code' => 'QMC-MAIN-PHARM', 'item_name' => 'Amoxicillin', 'minimum_stock_level' => 180, 'reorder_level' => 300],
            ['location_code' => 'QMC-MAIN-PHARM', 'item_name' => 'Artemether/Lumefantrine', 'minimum_stock_level' => 120, 'reorder_level' => 180],
            ['location_code' => 'QMC-MAIN-PHARM', 'item_name' => 'Salbutamol', 'minimum_stock_level' => 12, 'reorder_level' => 24],
            ['location_code' => 'QMC-MAIN-PHARM', 'item_name' => 'Omeprazole', 'minimum_stock_level' => 100, 'reorder_level' => 180],
            ['location_code' => 'QMC-MAIN-LAB', 'item_name' => 'CBC Reagent Pack', 'minimum_stock_level' => 12, 'reorder_level' => 20],
            ['location_code' => 'QMC-MAIN-LAB', 'item_name' => 'Malaria Rapid Test Kit', 'minimum_stock_level' => 40, 'reorder_level' => 80],
            ['location_code' => 'QMC-MAIN-LAB', 'item_name' => 'Examination Gloves', 'minimum_stock_level' => 40, 'reorder_level' => 80],
            ['location_code' => 'QMC-MAIN-PROC', 'item_name' => 'Ceftriaxone', 'minimum_stock_level' => 20, 'reorder_level' => 40],
            ['location_code' => 'QMC-MAIN-PROC', 'item_name' => '5ml Syringe', 'minimum_stock_level' => 60, 'reorder_level' => 120],
            ['location_code' => 'QMC-MAIN-PROC', 'item_name' => 'Sterile Gauze Roll', 'minimum_stock_level' => 30, 'reorder_level' => 60],
            ['location_code' => 'QMC-MAIN-PROC', 'item_name' => 'Sharps Container', 'minimum_stock_level' => 4, 'reorder_level' => 8],
        ];
    }
}
