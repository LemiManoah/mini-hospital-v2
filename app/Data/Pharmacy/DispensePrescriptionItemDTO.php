<?php

declare(strict_types=1);

namespace App\Data\Pharmacy;

final readonly class DispensePrescriptionItemDTO
{
    /**
     * @param  list<PostDispenseAllocationDTO>  $allocations
     */
    public function __construct(
        public string $prescriptionItemId,
        public float $dispensedQuantity,
        public bool $externalPharmacy,
        public ?string $externalReason,
        public ?string $notes,
        public ?string $substitutionInventoryItemId,
        public array $allocations,
    ) {}

    /**
     * @param  array{
     *   prescription_item_id: string,
     *   dispensed_quantity: int|float|string,
     *   external_pharmacy?: bool,
     *   external_reason?: string|null,
     *   notes?: string|null,
     *   substitution_inventory_item_id?: string|null,
     *   allocations?: list<array{
     *     inventory_batch_id: string,
     *     quantity: int|float|string
     *   }>
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            prescriptionItemId: $payload['prescription_item_id'],
            dispensedQuantity: (float) $payload['dispensed_quantity'],
            externalPharmacy: $payload['external_pharmacy'] ?? false,
            externalReason: self::nullableString($payload['external_reason'] ?? null),
            notes: self::nullableString($payload['notes'] ?? null),
            substitutionInventoryItemId: self::nullableString($payload['substitution_inventory_item_id'] ?? null),
            allocations: array_map(
                static fn (array $allocation): PostDispenseAllocationDTO => PostDispenseAllocationDTO::fromPayload($allocation),
                $payload['allocations'] ?? [],
            ),
        );
    }

    public function toCreateItemDTO(): CreateDispensingRecordItemDTO
    {
        return new CreateDispensingRecordItemDTO(
            prescriptionItemId: $this->prescriptionItemId,
            dispensedQuantity: $this->dispensedQuantity,
            externalPharmacy: $this->externalPharmacy,
            externalReason: $this->externalReason,
            notes: $this->notes,
            substitutionInventoryItemId: $this->substitutionInventoryItemId,
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
