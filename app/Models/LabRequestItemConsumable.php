<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LabRequestItemConsumable extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'lab_request_item_id' => 'string',
        'quantity' => 'float',
        'unit_cost' => 'float',
        'line_cost' => 'float',
        'used_at' => 'datetime',
        'recorded_by' => 'string',
    ];

    /**
     * @return BelongsTo<LabRequestItem, $this>
     */
    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(LabRequestItem::class, 'lab_request_item_id');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
