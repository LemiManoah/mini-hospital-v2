<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnitType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $symbol
 * @property-read string|null $description
 * @property-read UnitType $type
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
final class Unit extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'tenant_id' => 'string',
            'name' => 'string',
            'symbol' => 'string',
            'description' => 'string',
            'type' => UnitType::class,
            'created_by' => 'string',
            'updated_by' => 'string',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['name', 'symbol', 'description', 'type'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'unit.'.$eventName);
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
