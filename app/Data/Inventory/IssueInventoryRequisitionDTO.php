<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class IssueInventoryRequisitionDTO
{
    /**
     * @param  list<IssueInventoryRequisitionItemDTO>  $items
     */
    public function __construct(
        public ?string $issuedNotes,
        public array $items,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   issued_notes?: string|null,
         *   items: list<array{
         *     inventory_requisition_item_id: string,
         *     issue_quantity: float|int|string,
         *     notes?: string|null,
         *     allocations?: list<array{inventory_batch_id: string, quantity: float|int|string}>
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            issuedNotes: self::nullableString($validated['issued_notes'] ?? null),
            items: array_map(
                IssueInventoryRequisitionItemDTO::fromPayload(...),
                $validated['items'],
            ),
        );
    }

    /**
     * @return list<array{
     *   inventory_requisition_item_id: string,
     *   issue_quantity: float|int|string,
     *   notes: ?string,
     *   allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>
     * }>
     */
    public function itemAttributes(): array
    {
        return array_map(
            static fn (IssueInventoryRequisitionItemDTO $item): array => $item->toAttributes(),
            $this->items,
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
