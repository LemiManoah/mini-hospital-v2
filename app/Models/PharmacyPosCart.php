<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PharmacyPosCartStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $tenant_id
 * @property-read string $branch_id
 * @property-read string $inventory_location_id
 * @property-read string $user_id
 * @property-read string $cart_number
 * @property-read string|null $hold_reference
 * @property-read string|null $customer_name
 * @property-read string|null $customer_phone
 * @property-read string|null $notes
 * @property-read PharmacyPosCartStatus $status
 * @property-read Carbon|null $held_at
 * @property-read Carbon|null $converted_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read InventoryLocation|null $inventoryLocation
 * @property-read User|null $user
 * @property-read Collection<int, PharmacyPosCartItem> $items
 * @property-read PharmacyPosSale|null $sale
 */
final class PharmacyPosCart extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<InventoryLocation, $this> */
    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PharmacyPosCartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PharmacyPosCartItem::class);
    }

    /** @return BelongsTo<PharmacyPosSale, $this> */
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
