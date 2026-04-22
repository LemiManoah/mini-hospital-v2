<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PharmacyPosSaleStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $tenant_id
 * @property-read string $branch_id
 * @property-read string $inventory_location_id
 * @property-read string|null $pharmacy_pos_cart_id
 * @property-read string $sale_number
 * @property-read string $sale_type
 * @property-read string $gross_amount
 * @property-read string $discount_amount
 * @property-read string $paid_amount
 * @property-read string $balance_amount
 * @property-read string $change_amount
 * @property-read string|null $customer_name
 * @property-read string|null $customer_phone
 * @property-read string|null $notes
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read PharmacyPosSaleStatus $status
 * @property-read Carbon|null $sold_at
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read InventoryLocation|null $inventoryLocation
 * @property-read PharmacyPosCart|null $cart
 * @property-read User|null $createdBy
 * @property-read User|null $updatedBy
 * @property-read Collection<int, PharmacyPosSaleItem> $items
 * @property-read Collection<int, PharmacyPosPayment> $payments
 */
final class PharmacyPosSale extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /** @return BelongsTo<InventoryLocation, $this> */
    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    /** @return BelongsTo<PharmacyPosCart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosCart::class, 'pharmacy_pos_cart_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<PharmacyPosSaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PharmacyPosSaleItem::class);
    }

    /** @return HasMany<PharmacyPosPayment, $this> */
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
