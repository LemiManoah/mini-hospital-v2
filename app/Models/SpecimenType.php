<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read bool $is_active
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Collection<int, LabTestCatalog> $labTests
 */
final class SpecimenType extends Model
{
    use BelongsToTenant;
    use HasActivity;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<LabTestCatalog, $this>
     */
    public function labTests(): BelongsToMany
    {
        return $this->belongsToMany(
            LabTestCatalog::class,
            'lab_test_catalog_specimen_type',
            'specimen_type_id',
            'lab_test_catalog_id',
        )->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('administration')
            ->logOnly(['name', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(static fn (string $eventName): string => 'specimen_type.'.$eventName);
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
