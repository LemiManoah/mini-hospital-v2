<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PharmacyPosSaleStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Database\Factories\PharmacyPosSaleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PharmacyPosSale extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<PharmacyPosSaleFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosCart::class, 'pharmacy_pos_cart_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyPosSaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PharmacyPosPayment::class);
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'branch_id' => 'string',
            'inventory_location_id' => 'string',
            'pharmacy_pos_cart_id' => 'string',
            'created_by' => 'string',
            'updated_by' => 'string',
            'status' => PharmacyPosSaleStatus::class,
            'gross_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }
}
