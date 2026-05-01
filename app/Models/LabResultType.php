<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

final class LabResultType extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;

    protected $table = 'result_types';

    protected $casts = [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<LabTestCatalog, $this>
     */
    public function labTests(): HasMany
    {
        return $this->hasMany(LabTestCatalog::class, 'result_type_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['code', 'name', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'lab_result_type.'.$eventName);
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
