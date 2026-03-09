<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property-read string $id
 * @property-read string $city
 * @property-read string|null $district
 * @property-read string|null $state
 * @property-read string|null $country_id
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Country|null $country
 */
final class Address extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'tenant_id',
        'city',
        'district',
        'state',
        'country_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'tenant_id' => 'string',
            'city' => 'string',
            'district' => 'string',
            'state' => 'string',
            'country_id' => 'string',
            'created_by' => 'string',
            'updated_by' => 'string',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
