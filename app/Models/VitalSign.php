<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class VitalSign extends Model
{
    /** @use HasFactory<\Database\Factories\VitalSignFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    protected $fillable = [
        'triage_id',
        'recorded_at',
        'temperature',
        'temperature_unit',
        'pulse_rate',
        'respiratory_rate',
        'systolic_bp',
        'diastolic_bp',
        'map',
        'oxygen_saturation',
        'on_supplemental_oxygen',
        'oxygen_delivery_method',
        'oxygen_flow_rate',
        'blood_glucose',
        'blood_glucose_unit',
        'pain_score',
        'height_cm',
        'weight_kg',
        'bmi',
        'head_circumference_cm',
        'chest_circumference_cm',
        'muac_cm',
        'capillary_refill',
        'recorded_by',
    ];

    #[Override]
    protected $casts = [
        'triage_id' => 'string',
        'recorded_by' => 'string',
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:1',
        'oxygen_saturation' => 'decimal:2',
        'oxygen_flow_rate' => 'decimal:1',
        'blood_glucose' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'bmi' => 'decimal:2',
        'head_circumference_cm' => 'decimal:2',
        'chest_circumference_cm' => 'decimal:2',
        'muac_cm' => 'decimal:2',
        'on_supplemental_oxygen' => 'boolean',
    ];

    public function triage(): BelongsTo
    {
        return $this->belongsTo(TriageRecord::class, 'triage_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
