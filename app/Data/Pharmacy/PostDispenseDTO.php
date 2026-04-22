<?php

declare(strict_types=1);

namespace App\Data\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

final readonly class PostDispenseDTO
{
    /**
     * @param  list<PostDispenseItemDTO>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    /**
     * @param  array{
     *   items?: list<array{
     *     dispensing_record_item_id: string,
     *     allocations?: list<array{
     *       inventory_batch_id: string,
     *       quantity: int|float|string
     *     }>
     *   }>
     * }  $validated
     */
    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   items?: list<array{
         *     dispensing_record_item_id: string,
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

    /**
     * @param  array{
     *   items?: list<array{
     *     dispensing_record_item_id: string,
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
            items: array_map(
                PostDispenseItemDTO::fromPayload(...),
                $validated['items'] ?? [],
            ),
        );
    }
}
