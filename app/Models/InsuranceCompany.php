<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class InsuranceCompany extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\InsuranceCompanyFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'main_contact',
        'other_contact',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'status' => GeneralStatus::class,
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
}
