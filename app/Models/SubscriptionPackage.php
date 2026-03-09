<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $users
 * @property-read string $price
 * @property-read GeneralStatus $status
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
final class SubscriptionPackage extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPackageFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'name',
        'users',
        'price',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'name' => 'string',
            'users' => 'integer',
            'price' => 'decimal:2',
            'status' => GeneralStatus::class,
        ];
    }
}
