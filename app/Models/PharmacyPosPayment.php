<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $pharmacy_pos_sale_id
 * @property-read string|null $receipt_number
 * @property-read string $amount
 * @property-read string $payment_method
 * @property-read string|null $reference_number
 * @property-read bool $is_refund
 * @property-read string|null $notes
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read Carbon $payment_date
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read PharmacyPosSale|null $sale
 * @property-read User|null $createdBy
 */
final class PharmacyPosPayment extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<PharmacyPosSale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosSale::class, 'pharmacy_pos_sale_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'pharmacy_pos_sale_id' => 'string',
            'created_by' => 'string',
            'updated_by' => 'string',
            'amount' => 'decimal:2',
            'is_refund' => 'boolean',
            'payment_date' => 'datetime',
        ];
    }
}
