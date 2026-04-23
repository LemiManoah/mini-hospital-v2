<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Patient extends Model
{
    use HasFactory;
    use BelongsToTenant;
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

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return HasMany<PatientAllergy, $this>
     */
    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    /**
     * @return HasMany<PatientAllergy, $this>
     */
    public function activeAllergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class)->where('is_active', true);
    }

    /**
     * @return HasMany<PatientVisit, $this>
     */
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

        $units = $this->age_units === 'year'
            ? 'year(s)'
            : ($this->age_units === 'month'
                ? 'month(s)'
                : 'day(s)');

        return sprintf('%s %s', $this->age, $units);
    }

    public function calculateAge(): ?int
    {
        if ($this->date_of_birth === null) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    protected function getDisplayAgeAttribute(): ?int
    {
        if (
            array_key_exists('age', $this->attributes)
            && $this->attributes['age'] !== null
        ) {
            $age = $this->attributes['age'];

            return is_numeric($age) ? (int) $age : null;
        }

        $dateOfBirth = $this->resolvedDateOfBirth();

        if (! $dateOfBirth instanceof CarbonInterface) {
            return null;
        }

        $today = today();
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

    protected function getDisplayAgeUnitsAttribute(): ?string
    {
        if (
            array_key_exists('age', $this->attributes)
            && $this->attributes['age'] !== null
            && array_key_exists('age_units', $this->attributes)
            && $this->attributes['age_units'] !== null
        ) {
            $ageUnits = $this->attributes['age_units'];

            return is_string($ageUnits) ? $ageUnits : null;
        }

        $dateOfBirth = $this->resolvedDateOfBirth();

        if (! $dateOfBirth instanceof CarbonInterface) {
            return null;
        }

        $today = today();
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

        if (is_string($value)) {
            return $this->asDate($value);
        }

        return $value instanceof CarbonInterface ? $value : null;
    }
}
