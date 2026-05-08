<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DataImportStatus;
use App\Models\DataImport;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataImport>
 */
final class DataImportFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::factory();

        return [
            'tenant_id' => $tenant,
            'branch_id' => FacilityBranch::factory()->for($tenant),
            'user_id' => User::factory()->for($tenant),
            'import_type' => 'inventory_drug',
            'source_filename' => $this->faker->word().'.xlsx',
            'stored_path' => 'imports/inventory/'.$this->faker->uuid().'.xlsx',
            'status' => DataImportStatus::Queued,
            'imported_count' => 0,
            'skipped_count' => 0,
            'preview_count' => 0,
            'preview_rows' => [],
            'error_report' => [],
        ];
    }
}
