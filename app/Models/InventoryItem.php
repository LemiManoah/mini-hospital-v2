<?php

declare(strict_types=1);

namespace App\Models;

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

    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
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

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'inventory_item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, InventoryItemType|string $type): Builder
    {
        return $query->where(
            'item_type',
            $type instanceof InventoryItemType ? $type->value : $type,
        );
    }

    public function scopeDrugs(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::DRUG);
    }

    public function scopeConsumables(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::CONSUMABLE);
    }

    public function scopeSupplies(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::SUPPLY);
    }

    public function scopeReagents(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::REAGENT);
    }

    public function scopeOthers(Builder $query): Builder
    {
        return $query->ofType(InventoryItemType::OTHER);
    }
}
