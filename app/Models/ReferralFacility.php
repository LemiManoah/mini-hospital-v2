<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Database\Factories\ReferralFacilityFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string $name
 * @property-read string|null $facility_type
 * @property-read string|null $contact_person
 * @property-read string|null $phone
 * @property-read string|null $email
 * @property-read string|null $address
 * @property-read string|null $notes
 * @property-read bool $is_active
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ReferralFacility extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ReferralFacilityFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'tenant_id' => 'string',
            'facility_type' => 'string',
            'contact_person' => 'string',
            'phone' => 'string',
            'email' => 'string',
            'address' => 'string',
            'notes' => 'string',
            'is_active' => 'boolean',
            'created_by' => 'string',
            'updated_by' => 'string',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
