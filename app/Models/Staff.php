<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffType;
use App\Support\BranchContext;
use App\Traits\BelongsToTenant;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $staff_position_id
 * @property-read string|null $address_id
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string $employee_number
 * @property-read StaffType|null $type
 * @property-read bool $is_active
 * @property-read Carbon|null $hire_date
 * @property-read Carbon|null $termination_date
 * @property-read Carbon|null $last_login_at
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Tenant|null $tenant
 * @property-read StaffPosition|null $position
 * @property-read Address|null $address
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FacilityBranch> $branches
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $departments
 */
final class Staff extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'staff_position_id' => 'string',
        'address_id' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'employee_number' => 'string',
        'type' => StaffType::class,
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<StaffPosition, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class, 'staff_position_id');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return BelongsToMany<FacilityBranch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(FacilityBranch::class, 'staff_branches', 'staff_id', 'branch_id')
            ->withPivot('is_primary_location');
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_staff', 'staff_id', 'department_id');
    }

    /**
     * @param  Builder<Staff>  $query
     * @return Builder<Staff>
     */
    #[Scope]
    protected function doctors(Builder $query): Builder
    {
        return $query->whereHas('position', function (Builder $query): void {
            $query->where('name', 'like', '%Doctor%')
                ->orWhere('name', 'like', '%Consultant%');
        });
    }

    /**
     * @param  Builder<Staff>  $query
     * @return Builder<Staff>
     */
    #[Scope]
    protected function forActiveBranch(Builder $query): Builder
    {
        $branchId = BranchContext::getActiveBranchId();

        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas('branches', function (Builder $branchQuery) use ($branchId): void {
            $branchQuery->where('facility_branches.id', $branchId);
        });
    }
}
