<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string|null $triage_id
 * @property-read string|null $recorded_by
 * @property-read CarbonInterface|null $recorded_at
 * @property-read int|null $pulse_rate
 * @property-read int|null $respiratory_rate
 * @property-read int|null $systolic_bp
 * @property-read int|null $diastolic_bp
 * @property-read numeric-string|null $temperature
 * @property-read numeric-string|null $oxygen_saturation
 * @property-read numeric-string|null $oxygen_flow_rate
 * @property-read numeric-string|null $blood_glucose
 * @property-read numeric-string|null $height_cm
 * @property-read numeric-string|null $weight_kg
 * @property-read numeric-string|null $bmi
 * @property-read numeric-string|null $head_circumference_cm
 * @property-read numeric-string|null $chest_circumference_cm
 * @property-read numeric-string|null $muac_cm
 * @property-read bool $on_supplemental_oxygen
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read TriageRecord|null $triage
 * @property-read Staff|null $recordedBy
 */
final class VitalSign extends Model
{
    use HasFactory;
    use HasUuids;

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

    /**
     * @return BelongsTo<TriageRecord, $this>
     */
    public function triage(): BelongsTo
    {
        return $this->belongsTo(TriageRecord::class, 'triage_id');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
