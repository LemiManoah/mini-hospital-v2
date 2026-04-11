<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Patient extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $appends = [
        'display_age',
        'display_age_units',
    ];

    protected $casts = [
        'tenant_id' => 'string',
        'address_id' => 'string',
        'country_id' => 'string',
        'date_of_birth' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function activeAllergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class)->where('is_active', true);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PatientVisit::class);
    }

    // scopes
    public function fullname(): string
    {
        return mb_trim(sprintf('%s %s %s', $this->first_name, $this->middle_name, $this->last_name));
    }

    public function ageWithUnits(): ?string
    {
        if ($this->age === null || $this->age_units === null) {
            return null;
        }

        $units = match ($this->age_units) {
            'year' => 'year(s)',
            'month' => 'month(s)',
            'day' => 'day(s)',
            default => $this->age_units,
        };

        return sprintf('%s %s', $this->age, $units);
    }

    public function calculateAge(): ?int
    {
        if ($this->date_of_birth === null) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    public function getDisplayAgeAttribute(): ?int
    {
        if (
            array_key_exists('age', $this->attributes)
            && $this->attributes['age'] !== null
        ) {
            return (int) $this->attributes['age'];
        }

        $dateOfBirth = $this->resolvedDateOfBirth();

        if (! $dateOfBirth instanceof CarbonInterface) {
            return null;
        }

        $today = now()->startOfDay();
        $dob = $dateOfBirth->startOfDay();

        if ($dob->greaterThan($today)) {
            return null;
        }

        $years = (int) $dob->diffInYears($today);

        if ($years >= 1) {
            return $years;
        }

        $months = (int) $dob->diffInMonths($today);

        if ($months >= 1) {
            return $months;
        }

        return (int) $dob->diffInDays($today);
    }

    public function getDisplayAgeUnitsAttribute(): ?string
    {
        if (
            array_key_exists('age', $this->attributes)
            && $this->attributes['age'] !== null
            && array_key_exists('age_units', $this->attributes)
            && $this->attributes['age_units'] !== null
        ) {
            return (string) $this->attributes['age_units'];
        }

        $dateOfBirth = $this->resolvedDateOfBirth();

        if (! $dateOfBirth instanceof CarbonInterface) {
            return null;
        }

        $today = now()->startOfDay();
        $dob = $dateOfBirth->startOfDay();

        if ($dob->greaterThan($today)) {
            return null;
        }

        $years = (int) $dob->diffInYears($today);

        if ($years >= 1) {
            return 'year';
        }

        $months = (int) $dob->diffInMonths($today);

        if ($months >= 1) {
            return 'month';
        }

        return 'day';
    }

    private function resolvedDateOfBirth(): ?CarbonInterface
    {
        if (! array_key_exists('date_of_birth', $this->attributes)) {
            return null;
        }

        $value = $this->attributes['date_of_birth'];

        if ($value === null) {
            return null;
        }

        return $this->asDate($value);
    }
}
