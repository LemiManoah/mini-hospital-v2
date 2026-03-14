<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class LabTestCatalog extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\LabTestCatalogFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'test_code',
        'test_name',
        'category',
        'sub_category',
        'department_id',
        'specimen_type',
        'container_type',
        'volume_required_ml',
        'storage_requirements',
        'turnaround_time_minutes',
        'base_price',
        'requires_fasting',
        'reference_ranges',
        'is_active',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'department_id' => 'string',
        'volume_required_ml' => 'float',
        'turnaround_time_minutes' => 'integer',
        'base_price' => 'float',
        'requires_fasting' => 'boolean',
        'reference_ranges' => 'array',
        'is_active' => 'boolean',
    ];
}
