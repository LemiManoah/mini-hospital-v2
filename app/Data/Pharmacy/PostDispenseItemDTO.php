<?php

declare(strict_types=1);

namespace App\Data\Pharmacy;

final readonly class PostDispenseItemDTO
{
    /**
     * @param  list<PostDispenseAllocationDTO>  $allocations
     */
    public function __construct(
        public string $dispensingRecordItemId,
        public array $allocations,
    ) {}

    /**
     * @param  array{
     *   dispensing_record_item_id: string,
     *   allocations?: list<array{
     *     inventory_batch_id: string,
     *     quantity: int|float|string
     *   }>
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            dispensingRecordItemId: $payload['dispensing_record_item_id'],
            allocations: array_map(
                PostDispenseAllocationDTO::fromPayload(...),
                $payload['allocations'] ?? [],
            ),
        );
    }
}
