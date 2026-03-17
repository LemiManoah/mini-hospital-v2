<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $country_name
 * @property-read string $country_code
 * @property-read string $dial_code
 * @property-read string $currency
 * @property-read string $currency_symbol
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Collection<int, Address> $addresses
 */
final class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'country_name' => 'string',
            'country_code' => 'string',
            'dial_code' => 'string',
            'currency' => 'string',
            'currency_symbol' => 'string',
        ];
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
