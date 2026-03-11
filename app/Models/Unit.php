<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnitType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Override;

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

    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'description',
        'type',
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
            'name' => 'string',
            'symbol' => 'string',
            'description' => 'string',
            'type' => UnitType::class,
            'created_by' => 'string',
            'updated_by' => 'string',
            'deleted_at' => 'datetime',
        ];
    }
}
