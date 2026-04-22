<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PatientAllergy extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

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
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get allergies by severity
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function bySeverity(Builder $query, AllergySeverity $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get the severity label
     */
    protected function getSeverityLabelAttribute(): string
    {
        return $this->severity->label();
    }

    /**
     * Get the severity color
     */
    protected function getSeverityColorAttribute(): string
    {
        return $this->severity->color();
    }

    /**
     * Get the reaction label
     */
    protected function getReactionLabelAttribute(): string
    {
        return $this->reaction->label();
    }

    /**
     * Get the reaction color
     */
    protected function getReactionColorAttribute(): string
    {
        return $this->reaction->color();
    }
}
