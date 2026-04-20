<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacyPosPaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PharmacyPosPayment extends Model
{
    /** @use HasFactory<PharmacyPosPaymentFactory> */
    use HasFactory;

    use HasUuids;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosSale::class, 'pharmacy_pos_sale_id');
    }

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
