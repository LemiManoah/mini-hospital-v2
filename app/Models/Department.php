<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Department extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'is_clinical' => 'boolean',
        'is_active' => 'boolean',
        'contact_info' => 'array',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Staff, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'department_staff', 'department_id', 'staff_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['department_code', 'department_name', 'is_clinical', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'department.'.$eventName);
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
