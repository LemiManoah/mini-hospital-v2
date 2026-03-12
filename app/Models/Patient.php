<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayerType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class Patient extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'patient_number',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'age',
        'age_units',
        'gender',
        'email',
        'phone_number',
        'alternative_phone',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_relationship',
        'address_id',
        'marital_status',
        'occupation',
        'religion',
        'country_id',
        'blood_group',
        'default_payer_type',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'address_id' => 'string',
        'country_id' => 'string',
        'date_of_birth' => 'date',
        'default_payer_type' => PayerType::class,
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
     * @return HasMany<PatientInsurance, $this>
     */
    public function insurances(): HasMany
    {
        return $this->hasMany(PatientInsurance::class);
    }

    /**
     * @return HasOne<PatientInsurance, $this>
     */
    public function primaryInsurance(): HasOne
    {
        return $this->hasOne(PatientInsurance::class);
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
}
