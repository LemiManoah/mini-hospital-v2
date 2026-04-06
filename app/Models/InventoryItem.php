<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Database\Factories\InventoryItemFactory;
use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use App\Traits\BelongsToTenant;
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function locationItems(): HasMany
    {
        return $this->hasMany(InventoryLocationItem::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(InventoryRequisitionItem::class);
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'inventory_item_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ofType(Builder $query, InventoryItemType|string $type): Builder
    {
        return $query->where(
            'item_type',
            $type instanceof InventoryItemType ? $type->value : $type,
        );
    }

    #[Scope]
    protected function drugs(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::DRUG);
    }

    #[Scope]
    protected function consumables(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::CONSUMABLE);
    }

    #[Scope]
    protected function supplies(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::SUPPLY);
    }

    #[Scope]
    protected function reagents(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::REAGENT);
    }

    #[Scope]
    protected function others(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::OTHER);
    }
}
