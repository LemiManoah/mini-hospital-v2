<?php

declare(strict_types=1);

namespace App\Data\Pharmacy;

final readonly class PostDispenseAllocationDTO
{
    public function __construct(
        public string $inventoryBatchId,
        public float $quantity,
    ) {}

    /**
     * @param  array{
     *   inventory_batch_id: string,
     *   quantity: int|float|string
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryBatchId: $payload['inventory_batch_id'],
            quantity: (float) $payload['quantity'],
        );
    }
}
