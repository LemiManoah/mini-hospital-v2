<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

final class InsuranceCompany extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'status' => GeneralStatus::class,
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
     * @return HasMany<InsurancePackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(InsurancePackage::class);
    }

    /**
     * @return HasMany<InsuranceCompanyInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(InsuranceCompanyInvoice::class);
    }

    /**
     * @return HasMany<InsuredVisitClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuredVisitClaim::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['name', 'email', 'main_contact', 'other_contact', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'insurance_company.'.$eventName);
    }

    public function beforeActivityLogged(Activity $activity, string $event): void
    {
        $user = Auth::user();

        $activity->forceFill([
            'tenant_id' => $this->tenant_id,
            'branch_id' => null,
            'staff_id' => $user instanceof User ? $user->staffId() : null,
        ]);
    }
}
