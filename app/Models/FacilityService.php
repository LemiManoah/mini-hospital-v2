<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsultationType;
use App\Enums\FacilityServiceCategory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

final class FacilityService extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'category' => FacilityServiceCategory::class,
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_consultation' => 'boolean',
        'consultation_type' => ConsultationType::class,
        'charge_master_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return HasMany<FacilityServiceOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FacilityServiceOrder::class, 'facility_service_id');
    }

    /**
     * @return BelongsTo<ChargeMaster, $this>
     */
    public function chargeMaster(): BelongsTo
    {
        return $this->belongsTo(ChargeMaster::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['service_code', 'name', 'category', 'is_billable', 'is_consultation', 'consultation_type', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'facility_service.'.$eventName);
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
