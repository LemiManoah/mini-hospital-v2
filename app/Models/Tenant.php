<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'has_branches' => 'boolean',
        'status' => GeneralStatus::class,
        'facility_level' => FacilityLevel::class,
        'onboarding_completed_at' => 'datetime',
    ];

    public function isOnboardingComplete(): bool
    {
        return $this->onboarding_completed_at instanceof CarbonInterface;
    }

    /**
     * @return BelongsTo<SubscriptionPackage, $this>
     */
    public function subscriptionPackage(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class);
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
     * @return HasMany<FacilityBranch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(FacilityBranch::class);
    }

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
