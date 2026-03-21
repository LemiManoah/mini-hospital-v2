<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VisitChargeStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VisitCharge extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\VisitChargeFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_billing_id' => 'string',
        'patient_visit_id' => 'string',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'status' => VisitChargeStatus::class,
        'charged_at' => 'datetime',
    ];

    public function billing(): BelongsTo
    {
        return $this->belongsTo(VisitBilling::class, 'visit_billing_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
