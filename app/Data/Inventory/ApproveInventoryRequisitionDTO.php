<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class ApproveInventoryRequisitionDTO
{
    /**
     * @param  list<ApproveInventoryRequisitionItemDTO>  $items
     */
    public function __construct(
        public ?string $approvalNotes,
        public array $items,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   approval_notes?: string|null,
         *   items: list<array{
         *     inventory_requisition_item_id: string,
         *     approved_quantity: int|float|numeric-string|null
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            approvalNotes: self::nullableString($validated['approval_notes'] ?? null),
            items: array_map(
                ApproveInventoryRequisitionItemDTO::fromPayload(...),
                $validated['items'],
            ),
        );
    }

    /**
     * @return list<array{
     *   inventory_requisition_item_id: string,
     *   approved_quantity: int|float|numeric-string|null
     * }>
     */
    public function itemAttributes(): array
    {
        return array_map(
            static fn (ApproveInventoryRequisitionItemDTO $item): array => $item->toAttributes(),
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
