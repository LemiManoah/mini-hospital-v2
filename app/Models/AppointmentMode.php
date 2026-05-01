<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

final class AppointmentMode extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'is_virtual' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['name', 'is_virtual', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'appointment_mode.'.$eventName);
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
