<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SpecimenType extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\SpecimenTypeFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
    ];

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
