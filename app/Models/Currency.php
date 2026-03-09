<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property-read string $id
 * @property-read string $code
 * @property-read string $name
 * @property-read string $symbol
 * @property-read bool $modifiable
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
final class Currency extends Model
{
    /** @use HasFactory<\Database\Factories\CurrencyFactory> */
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
        'code',
        'name',
        'symbol',
        'modifiable',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'code' => 'string',
            'name' => 'string',
            'symbol' => 'string',
            'modifiable' => 'boolean',
        ];
    }
}
