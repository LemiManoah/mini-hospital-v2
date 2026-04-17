<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PharmacyTreatmentPlanCycleStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PharmacyTreatmentPlanCycle extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'pharmacy_treatment_plan_id' => 'string',
        'cycle_number' => 'integer',
        'scheduled_for' => 'date',
        'status' => PharmacyTreatmentPlanCycleStatus::class,
        'completed_at' => 'datetime',
        'dispensing_record_id' => 'string',
    ];

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(PharmacyTreatmentPlan::class, 'pharmacy_treatment_plan_id');
    }

    public function dispensingRecord(): BelongsTo
    {
        return $this->belongsTo(DispensingRecord::class);
    }
}
