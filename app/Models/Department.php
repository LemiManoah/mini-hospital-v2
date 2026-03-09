<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'head_of_department_id',
        'is_clinical',
        'is_active',
        'contact_info',
    ];

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
     * @return BelongsTo<Staff, $this>
     */
    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'head_of_department_id');
    }

    /**
     * @return HasMany<Staff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
