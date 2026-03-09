<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $users
 * @property-read string $price
 * @property-read GeneralStatus $status
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
final class SubscriptionPackage extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPackageFactory> */
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
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
