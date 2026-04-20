<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $code
 * @property-read string $name
 * @property-read string $symbol
 * @property-read int $decimal_places
 * @property-read string $symbol_position
 * @property-read bool $modifiable
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
final class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

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
            'decimal_places' => 'integer',
            'symbol_position' => 'string',
            'modifiable' => 'boolean',
        ];
    }

    /** @return HasMany<CurrencyExchangeRate, $this> */
    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class, 'from_currency_id');
    }

    /** @return HasMany<CurrencyExchangeRate, $this> */
    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class, 'to_currency_id');
    }
}
