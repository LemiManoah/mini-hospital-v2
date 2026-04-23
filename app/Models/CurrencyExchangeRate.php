<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $tenant_id
 * @property-read string $from_currency_id
 * @property-read string $to_currency_id
 * @property-read float $rate
 * @property-read Carbon $effective_date
 * @property-read string|null $notes
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Currency $fromCurrency
 * @property-read Currency $toCurrency
 */
final class CurrencyExchangeRate extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'rate' => 'float',
            'effective_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Currency, $this> */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    /** @return BelongsTo<Currency, $this> */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }
}
