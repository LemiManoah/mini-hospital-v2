<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use App\Traits\BelongsToTenant;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InventoryItem extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'unit_id' => 'string',
        'item_type' => InventoryItemType::class,
        'category' => DrugCategory::class,
        'dosage_form' => DrugDosageForm::class,
        'minimum_stock_level' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'default_purchase_price' => 'decimal:2',
        'default_selling_price' => 'decimal:2',
        'expires' => 'boolean',
        'is_controlled' => 'boolean',
        'therapeutic_classes' => 'array',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<InventoryLocationItem, $this>
     */
    public function locationItems(): HasMany
    {
        return $this->hasMany(InventoryLocationItem::class);
    }

    /**
     * @return HasMany<InventoryBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<ReconciliationItem, $this>
     */
    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    /**
     * @return HasMany<InventoryRequisitionItem, $this>
     */
    public function requisitionItems(): HasMany
    {
        return $this->hasMany(InventoryRequisitionItem::class);
    }

    /**
     * @return HasMany<PrescriptionItem, $this>
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'inventory_item_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function ofType(Builder $query, InventoryItemType|string $type): Builder
    {
        return $query->where(
            'item_type',
            $type instanceof InventoryItemType ? $type->value : $type,
        );
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function drugs(Builder $query): void
    {
        $query->where('item_type', InventoryItemType::DRUG->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function consumables(Builder $query): void
    {
        $query->where('item_type', InventoryItemType::CONSUMABLE->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function supplies(Builder $query): void
    {
        $query->where('item_type', InventoryItemType::SUPPLY->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function reagents(Builder $query): void
    {
        $query->where('item_type', InventoryItemType::REAGENT->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function others(Builder $query): void
    {
        $query->where('item_type', InventoryItemType::OTHER->value);
    }
}
