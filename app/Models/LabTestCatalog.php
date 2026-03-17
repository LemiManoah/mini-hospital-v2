<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LabTestCatalog extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\LabTestCatalogFactory> */
    use HasFactory;

    use HasUuids;
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
