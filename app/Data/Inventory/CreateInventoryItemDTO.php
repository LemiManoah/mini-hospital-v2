<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateInventoryItemDTO
{
    /**
     * @param  list<string>|null  $therapeuticClasses
     */
    public function __construct(
        public string $itemType,
        public ?string $name,
        public ?string $genericName,
        public ?string $brandName,
        public ?string $description,
        public ?string $unitId,
        public ?string $category,
        public ?string $strength,
        public ?string $dosageForm,
        public int|float $minimumStockLevel,
        public int|float $reorderLevel,
        public int|float|string|null $defaultPurchasePrice,
        public int|float|string|null $defaultSellingPrice,
        public ?string $manufacturer,
        public bool $expires,
        public bool $isControlled,
        public ?string $scheduleClass,
        public ?array $therapeuticClasses,
        public ?string $contraindications,
        public ?string $interactions,
        public ?string $sideEffects,
        public bool $isActive,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     item_type: string,
         *     name?: string|null,
         *     generic_name?: string|null,
         *     brand_name?: string|null,
         *     description?: string|null,
         *     unit_id?: string|null,
         *     category?: string|null,
         *     strength?: string|null,
         *     dosage_form?: string|null,
         *     minimum_stock_level?: int|float,
         *     reorder_level?: int|float,
         *     default_purchase_price?: int|float|string|null,
         *     default_selling_price?: int|float|string|null,
         *     manufacturer?: string|null,
         *     expires?: bool,
         *     is_controlled?: bool,
         *     schedule_class?: string|null,
         *     therapeutic_classes?: list<string>|null,
         *     contraindications?: string|null,
         *     interactions?: string|null,
         *     side_effects?: string|null,
         *     is_active?: bool
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            itemType: $validated['item_type'],
            name: $validated['name'] ?? null,
            genericName: $validated['generic_name'] ?? null,
            brandName: $validated['brand_name'] ?? null,
            description: $validated['description'] ?? null,
            unitId: $validated['unit_id'] ?? null,
            category: $validated['category'] ?? null,
            strength: $validated['strength'] ?? null,
            dosageForm: $validated['dosage_form'] ?? null,
            minimumStockLevel: $validated['minimum_stock_level'] ?? 0,
            reorderLevel: $validated['reorder_level'] ?? 0,
            defaultPurchasePrice: $validated['default_purchase_price'] ?? null,
            defaultSellingPrice: $validated['default_selling_price'] ?? null,
            manufacturer: $validated['manufacturer'] ?? null,
            expires: $validated['expires'] ?? false,
            isControlled: $validated['is_controlled'] ?? false,
            scheduleClass: $validated['schedule_class'] ?? null,
            therapeuticClasses: $validated['therapeutic_classes'] ?? null,
            contraindications: $validated['contraindications'] ?? null,
            interactions: $validated['interactions'] ?? null,
            sideEffects: $validated['side_effects'] ?? null,
            isActive: $validated['is_active'] ?? true,
        );
    }

    /**
     * @return array<string, bool|int|float|string|list<string>|null>
     */
    public function toAttributes(): array
    {
        return [
            'item_type' => $this->itemType,
            'name' => $this->name,
            'generic_name' => $this->genericName,
            'brand_name' => $this->brandName,
            'description' => $this->description,
            'unit_id' => $this->unitId,
            'category' => $this->category,
            'strength' => $this->strength,
            'dosage_form' => $this->dosageForm,
            'minimum_stock_level' => $this->minimumStockLevel,
            'reorder_level' => $this->reorderLevel,
            'default_purchase_price' => $this->defaultPurchasePrice,
            'default_selling_price' => $this->defaultSellingPrice,
            'manufacturer' => $this->manufacturer,
            'expires' => $this->expires,
            'is_controlled' => $this->isControlled,
            'schedule_class' => $this->scheduleClass,
            'therapeutic_classes' => $this->therapeuticClasses,
            'contraindications' => $this->contraindications,
            'interactions' => $this->interactions,
            'side_effects' => $this->sideEffects,
            'is_active' => $this->isActive,
        ];
    }
}
