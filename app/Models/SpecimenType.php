<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read bool $is_active
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LabTestCatalog> $labTests
 */
final class SpecimenType extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<LabTestCatalog, $this>
     */
    public function labTests(): BelongsToMany
    {
        return $this->belongsToMany(
            LabTestCatalog::class,
            'lab_test_catalog_specimen_type',
            'specimen_type_id',
            'lab_test_catalog_id',
        )->withTimestamps();
    }
}
