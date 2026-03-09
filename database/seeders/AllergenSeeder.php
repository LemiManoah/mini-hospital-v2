<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AllergyType;
use App\Models\Allergen;
use Illuminate\Database\Seeder;

final class AllergenSeeder extends Seeder
{
    public function run(): void
    {
        $allergens = [
            ['name' => 'Penicillin', 'description' => 'Common beta-lactam antibiotic allergy.', 'type' => AllergyType::MEDICATION],
            ['name' => 'Sulfonamides', 'description' => 'Sulfa medication sensitivity.', 'type' => AllergyType::MEDICATION],
            ['name' => 'Peanuts', 'description' => 'Food allergy with risk of severe reactions.', 'type' => AllergyType::FOOD],
            ['name' => 'Shellfish', 'description' => 'Food allergy often linked to crustaceans.', 'type' => AllergyType::FOOD],
            ['name' => 'Pollen', 'description' => 'Seasonal environmental allergen.', 'type' => AllergyType::ENVIRONMENTAL],
            ['name' => 'Dust Mites', 'description' => 'House dust mite sensitivity.', 'type' => AllergyType::ENVIRONMENTAL],
            ['name' => 'Latex', 'description' => 'Latex sensitivity from gloves and medical products.', 'type' => AllergyType::LATEX],
            ['name' => 'Iodinated Contrast', 'description' => 'Contrast media hypersensitivity.', 'type' => AllergyType::CONTRAST],
            ['name' => 'Aspirin', 'description' => 'Aspirin sensitivity or intolerance.', 'type' => AllergyType::MEDICATION],
            ['name' => 'NSAIDs', 'description' => 'Sensitivity to Nonsteroidal Anti-inflammatory Drugs.', 'type' => AllergyType::MEDICATION],
            ['name' => 'Morphine', 'description' => 'Opioid/Morphine sensitivity.', 'type' => AllergyType::MEDICATION],
            ['name' => 'Egg Protein', 'description' => 'Common food allergen, potentially relevant for some vaccines.', 'type' => AllergyType::FOOD],
        ];

        foreach ($allergens as $allergen) {
            Allergen::query()->updateOrCreate(
                ['name' => $allergen['name']],
                $allergen
            );
        }
    }
}
