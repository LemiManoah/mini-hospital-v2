<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class FacilityBranch extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\FacilityBranchFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'name',
        'address_id',
        'main_contact',
        'other_contact',
        'email',
        'tenant_id',
        'currency_id',
        'status',
        'is_main_branch',
        'has_store',
        'branch_code',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'status' => GeneralStatus::class,
        'is_main_branch' => 'boolean',
        'has_store' => 'boolean',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return BelongsToMany<Staff, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_branches', 'branch_id', 'staff_id')
            ->withPivot('is_primary_location');
    }
}
