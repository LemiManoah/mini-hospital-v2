<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FacilityServiceCategory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

final class FacilityService extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\FacilityServiceFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'service_code',
        'name',
        'category',
        'department_name',
        'description',
        'default_instructions',
        'is_billable',
        'charge_master_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'category' => FacilityServiceCategory::class,
        'is_billable' => 'boolean',
        'charge_master_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(FacilityServiceOrder::class, 'facility_service_id');
    }
}
