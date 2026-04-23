<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FacilityServiceCategory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FacilityService extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'category' => FacilityServiceCategory::class,
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_billable' => 'boolean',
        'charge_master_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return HasMany<FacilityServiceOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FacilityServiceOrder::class, 'facility_service_id');
    }
}
