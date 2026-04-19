<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Prescription extends Model
{
    /** @use HasFactory<\Database\Factories\PrescriptionFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'visit_id' => 'string',
        'consultation_id' => 'string',
        'prescribed_by' => 'string',
        'prescription_date' => 'datetime',
        'is_discharge_medication' => 'boolean',
        'is_long_term' => 'boolean',
        'status' => PrescriptionStatus::class,
    ];

    /** @return BelongsTo<Consultation, $this> */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    /** @return BelongsTo<PatientVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    /** @return BelongsTo<Staff, $this> */
    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prescribed_by');
    }

    /** @return HasMany<PrescriptionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id');
    }

    /** @return HasMany<DispensingRecord, $this> */
    public function dispensingRecords(): HasMany
    {
        return $this->hasMany(DispensingRecord::class, 'prescription_id');
    }
}
