<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Models\Drug;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class DrugSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = Tenant::query()->value('id');

        if (! is_string($tenantId) || $tenantId === '') {
            return;
        }

        $drugs = [
            ['generic_name' => 'Paracetamol', 'brand_name' => 'Panadol', 'drug_code' => 'DRG-001', 'category' => DrugCategory::ANALGESIC, 'dosage_form' => DrugDosageForm::TABLET, 'strength' => '500mg', 'unit' => 'tab'],
            ['generic_name' => 'Amoxicillin', 'brand_name' => 'Amoxil', 'drug_code' => 'DRG-002', 'category' => DrugCategory::ANTIBIOTIC, 'dosage_form' => DrugDosageForm::CAPSULE, 'strength' => '500mg', 'unit' => 'cap'],
            ['generic_name' => 'Artemether/Lumefantrine', 'brand_name' => 'Coartem', 'drug_code' => 'DRG-003', 'category' => DrugCategory::ANTI_MALARIAL, 'dosage_form' => DrugDosageForm::TABLET, 'strength' => '20/120mg', 'unit' => 'tab'],
            ['generic_name' => 'Salbutamol', 'brand_name' => 'Ventolin', 'drug_code' => 'DRG-004', 'category' => DrugCategory::RESPIRATORY, 'dosage_form' => DrugDosageForm::INHALER, 'strength' => '100mcg', 'unit' => 'puff'],
            ['generic_name' => 'Omeprazole', 'brand_name' => null, 'drug_code' => 'DRG-005', 'category' => DrugCategory::GASTROINTESTINAL, 'dosage_form' => DrugDosageForm::CAPSULE, 'strength' => '20mg', 'unit' => 'cap'],
            ['generic_name' => 'Ceftriaxone', 'brand_name' => null, 'drug_code' => 'DRG-006', 'category' => DrugCategory::ANTIBIOTIC, 'dosage_form' => DrugDosageForm::INJECTION, 'strength' => '1g', 'unit' => 'vial'],
        ];

        foreach ($drugs as $drug) {
            Drug::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'drug_code' => $drug['drug_code']],
                [
                    ...$drug,
                    'tenant_id' => $tenantId,
                    'is_controlled' => false,
                    'is_active' => true,
                ],
            );
        }
    }
}
