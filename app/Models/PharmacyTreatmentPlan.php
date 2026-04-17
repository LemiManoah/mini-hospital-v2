<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PharmacyTreatmentPlanFrequencyUnit;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PharmacyTreatmentPlan extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'visit_id' => 'string',
        'prescription_id' => 'string',
        'start_date' => 'date',
        'frequency_unit' => PharmacyTreatmentPlanFrequencyUnit::class,
        'frequency_interval' => 'integer',
        'total_authorized_cycles' => 'integer',
        'completed_cycles' => 'integer',
        'next_refill_date' => 'date',
        'status' => PharmacyTreatmentPlanStatus::class,
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyTreatmentPlanItem::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(PharmacyTreatmentPlanCycle::class);
    }
}
