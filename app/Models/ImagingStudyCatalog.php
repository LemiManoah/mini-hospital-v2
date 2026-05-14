<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImagingModality;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImagingStudyCatalog extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'modality' => ImagingModality::class,
        'base_price' => 'float',
        'charge_master_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<ChargeMaster, $this>
     */
    public function chargeMaster(): BelongsTo
    {
        return $this->belongsTo(ChargeMaster::class);
    }
}
