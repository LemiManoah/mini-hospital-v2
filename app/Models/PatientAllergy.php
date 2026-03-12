<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class PatientAllergy extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\PatientAllergyFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'patient_id',
        'allergen_id',
        'severity',
        'reaction',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'patient_id' => 'string',
        'allergen_id' => 'string',
        'severity' => AllergySeverity::class,
        'reaction' => AllergyReaction::class,
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Allergen, $this>
     */
    public function allergen(): BelongsTo
    {
        return $this->belongsTo(Allergen::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    /**
     * Scope to get only active allergies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get allergies by severity
     */
    public function scopeBySeverity($query, AllergySeverity $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get the severity label
     */
    public function getSeverityLabelAttribute(): string
    {
        return $this->severity->label();
    }

    /**
     * Get the severity color
     */
    public function getSeverityColorAttribute(): string
    {
        return $this->severity->color();
    }

    /**
     * Get the reaction label
     */
    public function getReactionLabelAttribute(): string
    {
        return $this->reaction->label();
    }

    /**
     * Get the reaction color
     */
    public function getReactionColorAttribute(): string
    {
        return $this->reaction->color();
    }
}
