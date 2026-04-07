<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use App\Models\Country;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\Unit;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;

final class InventoryItemSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $country = $this->ugandaCountry();
        $creator = $tenant instanceof Tenant ? $this->cityGeneralRegistrar($tenant) : null;
        $mainBranch = $tenant instanceof Tenant ? $this->cityGeneralMainBranch($tenant) : null;

        if (! $tenant instanceof Tenant || ! $country instanceof Country || ! $mainBranch instanceof FacilityBranch) {
            return;
        }

        $unitIds = Unit::query()
            ->pluck('id', 'symbol')
            ->map(static fn (mixed $id): ?string => is_string($id) ? $id : null);

        foreach ($this->drugItems() as $item) {
            InventoryItem::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'item_type' => InventoryItemType::DRUG->value,
                    'generic_name' => $item['generic_name'],
                    'strength' => $item['strength'],
                ],
                [
                    'tenant_id' => $tenant->id,
                    'item_type' => InventoryItemType::DRUG,
                    'name' => $item['generic_name'],
                    'generic_name' => $item['generic_name'],
                    'brand_name' => $item['brand_name'],
                    'category' => $item['category'],
                    'dosage_form' => $item['dosage_form'],
                    'strength' => $item['strength'],
                    'manufacturer' => $item['manufacturer'],
                    'unit_id' => $unitIds[$item['unit_symbol']] ?? null,
                    'description' => $item['description'],
                    'minimum_stock_level' => $item['minimum_stock_level'],
                    'reorder_level' => $item['reorder_level'],
                    'default_purchase_price' => $item['default_purchase_price'],
                    'default_selling_price' => $item['default_selling_price'],
                    'expires' => $item['expires'],
                    'is_controlled' => false,
                    'is_active' => true,
                    'created_by' => $creator?->id,
                    'updated_by' => $creator?->id,
                ],
            );
        }

        foreach ($this->nonDrugItems() as $item) {
            InventoryItem::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'item_type' => $item['item_type']->value,
                    'name' => $item['name'],
                ],
                [
                    'tenant_id' => $tenant->id,
                    'item_type' => $item['item_type'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'unit_id' => $unitIds[$item['unit_symbol']] ?? null,
                    'manufacturer' => $item['manufacturer'],
                    'minimum_stock_level' => $item['minimum_stock_level'],
                    'reorder_level' => $item['reorder_level'],
                    'default_purchase_price' => $item['default_purchase_price'],
                    'default_selling_price' => $item['default_selling_price'],
                    'expires' => $item['expires'],
                    'is_active' => true,
                    'created_by' => $creator?->id,
                    'updated_by' => $creator?->id,
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     generic_name: string,
     *     brand_name: string|null,
     *     category: DrugCategory,
     *     dosage_form: DrugDosageForm,
     *     strength: string,
     *     manufacturer: string|null,
     *     unit_symbol: string,
     *     description: string,
     *     minimum_stock_level: int,
     *     reorder_level: int,
     *     default_purchase_price: int,
     *     default_selling_price: int,
     *     expires: bool
     * }>
     */
    private function drugItems(): array
    {
        return [
            [
                'generic_name' => 'Paracetamol',
                'brand_name' => 'Panadol',
                'category' => DrugCategory::ANALGESIC,
                'dosage_form' => DrugDosageForm::TABLET,
                'strength' => '500mg',
                'manufacturer' => 'GSK',
                'unit_symbol' => 'tab',
                'description' => 'Routine first-line analgesic and antipyretic for outpatient use.',
                'minimum_stock_level' => 800,
                'reorder_level' => 1200,
                'default_purchase_price' => 120,
                'default_selling_price' => 300,
                'expires' => true,
            ],
            [
                'generic_name' => 'Amoxicillin',
                'brand_name' => 'Amoxil',
                'category' => DrugCategory::ANTIBIOTIC,
                'dosage_form' => DrugDosageForm::CAPSULE,
                'strength' => '500mg',
                'manufacturer' => 'GSK',
                'unit_symbol' => 'cap',
                'description' => 'Common oral antibiotic kept in the main branch pharmacy.',
                'minimum_stock_level' => 500,
                'reorder_level' => 900,
                'default_purchase_price' => 180,
                'default_selling_price' => 500,
                'expires' => true,
            ],
            [
                'generic_name' => 'Artemether/Lumefantrine',
                'brand_name' => 'Coartem',
                'category' => DrugCategory::ANTI_MALARIAL,
                'dosage_form' => DrugDosageForm::TABLET,
                'strength' => '20/120mg',
                'manufacturer' => 'Novartis',
                'unit_symbol' => 'tab',
                'description' => 'Frontline antimalarial stock for the Kampala main branch.',
                'minimum_stock_level' => 240,
                'reorder_level' => 480,
                'default_purchase_price' => 350,
                'default_selling_price' => 900,
                'expires' => true,
            ],
            [
                'generic_name' => 'Salbutamol',
                'brand_name' => 'Ventolin',
                'category' => DrugCategory::RESPIRATORY,
                'dosage_form' => DrugDosageForm::INHALER,
                'strength' => '100mcg',
                'manufacturer' => 'GSK',
                'unit_symbol' => 'puff',
                'description' => 'Bronchodilator inhaler used in OPD and emergency stabilization.',
                'minimum_stock_level' => 30,
                'reorder_level' => 60,
                'default_purchase_price' => 18000,
                'default_selling_price' => 26000,
                'expires' => true,
            ],
            [
                'generic_name' => 'Omeprazole',
                'brand_name' => null,
                'category' => DrugCategory::GASTROINTESTINAL,
                'dosage_form' => DrugDosageForm::CAPSULE,
                'strength' => '20mg',
                'manufacturer' => 'Cipla',
                'unit_symbol' => 'cap',
                'description' => 'Proton pump inhibitor for acute gastritis and ulcer care.',
                'minimum_stock_level' => 200,
                'reorder_level' => 400,
                'default_purchase_price' => 220,
                'default_selling_price' => 650,
                'expires' => true,
            ],
            [
                'generic_name' => 'Ceftriaxone',
                'brand_name' => null,
                'category' => DrugCategory::ANTIBIOTIC,
                'dosage_form' => DrugDosageForm::INJECTION,
                'strength' => '1g',
                'manufacturer' => 'Roche',
                'unit_symbol' => 'g',
                'description' => 'Injectable antibiotic for procedure room and emergency care.',
                'minimum_stock_level' => 80,
                'reorder_level' => 150,
                'default_purchase_price' => 2500,
                'default_selling_price' => 6500,
                'expires' => true,
            ],
        ];
    }

    /**
     * @return list<array{
     *     name: string,
     *     item_type: InventoryItemType,
     *     unit_symbol: string,
     *     description: string,
     *     manufacturer: string|null,
     *     minimum_stock_level: int,
     *     reorder_level: int,
     *     default_purchase_price: int,
     *     default_selling_price: int|null,
     *     expires: bool
     * }>
     */
    private function nonDrugItems(): array
    {
        return [
            [
                'name' => 'Examination Gloves',
                'item_type' => InventoryItemType::CONSUMABLE,
                'unit_symbol' => 'sachet',
                'description' => 'Single-use gloves for OPD, treatment room, and laboratory work.',
                'manufacturer' => 'SafeTouch',
                'minimum_stock_level' => 120,
                'reorder_level' => 200,
                'default_purchase_price' => 18000,
                'default_selling_price' => null,
                'expires' => true,
            ],
            [
                'name' => '5ml Syringe',
                'item_type' => InventoryItemType::CONSUMABLE,
                'unit_symbol' => 'sachet',
                'description' => 'Routine disposable syringe used for injections and blood draws.',
                'manufacturer' => 'Becton Dickinson',
                'minimum_stock_level' => 200,
                'reorder_level' => 350,
                'default_purchase_price' => 250,
                'default_selling_price' => null,
                'expires' => true,
            ],
            [
                'name' => 'CBC Reagent Pack',
                'item_type' => InventoryItemType::REAGENT,
                'unit_symbol' => 'sachet',
                'description' => 'Laboratory reagent pack supporting complete blood count processing.',
                'manufacturer' => 'Mindray',
                'minimum_stock_level' => 20,
                'reorder_level' => 40,
                'default_purchase_price' => 145000,
                'default_selling_price' => null,
                'expires' => true,
            ],
            [
                'name' => 'Malaria Rapid Test Kit',
                'item_type' => InventoryItemType::REAGENT,
                'unit_symbol' => 'sachet',
                'description' => 'Rapid diagnostic test kit used by the main branch laboratory.',
                'manufacturer' => 'SD Biosensor',
                'minimum_stock_level' => 60,
                'reorder_level' => 120,
                'default_purchase_price' => 1200,
                'default_selling_price' => null,
                'expires' => true,
            ],
            [
                'name' => 'Sterile Gauze Roll',
                'item_type' => InventoryItemType::SUPPLY,
                'unit_symbol' => 'sachet',
                'description' => 'General dressing and wound care supply for the treatment room.',
                'manufacturer' => 'Medline',
                'minimum_stock_level' => 80,
                'reorder_level' => 150,
                'default_purchase_price' => 3500,
                'default_selling_price' => null,
                'expires' => true,
            ],
            [
                'name' => 'Sharps Container',
                'item_type' => InventoryItemType::OTHER,
                'unit_symbol' => 'sachet',
                'description' => 'Safety container for used needles and sharps disposal.',
                'manufacturer' => 'SafetyBox',
                'minimum_stock_level' => 12,
                'reorder_level' => 24,
                'default_purchase_price' => 9500,
                'default_selling_price' => null,
                'expires' => false,
            ],
        ];
    }
}
