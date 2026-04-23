<?php

declare(strict_types=1);

namespace App\Data\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

final readonly class DispensePrescriptionDTO
{
    /**
     * @param  list<DispensePrescriptionItemDTO>  $items
     */
    public function __construct(
        public string $inventoryLocationId,
        public string $dispensedAt,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   inventory_location_id: string,
         *   dispensed_at: string,
         *   notes?: string|null,
         *   items: list<array{
         *     prescription_item_id: string,
         *     dispensed_quantity: int|float|string,
         *     external_pharmacy?: bool,
         *     external_reason?: string|null,
         *     notes?: string|null,
         *     substitution_inventory_item_id?: string|null,
         *     allocations?: list<array{
         *       inventory_batch_id: string,
         *       quantity: int|float|string
         *     }>
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return self::fromValidated($validated);
    }

    public function toCreateDispensingRecordDTO(): CreateDispensingRecordDTO
    {
        return new CreateDispensingRecordDTO(
            inventoryLocationId: $this->inventoryLocationId,
            dispensedAt: $this->dispensedAt,
            notes: $this->notes,
            items: array_map(
                static fn (DispensePrescriptionItemDTO $item): CreateDispensingRecordItemDTO => $item->toCreateItemDTO(),
                $this->items,
            ),
        );
    }

    /**
     * @param  array{
     *   inventory_location_id: string,
     *   dispensed_at: string,
     *   notes?: string|null,
     *   items: list<array{
     *     prescription_item_id: string,
     *     dispensed_quantity: int|float|string,
     *     external_pharmacy?: bool,
     *     external_reason?: string|null,
     *     notes?: string|null,
     *     substitution_inventory_item_id?: string|null,
     *     allocations?: list<array{
     *       inventory_batch_id: string,
     *       quantity: int|float|string
     *     }>
     *   }>
     * }  $validated
     */
    private static function fromValidated(array $validated): self
    {
        return new self(
            inventoryLocationId: $validated['inventory_location_id'],
            dispensedAt: $validated['dispensed_at'],
            notes: self::nullableString($validated['notes'] ?? null),
            items: array_map(
                DispensePrescriptionItemDTO::fromPayload(...),
                $validated['items'],
            ),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
