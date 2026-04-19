<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PharmacyPosCartStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PharmacyPosCart extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\PharmacyPosCartFactory> */
    use HasFactory;

    use HasUuids;

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyPosCartItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosSale::class, 'id', 'pharmacy_pos_cart_id');
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'branch_id' => 'string',
            'inventory_location_id' => 'string',
            'user_id' => 'string',
            'status' => PharmacyPosCartStatus::class,
            'held_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }
}
